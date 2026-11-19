<?php

namespace App\DataFixtures;

use App\Entity\Alerte;
use App\Entity\Commande;
use App\Entity\DemandeSurMesure;
use App\Entity\LigneCommande;
use App\Entity\Modele;
use App\Entity\User;
use App\Entity\Variante;
use App\Enum\SecteurLivraison;
use App\Enum\StatutCommande;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Quelques données de démonstration pour l'admin et l'espace client :
 * une commande, des alertes de sortie et une demande sur-mesure.
 */
class DemoFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [CatalogueFixtures::class, UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $client = $this->getReference(UserFixtures::CLIENT, User::class);

        // Commande payée : une paire Capucine en 38
        $commande = (new Commande())
            ->setUser($client)
            ->setStatut(StatutCommande::Payee)
            ->setCreatedAt(new \DateTimeImmutable('-3 days'))
            ->setCgvAccepteesLe(new \DateTimeImmutable('-3 days'))
            ->setAdresseLivraison("Maëva Payet\n12 rue des Flamboyants\n97400 Saint-Denis\nLa Réunion");
        $commande->addLigne(
            (new LigneCommande())
                ->setVariante($this->getReference('variante-capucine.png-38', Variante::class))
                ->setQuantite(1)
        );
        $commande->setSecteurLivraison(SecteurLivraison::Nord); // Saint-Denis : 5 €
        $manager->persist($commande);

        // Alertes « Me prévenir de sa sortie »
        foreach ([
            ['client@lamepei.re', 'lagon.png'],
            ['client@lamepei.re', 'coral-kiss.png'],
            ['lea.hoarau@example.com', 'lagon.png'],
            ['nathan.grondin@example.com', 'lavender-shell.png'],
        ] as [$email, $image]) {
            $manager->persist(
                (new Alerte())
                    ->setEmail($email)
                    ->setModele($this->getReference('modele-'.$image, Modele::class))
            );
        }

        // Demande sur-mesure
        $manager->persist(
            (new DemandeSurMesure())
                ->setNom('Léa Hoarau')
                ->setEmail('lea.hoarau@example.com')
                ->setColoris('Blanc nacré et doré')
                ->setTaille(38)
                ->setMessage("Pour mon mariage en juin : des tongs blanches avec perles nacrées et quelques perles dorées, si possible avec un petit hibiscus.")
                ->setModele($this->getReference('modele-fleur-de-sable.png', Modele::class))
        );

        $manager->flush();
    }
}
