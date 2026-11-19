<?php

namespace App\Controller;

use App\Repository\DropRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page de présentation d'une collection (Fleur Peï, Éclat Peï…) avec ses modèles.
 */
final class CollectionController extends AbstractController
{
    #[Route('/collection/{slug}', name: 'app_collection_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(string $slug, DropRepository $dropRepository): Response
    {
        $collection = $dropRepository->findOneVisibleBySlug($slug);

        if ($collection === null) {
            throw $this->createNotFoundException('Cette collection n\'existe pas.');
        }

        return $this->render('collection/show.html.twig', [
            'collection' => $collection,
            'autresCollections' => array_filter(
                $dropRepository->findVisibles(),
                fn ($c) => $c->getId() !== $collection->getId(),
            ),
        ]);
    }
}
