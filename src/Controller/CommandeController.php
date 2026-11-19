<?php

namespace App\Controller;

use App\Dto\AdresseLivraison;
use App\Entity\User;
use App\Form\AdresseLivraisonType;
use App\Repository\CommandeRepository;
use App\Service\CommandeManager;
use App\Service\Panier;
use App\Service\StockInsuffisantException;
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
    #[Route('', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Panier $panier,
        CommandeManager $commandeManager,
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

            return $this->redirectToRoute('app_commande_confirmation', [
                'reference' => $commande->getReference(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{reference}/confirmation', name: 'app_commande_confirmation', methods: ['GET'])]
    public function confirmation(
        string $reference,
        CommandeRepository $commandeRepository,
        #[CurrentUser] User $client,
    ): Response {
        // Un client ne peut voir que ses propres commandes
        $commande = $commandeRepository->findOneBy(['reference' => $reference, 'user' => $client]);

        if ($commande === null) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
        ]);
    }
}
