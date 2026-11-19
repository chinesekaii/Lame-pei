<?php

namespace App\Controller;

use App\Entity\Alerte;
use App\Entity\User;
use App\Entity\Variante;
use App\Form\AlerteType;
use App\Repository\ModeleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModeleController extends AbstractController
{
    /** Pointure présélectionnée sur la fiche (celle de la maquette). */
    private const POINTURE_PAR_DEFAUT = 39;

    #[Route('/modele/{slug}', name: 'app_modele_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET', 'POST'])]
    public function show(string $slug, Request $request, ModeleRepository $modeleRepository, EntityManagerInterface $em): Response
    {
        $modele = $modeleRepository->findOneVisibleBySlug($slug);

        if ($modele === null) {
            throw $this->createNotFoundException('Ce modèle n\'existe pas.');
        }

        // Formulaire « Me prévenir de sa sortie », uniquement pour les modèles en production
        $formAlerte = null;
        if (!$modele->isDisponible()) {
            $alerte = (new Alerte())->setModele($modele); // fixé avant la validation (unicité email + modèle)
            $user = $this->getUser();
            if ($user instanceof User) {
                $alerte->setEmail($user->getEmail());
            }

            $formAlerte = $this->createForm(AlerteType::class, $alerte, [
                'action' => $this->generateUrl('app_modele_show', ['slug' => $slug]).'#alerte',
            ]);
            $formAlerte->handleRequest($request);

            if ($formAlerte->isSubmitted() && $formAlerte->isValid()) {
                $em->persist($alerte);
                $em->flush();

                $this->addFlash('alerte', sprintf('C\'est noté ! Nous vous écrirons dès que %s sortira de l\'atelier.', $modele->getNom()));

                return $this->redirect($this->generateUrl('app_modele_show', ['slug' => $slug]).'#alerte', Response::HTTP_SEE_OTHER);
            }
        }

        // Pointure cochée par défaut : la 39 si elle est en stock, sinon la première disponible
        $pointureSelectionnee = null;
        if ($modele->isDisponible()) {
            $enStock = $modele->getVariantes()->filter(fn (Variante $v) => $v->isEnStock());
            foreach ($enStock as $variante) {
                $pointureSelectionnee ??= $variante->getPointure();
                if ($variante->getPointure() === self::POINTURE_PAR_DEFAUT) {
                    $pointureSelectionnee = self::POINTURE_PAR_DEFAUT;
                    break;
                }
            }
        }

        return $this->render('modele/show.html.twig', [
            'modele' => $modele,
            'pointureSelectionnee' => $pointureSelectionnee,
            'autres' => $modeleRepository->findAutres($modele),
            'formAlerte' => $formAlerte,
        ]);
    }
}
