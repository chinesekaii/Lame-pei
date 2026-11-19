<?php

namespace App\Controller;

use App\Enum\StatutModele;
use App\Repository\ModeleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ModeleRepository $modeleRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'disponibles' => $modeleRepository->findByStatut(StatutModele::Disponible),
            'enProduction' => $modeleRepository->findByStatut(StatutModele::EnProduction),
        ]);
    }
}
