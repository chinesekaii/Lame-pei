<?php

namespace App\Command;

use App\Catalogue\RetoursClients;
use App\Entity\Avis;
use App\Entity\Modele;
use App\Entity\PhotoClient;
use App\Enum\SourceAvis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Importe les retours clients de RetoursClients (en attente de publication).
 * Un retour déjà présent (même texte) n'est pas recréé : la commande peut être relancée.
 *
 *   php bin/console app:avis:importer
 */
#[AsCommand(name: 'app:avis:importer', description: 'Importe les retours clients reçus avant le site (avis en attente) et la galerie photos')]
final class ImporterAvisCommand
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $repo = $this->em->getRepository(Avis::class);
        $importes = 0;

        foreach (RetoursClients::RETOURS as $retour) {
            if ($repo->findOneBy(['texte' => $retour['texte']]) !== null) {
                $io->text(sprintf('déjà présent : %s', $retour['auteur']));
                continue;
            }

            $avis = (new Avis())
                ->setAuteur($retour['auteur'])
                ->setTexte($retour['texte'])
                ->setSource(SourceAvis::from($retour['source']))
                ->setModele($retour['modele'] ? $this->em->getRepository(Modele::class)->findOneBy(['slug' => $retour['modele']]) : null);
            $this->em->persist($avis);
            ++$importes;
            $io->text(sprintf('importé : %s — « %s »', $retour['auteur'], mb_strimwidth($retour['texte'], 0, 50, '…')));
        }

        // Galerie photos (visibles : sans visage, choisies par la marque)
        $photos = 0;
        foreach (RetoursClients::PHOTOS as $i => $donnees) {
            if ($this->em->getRepository(PhotoClient::class)->findOneBy(['image' => $donnees['image']]) !== null) {
                continue;
            }
            $this->em->persist(
                (new PhotoClient())
                    ->setImage($donnees['image'])
                    ->setLegende($donnees['legende'])
                    ->setOrdre(($i + 1) * 10)
                    ->setModele($donnees['modele'] ? $this->em->getRepository(Modele::class)->findOneBy(['slug' => $donnees['modele']]) : null)
            );
            ++$photos;
        }

        $this->em->flush();
        $io->success(sprintf('%d avis importé(s) en attente de publication, %d photo(s) ajoutée(s) à la galerie.', $importes, $photos));

        return Command::SUCCESS;
    }
}
