<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\User;
use App\Form\MotDePasseType;
use App\Form\ProfilType;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Espace client : commandes, informations personnelles, mot de passe.
 */
#[Route('/compte')]
#[IsGranted('ROLE_USER')]
final class CompteController extends AbstractController
{
    #[Route('', name: 'app_compte', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, CommandeRepository $commandeRepository): Response
    {
        return $this->render('compte/index.html.twig', [
            'commandes' => $commandeRepository->findBy(['user' => $user], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/commandes/{reference}', name: 'app_compte_commande', methods: ['GET'])]
    public function commande(string $reference, #[CurrentUser] User $user, CommandeRepository $commandeRepository, AvisRepository $avisRepository): Response
    {
        // Un client ne voit que ses propres commandes
        $commande = $commandeRepository->findOneBy(['reference' => $reference, 'user' => $user]);

        if ($commande === null) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        // Modèles sur lesquels le client a déjà laissé un avis (pour ne pas lui reproposer)
        $avisLaisses = array_map(
            fn (Avis $a) => $a->getModele()?->getId(),
            $avisRepository->findBy(['user' => $user]),
        );

        return $this->render('compte/commande.html.twig', [
            'commande' => $commande,
            'avisLaisses' => $avisLaisses,
        ]);
    }

    #[Route('/informations', name: 'app_compte_profil', methods: ['GET', 'POST'])]
    public function profil(#[CurrentUser] User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->flush();
                $this->addFlash('success', 'Vos informations sont enregistrées.');

                return $this->redirectToRoute('app_compte_profil', status: Response::HTTP_SEE_OTHER);
            }

            // Le formulaire a modifié l'utilisateur connecté en mémoire : on annule ces changements
            // pour ne pas garder un e-mail invalide dans la session (sinon déconnexion à la requête suivante).
            $em->refresh($user);
        }

        return $this->render('compte/profil.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_compte_mot_de_passe', methods: ['GET', 'POST'])]
    public function motDePasse(
        #[CurrentUser] User $user,
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(MotDePasseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('nouveau')->getData()));
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié.');

            return $this->redirectToRoute('app_compte', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte/mot_de_passe.html.twig', [
            'form' => $form,
        ]);
    }
}
