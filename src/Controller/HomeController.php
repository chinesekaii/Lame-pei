<?php

namespace App\Controller;

use App\Entity\DemandeSurMesure;
use App\Entity\User;
use App\Enum\StatutModele;
use App\Form\DemandeSurMesureType;
use App\Repository\DropRepository;
use App\Repository\ModeleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(Request $request, ModeleRepository $modeleRepository, DropRepository $dropRepository, EntityManagerInterface $em): Response
    {
        // Formulaire sur-mesure (section #commander)
        $demande = new DemandeSurMesure();

        // Préremplissage : client connecté, et modèle passé en paramètre (?modele=lagon)
        $user = $this->getUser();
        if ($user instanceof User) {
            $demande->setNom(trim($user->getPrenom().' '.$user->getNom()));
            $demande->setEmail($user->getEmail());
        }
        if ($slug = $request->query->get('modele')) {
            $demande->setModele($modeleRepository->findOneVisibleBySlug((string) $slug));
        }

        $form = $this->createForm(DemandeSurMesureType::class, $demande, [
            'action' => $this->generateUrl('app_home').'#commander',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($demande);
            $em->flush();

            $this->addFlash('sur_mesure', 'Merci ! Votre demande est bien partie : nous vous répondons sous 48 h.');

            return $this->redirect($this->generateUrl('app_home').'#commander', Response::HTTP_SEE_OTHER);
        }

        return $this->render('home/index.html.twig', [
            'collections' => $dropRepository->findVisibles(),
            // Modèles en vente qui ne sont rattachés à aucune collection (affichés à part s'il y en a)
            'horsCollection' => array_filter(
                $modeleRepository->findByStatut(StatutModele::Disponible),
                fn ($m) => $m->getDrop() === null,
            ),
            'enProduction' => $modeleRepository->findByStatut(StatutModele::EnProduction),
            'form' => $form,
        ]);
    }
}
