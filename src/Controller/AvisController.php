<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\User;
use App\Enum\SourceAvis;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use App\Repository\ModeleRepository;
use App\Repository\PhotoClientRepository;
use App\Service\Courrier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AvisController extends AbstractController
{
    /** Tous les avis publiés. */
    #[Route('/avis', name: 'app_avis', methods: ['GET'])]
    public function index(AvisRepository $avisRepository, PhotoClientRepository $photoClientRepository): Response
    {
        return $this->render('avis/index.html.twig', [
            'photosClients' => $photoClientRepository->findVisibles(),
            'avis' => $avisRepository->findPublies(),
            'stats' => $avisRepository->statistiques(),
        ]);
    }

    /**
     * Avis d'un client sur un modèle qu'il a reçu (commande livrée) : « achat vérifié ».
     * Il est publié après validation par la marque.
     */
    #[Route('/compte/avis/{slug}', name: 'app_avis_nouveau', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function nouveau(
        string $slug,
        Request $request,
        ModeleRepository $modeleRepository,
        AvisRepository $avisRepository,
        EntityManagerInterface $em,
        Courrier $courrier,
        #[CurrentUser] User $user,
    ): Response {
        $modele = $modeleRepository->findOneBy(['slug' => $slug]);
        if ($modele === null || !$avisRepository->aRecuLeModele($user, $modele)) {
            $this->addFlash('error', 'Vous pourrez donner votre avis sur ce modèle une fois votre paire reçue.');

            return $this->redirectToRoute('app_compte');
        }

        if ($avisRepository->findOneBy(['user' => $user, 'modele' => $modele]) !== null) {
            $this->addFlash('success', sprintf('Merci, vous avez déjà donné votre avis sur %s.', $modele->getNom()));

            return $this->redirectToRoute('app_compte');
        }

        $avis = (new Avis())
            ->setUser($user)
            ->setModele($modele)
            ->setSource(SourceAvis::Site)
            ->setAchatVerifie(true)
            ->setAuteur(trim($user->getPrenom().' '.mb_substr((string) $user->getNom(), 0, 1).'.'));

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($avis);
            $em->flush();

            $courrier->nouvelAvisPourMarque($avis);
            $this->addFlash('success', 'Merci pour votre avis ! Il apparaîtra sur le site après une rapide vérification.');

            return $this->redirectToRoute('app_compte', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('avis/nouveau.html.twig', [
            'form' => $form,
            'modele' => $modele,
        ]);
    }
}
