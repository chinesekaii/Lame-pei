<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\DemandeSurMesure;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Tous les e-mails envoyés par le site.
 *
 * Un e-mail qui ne part pas (serveur SMTP indisponible…) ne doit jamais bloquer
 * le client : l'erreur est journalisée et la méthode renvoie false.
 */
class Courrier
{
    private const NOM_EXPEDITEUR = 'L\'ÂME PEÏ';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'MAILER_FROM')] private readonly string $adresseExpediteur,
        #[Autowire(env: 'EMAIL_MARQUE')] private readonly string $adresseMarque,
    ) {
    }

    // ---------- Clients ----------

    public function confirmationCommande(Commande $commande): bool
    {
        return $this->envoyer(
            $this->pourClient($commande->getUser()->getEmail())
                ->subject(sprintf('Votre commande %s est confirmée', $commande->getReference()))
                ->htmlTemplate('emails/commande_confirmation.html.twig')
                ->context(['commande' => $commande]),
        );
    }

    public function changementStatutCommande(Commande $commande): bool
    {
        return $this->envoyer(
            $this->pourClient($commande->getUser()->getEmail())
                ->subject(sprintf('Commande %s : %s', $commande->getReference(), mb_strtolower($commande->getStatut()->label())))
                ->htmlTemplate('emails/commande_statut.html.twig')
                ->context(['commande' => $commande]),
        );
    }

    public function reinitialisationMotDePasse(User $user, string $lien, int $minutesValidite): bool
    {
        return $this->envoyer(
            $this->pourClient($user->getEmail())
                ->subject('Choisissez un nouveau mot de passe')
                ->htmlTemplate('emails/mot_de_passe.html.twig')
                ->context(['user' => $user, 'lien' => $lien, 'minutes' => $minutesValidite]),
        );
    }

    public function accuseDemandeSurMesure(DemandeSurMesure $demande): bool
    {
        return $this->envoyer(
            $this->pourClient($demande->getEmail())
                ->subject('Votre demande sur-mesure est bien arrivée')
                ->htmlTemplate('emails/sur_mesure_client.html.twig')
                ->context(['demande' => $demande]),
        );
    }

    public function sortieModele(Alerte $alerte): bool
    {
        return $this->envoyer(
            $this->pourClient($alerte->getEmail())
                ->subject(sprintf('%s est disponible !', $alerte->getModele()->getNom()))
                ->htmlTemplate('emails/sortie_modele.html.twig')
                ->context(['modele' => $alerte->getModele()]),
        );
    }

    // ---------- Marque ----------

    public function nouvelleCommandePourMarque(Commande $commande): bool
    {
        return $this->envoyer(
            $this->pourMarque()
                ->replyTo($commande->getUser()->getEmail())
                ->subject(sprintf('Nouvelle commande %s — %s', $commande->getReference(), number_format($commande->getTotal() / 100, 2, ',', ' ').' €'))
                ->htmlTemplate('emails/commande_marque.html.twig')
                ->context(['commande' => $commande]),
        );
    }

    public function nouvelleDemandePourMarque(DemandeSurMesure $demande): bool
    {
        return $this->envoyer(
            $this->pourMarque()
                ->replyTo(new Address($demande->getEmail(), $demande->getNom()))
                ->subject(sprintf('Nouvelle demande sur-mesure de %s', $demande->getNom()))
                ->htmlTemplate('emails/sur_mesure_marque.html.twig')
                ->context(['demande' => $demande]),
        );
    }

    public function nouvelAvisPourMarque(Avis $avis): bool
    {
        return $this->envoyer(
            $this->pourMarque()
                ->subject(sprintf('Nouvel avis à valider : %s (%d/5)', $avis->getModele()?->getNom() ?? 'avis général', (int) $avis->getNote()))
                ->htmlTemplate('emails/avis_marque.html.twig')
                ->context(['avis' => $avis]),
        );
    }

    // ---------- Outils ----------

    /** E-mail destiné à un client : les réponses arrivent à la marque. */
    private function pourClient(string $destinataire): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address($this->adresseExpediteur, self::NOM_EXPEDITEUR))
            ->replyTo(new Address($this->adresseMarque, self::NOM_EXPEDITEUR))
            ->to($destinataire);
    }

    /** E-mail de notification destiné à la marque. */
    private function pourMarque(): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address($this->adresseExpediteur, self::NOM_EXPEDITEUR.' · site'))
            ->to($this->adresseMarque);
    }

    private function envoyer(TemplatedEmail $email): bool
    {
        try {
            $this->mailer->send($email);

            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('E-mail non envoyé : {sujet} ({erreur})', [
                'sujet' => $email->getSubject(),
                'erreur' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
