<?php

namespace App\Controller\Admin;

use App\Entity\Alerte;
use App\Entity\Commande;
use App\Entity\DemandeSurMesure;
use App\Entity\Modele;
use App\Enum\StatutCommande;
use App\Enum\StatutDemande;
use App\Enum\StatutModele;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ColorScheme;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Back-office de la boutique, réservé aux administrateurs.
 */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    /** En dessous de ce nombre de paires, un modèle disponible est signalé. */
    private const SEUIL_STOCK_FAIBLE = 3;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function index(): Response
    {
        $commandes = $this->em->getRepository(Commande::class);
        $demandes = $this->em->getRepository(DemandeSurMesure::class);

        // Modèles disponibles dont le stock est faible
        $stockFaible = array_filter(
            $this->em->getRepository(Modele::class)->findBy(['statut' => StatutModele::Disponible], ['ordre' => 'ASC']),
            fn (Modele $m) => $m->getStockTotal() < self::SEUIL_STOCK_FAIBLE,
        );

        // Nombre d'alertes en attente par modèle en production
        $alertesParModele = $this->em->createQueryBuilder()
            ->select('m.nom', 'COUNT(a.id) AS nombre')
            ->from(Alerte::class, 'a')
            ->join('a.modele', 'm')
            ->where('a.envoyee = false')
            ->groupBy('m.id')
            ->orderBy('nombre', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return $this->render('admin/dashboard.html.twig', [
            'aPreparer' => $commandes->count(['statut' => [StatutCommande::Payee, StatutCommande::EnFabrication]]),
            'enAttente' => $commandes->count(['statut' => StatutCommande::EnAttente]),
            'nouvellesDemandes' => $demandes->count(['statut' => StatutDemande::Nouvelle]),
            'dernieresCommandes' => $commandes->findBy([], ['createdAt' => 'DESC'], 5),
            'dernieresDemandes' => $demandes->findBy(['statut' => [StatutDemande::Nouvelle, StatutDemande::EnCours]], ['createdAt' => 'DESC'], 5),
            'stockFaible' => $stockFaible,
            'seuilStock' => self::SEUIL_STOCK_FAIBLE,
            'alertesParModele' => $alertesParModele,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<span style="font-family: \'Cormorant Garamond\', serif; letter-spacing: .12em">L\'ÂME PEÏ</span>')
            ->setFaviconPath('images/logo.png')
            // Thème clair par défaut (chaque admin peut basculer en sombre via le menu utilisateur)
            ->setDefaultColorScheme(ColorScheme::LIGHT)
            ->setTheme(Theme::new()->primaryColor('#A9502E'));
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addHtmlContentToHead('<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&display=swap" rel="stylesheet">');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setTimezone('Indian/Reunion')
            ->setDateFormat('dd/MM/yyyy')
            ->setDateTimeFormat('dd/MM/yyyy HH:mm')
            ->setPaginatorPageSize(25);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');

        yield MenuItem::section('Ventes');
        yield MenuItem::linkTo(CommandeCrudController::class, 'Commandes', 'fa fa-bag-shopping');
        yield MenuItem::linkTo(UserCrudController::class, 'Clients', 'fa fa-users');

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(ModeleCrudController::class, 'Modèles', 'fa fa-shoe-prints');
        yield MenuItem::linkTo(DropCrudController::class, 'Collections', 'fa fa-layer-group');
        yield MenuItem::linkTo(CouleurPerleCrudController::class, 'Couleurs de perles', 'fa fa-palette');
        yield MenuItem::linkTo(MateriauCrudController::class, 'Matériaux', 'fa fa-scissors');

        yield MenuItem::section('Demandes');
        yield MenuItem::linkTo(DemandeSurMesureCrudController::class, 'Sur-mesure', 'fa fa-envelope-open-text');
        yield MenuItem::linkTo(AlerteCrudController::class, 'Alertes de sortie', 'fa fa-bell');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Voir le site', 'fa fa-store', 'app_home');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-right-from-bracket');
    }
}
