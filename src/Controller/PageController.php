<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages de contenu fixe (CGV, et plus tard mentions légales, confidentialité…).
 */
final class PageController extends AbstractController
{
    #[Route('/cgv', name: 'app_cgv', methods: ['GET'])]
    public function cgv(): Response
    {
        return $this->render('page/cgv.html.twig');
    }
}
