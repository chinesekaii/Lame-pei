<?php

namespace App\DataFixtures;

use App\Catalogue\RetoursClients;
use App\Entity\Avis;
use App\Entity\Modele;
use App\Entity\PhotoClient;
use App\Enum\SourceAvis;
use App\Enum\StatutAvis;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Base de démonstration : les retours clients réels (RetoursClients), publiés.
 * Sur la vraie base, ils sont importés en attente par « app:avis:importer ».
 */
class AvisFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [CatalogueFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (RetoursClients::RETOURS as $retour) {
            $manager->persist(
                (new Avis())
                    ->setAuteur($retour['auteur'])
                    ->setTexte($retour['texte'])
                    ->setSource(SourceAvis::from($retour['source']))
                    ->setModele($retour['modele'] ? $this->getReference('modele-'.$retour['modele'], Modele::class) : null)
                    ->setStatut(StatutAvis::Publie)
            );
        }

        foreach (RetoursClients::PHOTOS as $i => $photo) {
            $manager->persist(
                (new PhotoClient())
                    ->setImage($photo['image'])
                    ->setLegende($photo['legende'])
                    ->setOrdre(($i + 1) * 10)
                    ->setModele($photo['modele'] ? $this->getReference('modele-'.$photo['modele'], Modele::class) : null)
            );
        }

        $manager->flush();
    }
}
