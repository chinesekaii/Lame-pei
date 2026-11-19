<?php

namespace App\Command;

use App\Catalogue\CatalogueSynchroniseur;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Met la base à jour avec le catalogue de src/Catalogue/CatalogueReference.php,
 * sans toucher aux clients, commandes, demandes et alertes.
 *
 *   php bin/console app:catalogue:synchroniser
 */
#[AsCommand(name: 'app:catalogue:synchroniser', description: 'Met à jour collections, modèles et stock depuis CatalogueReference')]
final class SynchroniserCatalogueCommand
{
    public function __construct(private readonly CatalogueSynchroniseur $synchroniseur)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->title('Synchronisation du catalogue L\'ÂME PEÏ');
        $io->listing($this->synchroniseur->synchroniser());
        $io->success('Catalogue à jour.');

        return Command::SUCCESS;
    }
}
