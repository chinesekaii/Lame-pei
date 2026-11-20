<?php

namespace App\Service;

use App\Entity\Commande;
use App\Enum\StatutCommande;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Paiement par carte avec Stripe Checkout (page de paiement hébergée par Stripe).
 *
 *  1. creerSession() : la commande (déjà enregistrée « en attente ») est envoyée à Stripe,
 *     qui renvoie l'adresse de sa page de paiement ;
 *  2. au retour du client (synchroniser()) ou via le webhook (traiterWebhook()),
 *     la commande passe en « Payée » et les e-mails de confirmation partent ;
 *  3. si le client ne paie pas, la session expire : la commande est annulée et le stock libéré.
 */
class PaiementStripe
{
    /** Durée pendant laquelle les paires restent réservées pour le client (Stripe : 30 min minimum). */
    public const DUREE_RESERVATION_MINUTES = 60;

    private ?StripeClient $stripe = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CommandeManager $commandeManager,
        private readonly Courrier $courrier,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'STRIPE_SECRET_KEY')] private readonly string $cleSecrete,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')] private readonly string $secretWebhook,
    ) {
    }

    /** Faux tant que la clé secrète Stripe n'est pas renseignée dans .env.local. */
    public function estConfigure(): bool
    {
        return str_starts_with($this->cleSecrete, 'sk_');
    }

    /**
     * Crée la page de paiement Stripe pour cette commande et renvoie son adresse.
     */
    public function creerSession(Commande $commande): string
    {
        $lignes = [];
        foreach ($commande->getLignes() as $ligne) {
            $lignes[] = [
                'quantity' => $ligne->getQuantite(),
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $ligne->getPrixUnitaire(), // déjà en centimes
                    'product_data' => [
                        'name' => sprintf('%s — taille %s', $ligne->getVariante()->getModele()->getNom(), $ligne->getVariante()->getTaille()),
                    ],
                ],
            ];
        }

        if ($commande->getFraisLivraison() > 0) {
            $lignes[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $commande->getFraisLivraison(),
                    'product_data' => ['name' => sprintf('Livraison en main propre (%s)', $commande->getSecteurLivraison()?->label())],
                ],
            ];
        }

        $reference = $commande->getReference();
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'locale' => 'fr',
            'line_items' => $lignes,
            'customer_email' => $commande->getUser()->getEmail(),
            'client_reference_id' => $reference,
            'metadata' => ['reference' => $reference],
            'payment_intent_data' => ['metadata' => ['reference' => $reference]],
            'expires_at' => time() + self::DUREE_RESERVATION_MINUTES * 60,
            // {CHECKOUT_SESSION_ID} est remplacé par Stripe au retour du client
            'success_url' => $this->urlGenerator->generate('app_commande_confirmation', ['reference' => $reference], UrlGeneratorInterface::ABSOLUTE_URL).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urlGenerator->generate('app_commande_paiement_annule', ['reference' => $reference], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $commande->setStripeSessionId($session->id);
        $this->em->flush();

        return $session->url;
    }

    /**
     * Au retour du client sur le site : on demande à Stripe où en est le paiement.
     * (Indispensable en local, où le webhook n'arrive pas forcément.)
     */
    public function synchroniser(Commande $commande, string $sessionId): void
    {
        if (!$commande->isEnAttenteDePaiement() || $sessionId !== $commande->getStripeSessionId()) {
            return;
        }

        $this->appliquerSession($this->client()->checkout->sessions->retrieve($sessionId));
    }

    /**
     * Notification envoyée par Stripe (paiement réussi, session expirée…).
     *
     * @throws \UnexpectedValueException|\Stripe\Exception\SignatureVerificationException si le message n'est pas authentique
     */
    public function traiterWebhook(string $contenu, string $signature): void
    {
        $evenement = Webhook::constructEvent($contenu, $signature, $this->secretWebhook);

        if (\in_array($evenement->type, [Event::CHECKOUT_SESSION_COMPLETED, Event::CHECKOUT_SESSION_ASYNC_PAYMENT_SUCCEEDED, Event::CHECKOUT_SESSION_EXPIRED], true)) {
            $this->appliquerSession($evenement->data->object);
        }
    }

    /** Met la commande à jour selon l'état de la session Stripe. */
    private function appliquerSession(Session $session): void
    {
        $commande = $this->em->getRepository(Commande::class)->findOneBy(['reference' => $session->client_reference_id]);
        if ($commande === null) {
            $this->logger->warning('Session Stripe {id} : commande {reference} introuvable.', ['id' => $session->id, 'reference' => $session->client_reference_id]);

            return;
        }

        if ($session->payment_status === Session::PAYMENT_STATUS_PAID) {
            $this->marquerPayee($commande, \is_string($session->payment_intent) ? $session->payment_intent : $session->payment_intent?->id);
        } elseif ($session->status === Session::STATUS_EXPIRED && $session->id === $commande->getStripeSessionId()) {
            // Seule la dernière session compte : le client a pu en ouvrir une nouvelle
            if ($this->commandeManager->annulerCommandeImpayee($commande)) {
                $this->em->flush();
                $this->logger->info('Commande {reference} annulée : paiement non effectué à temps.', ['reference' => $commande->getReference()]);
            }
        }
    }

    /**
     * Passe la commande en « Payée » (une seule fois, même si Stripe prévient plusieurs fois)
     * et envoie les e-mails de confirmation.
     */
    private function marquerPayee(Commande $commande, ?string $paiementId): void
    {
        $vientDEtrePayee = $this->em->wrapInTransaction(function () use ($commande, $paiementId): bool {
            $this->em->refresh($commande, LockMode::PESSIMISTIC_WRITE);
            if ($commande->getStatut() === StatutCommande::Payee || $commande->getPayeeLe() !== null) {
                return false;
            }

            if ($commande->getStatut() === StatutCommande::Annulee) {
                // Cas rare : paiement reçu juste après l'annulation automatique.
                // Le client a payé : on reprend la commande et on retire de nouveau les paires du stock.
                // Si une paire est partie entre-temps, la marque devra rembourser (le stock ne descend pas sous 0).
                foreach ($commande->getLignes() as $ligne) {
                    $variante = $ligne->getVariante();
                    $variante->setStock(max(0, $variante->getStock() - $ligne->getQuantite()));
                }
                $this->logger->critical('Commande {reference} payée alors qu\'elle venait d\'être annulée (paiement {paiement}) : vérifier le stock.', ['reference' => $commande->getReference(), 'paiement' => $paiementId]);
            }

            $commande->setStatut(StatutCommande::Payee)
                ->setPayeeLe(new \DateTimeImmutable())
                ->setStripePaiementId($paiementId);

            return true;
        });

        if ($vientDEtrePayee) {
            $this->courrier->confirmationCommande($commande);
            $this->courrier->nouvelleCommandePourMarque($commande);
        }
    }

    private function client(): StripeClient
    {
        if (!$this->estConfigure()) {
            throw new \LogicException('Paiement Stripe non configuré : renseignez STRIPE_SECRET_KEY dans .env.local.');
        }

        return $this->stripe ??= new StripeClient($this->cleSecrete);
    }
}
