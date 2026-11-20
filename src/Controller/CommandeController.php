<?php

namespace App\Controller;

use App\Dto\AdresseLivraison;
use App\Entity\Commande;
use App\Entity\User;
use App\Form\AdresseLivraisonType;
use App\Repository\CommandeRepository;
use App\Service\CommandeManager;
use App\Service\PaiementStripe;
use App\Service\Panier;
use App\Service\StockInsuffisantException;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
#[IsGranted('ROLE_USER')]
final class CommandeController extends AbstractController
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Panier $panier,
        CommandeManager $commandeManager,
        PaiementStripe $paiement,
        #[CurrentUser] User $client,
    ): Response {
        if ($panier->estVide()) {
            $this->addFlash('error', 'Votre panier est vide.');

            return $this->redirectToRoute('app_panier_index');
        }

        $adresse = new AdresseLivraison();
        $adresse->nomComplet = trim($client->getPrenom().' '.$client->getNom());

        $form = $this->createForm(AdresseLivraisonType::class, $adresse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $commande = $commandeManager->creerDepuisPanier($client, $adresse);
            } catch (StockInsuffisantException $e) {
                $this->addFlash('error', $e->getMessage().' Votre panier a été mis à jour.');

                return $this->redirectToRoute('app_panier_index', status: Response::HTTP_SEE_OTHER);
            }

            // Les e-mails de confirmation partent une fois le paiement reçu (voir PaiementStripe)
            return $this->allerAuPaiement($commande, $paiement);
        }

        return $this->render('commande/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{reference}/confirmation', name: 'app_commande_confirmation', methods: ['GET'])]
    public function confirmation(string $reference, Request $request, PaiementStripe $paiement, #[CurrentUser] User $client): Response
    {
        $commande = $this->trouverCommande($reference, $client);

        // Retour depuis la page Stripe : on vérifie tout de suite si le paiement est passé
        $sessionId = $request->query->getString('session_id');
        if ($sessionId !== '' && $paiement->estConfigure()) {
            try {
                $paiement->synchroniser($commande, $sessionId);
            } catch (ApiErrorException $e) {
                $this->logger->error('Stripe indisponible au retour du client : {erreur}', ['erreur' => $e->getMessage()]);
            }
        }

        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
        ]);
    }

    /** Le client a cliqué sur « Retour » sur la page Stripe. */
    #[Route('/{reference}/paiement-annule', name: 'app_commande_paiement_annule', methods: ['GET'])]
    public function paiementAnnule(string $reference, #[CurrentUser] User $client): Response
    {
        $commande = $this->trouverCommande($reference, $client);

        if ($commande->isEnAttenteDePaiement()) {
            $this->addFlash('error', sprintf(
                'Le paiement n\'a pas été effectué. Vos paires restent réservées %d minutes : vous pouvez payer à nouveau ci-dessous.',
                PaiementStripe::DUREE_RESERVATION_MINUTES,
            ));
        }

        return $this->redirectToRoute('app_commande_confirmation', ['reference' => $reference]);
    }

    /** Bouton « Payer maintenant » d'une commande encore en attente de paiement. */
    #[Route('/{reference}/payer', name: 'app_commande_payer', methods: ['POST'])]
    public function payer(string $reference, Request $request, PaiementStripe $paiement, #[CurrentUser] User $client): Response
    {
        $commande = $this->trouverCommande($reference, $client);

        if (!$this->isCsrfTokenValid('payer-'.$reference, $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Votre session a expiré, merci de réessayer.');
        } elseif (!$commande->isEnAttenteDePaiement()) {
            $this->addFlash('error', 'Cette commande ne peut plus être payée en ligne.');
        } else {
            return $this->allerAuPaiement($commande, $paiement);
        }

        return $this->redirectToRoute('app_commande_confirmation', ['reference' => $reference], Response::HTTP_SEE_OTHER);
    }

    /** Redirige vers la page de paiement Stripe (ou explique pourquoi c'est impossible). */
    private function allerAuPaiement(Commande $commande, PaiementStripe $paiement): Response
    {
        $confirmation = $this->generateUrl('app_commande_confirmation', ['reference' => $commande->getReference()]);

        if (!$paiement->estConfigure()) {
            $this->addFlash('error', 'Le paiement en ligne n\'est pas encore disponible. Votre commande est enregistrée : nous vous contactons pour le règlement.');

            return $this->redirect($confirmation, Response::HTTP_SEE_OTHER);
        }

        try {
            return $this->redirect($paiement->creerSession($commande), Response::HTTP_SEE_OTHER);
        } catch (ApiErrorException $e) {
            $this->logger->error('Création de la session Stripe impossible : {erreur}', ['erreur' => $e->getMessage()]);
            $this->addFlash('error', 'Le paiement est momentanément indisponible. Votre commande est enregistrée : réessayez dans quelques minutes.');

            return $this->redirect($confirmation, Response::HTTP_SEE_OTHER);
        }
    }

    /** Un client ne voit que ses propres commandes. */
    private function trouverCommande(string $reference, User $client): Commande
    {
        $commande = $this->commandeRepository->findOneBy(['reference' => $reference, 'user' => $client]);

        if ($commande === null) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $commande;
    }
}
