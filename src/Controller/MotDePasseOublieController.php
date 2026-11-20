<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NouveauMotDePasseType;
use App\Repository\UserRepository;
use App\Security\LienReinitialisation;
use App\Service\Courrier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class MotDePasseOublieController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_mot_de_passe_oublie', methods: ['GET', 'POST'])]
    public function demander(
        Request $request,
        UserRepository $userRepository,
        LienReinitialisation $lien,
        Courrier $courrier,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('mot-de-passe-oublie', $request->getPayload()->getString('_token'))) {
                $this->addFlash('error', 'Votre session a expiré, merci de réessayer.');

                return $this->redirectToRoute('app_mot_de_passe_oublie');
            }

            $email = mb_strtolower(trim($request->getPayload()->getString('email')));
            $user = $email !== '' ? $userRepository->findOneBy(['email' => $email]) : null;

            if ($user !== null) {
                $courrier->reinitialisationMotDePasse($user, $lien->generer($user), LienReinitialisation::DUREE_MINUTES);
            }

            // Même message que le compte existe ou non : on ne révèle pas quelles adresses sont inscrites
            $this->addFlash('success', 'Si un compte existe avec cette adresse, un e-mail vient de vous être envoyé avec un lien pour choisir un nouveau mot de passe.');

            return $this->redirectToRoute('app_login', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('security/mot_de_passe_oublie.html.twig');
    }

    #[Route('/reinitialiser-mot-de-passe/{id}', name: 'app_reinitialiser_mot_de_passe', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function reinitialiser(
        int $id,
        Request $request,
        UserRepository $userRepository,
        LienReinitialisation $lien,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $user = $userRepository->find($id);

        if (!$user instanceof User || !$lien->estValide($request, $user)) {
            $this->addFlash('error', 'Ce lien n\'est plus valable (il a expiré ou a déjà servi). Faites une nouvelle demande.');

            return $this->redirectToRoute('app_mot_de_passe_oublie');
        }

        $form = $this->createForm(NouveauMotDePasseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('nouveau')->getData()));
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe est modifié. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('security/reinitialiser.html.twig', [
            'form' => $form,
        ]);
    }
}
