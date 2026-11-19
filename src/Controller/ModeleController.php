<?php

namespace App\Controller;

use App\Entity\Variante;
use App\Repository\ModeleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModeleController extends AbstractController
{
    /** Pointure présélectionnée sur la fiche (celle de la maquette). */
    private const POINTURE_PAR_DEFAUT = 39;

    #[Route('/modele/{slug}', name: 'app_modele_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(string $slug, ModeleRepository $modeleRepository): Response
    {
        $modele = $modeleRepository->findOneVisibleBySlug($slug);

        if ($modele === null) {
            throw $this->createNotFoundException('Ce modèle n\'existe pas.');
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
        ]);
    }
}
