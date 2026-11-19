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

        // Commande payée : une paire Capucine en 37/38 (la collection Fleur Peï est épuisée depuis)
        $commande = (new Commande())
            ->setUser($client)
            ->setStatut(StatutCommande::Payee)
            ->setCreatedAt(new \DateTimeImmutable('-3 days'))
            ->setCgvAccepteesLe(new \DateTimeImmutable('-3 days'))
            ->setAdresseLivraison("Maëva Payet\n12 rue des Flamboyants\n97400 SAINT-DENIS\nLa Réunion\nTél. 0692 00 00 00");
        $commande->addLigne(
            (new LigneCommande())
                ->setVariante($this->getReference('variante-capucine-37/38', Variante::class))
                ->setQuantite(1)
        );
        $commande->setSecteurLivraison(SecteurLivraison::Nord); // Saint-Denis : 5 €
        $manager->persist($commande);

        // Alertes « Me prévenir de sa sortie » (sur d'anciens modèles, aujourd'hui archivés)
        foreach ([
            ['client@lamepei.re', 'lagon'],
            ['client@lamepei.re', 'coral-kiss'],
            ['lea.hoarau@example.com', 'lagon'],
            ['nathan.grondin@example.com', 'lavender-shell'],
        ] as [$email, $slug]) {
            $manager->persist(
                (new Alerte())
                    ->setEmail($email)
                    ->setModele($this->getReference('modele-'.$slug, Modele::class))
            );
        }

        // Demande sur-mesure
        $manager->persist(
            (new DemandeSurMesure())
                ->setNom('Léa Hoarau')
                ->setEmail('lea.hoarau@example.com')
                ->setColoris('Blanc nacré et doré')
                ->setTaille('37/38')
                ->setMessage("Pour mon mariage en juin : des savates blanches avec perles nacrées et quelques perles dorées, si possible avec un petit hibiscus.")
                ->setModele($this->getReference('modele-soley-glitter', Modele::class))
        );

        $manager->flush();
    }
}
