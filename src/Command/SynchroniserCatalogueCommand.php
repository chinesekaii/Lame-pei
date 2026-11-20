<?php

namespace App\Command;

use App\Catalogue\CatalogueSynchroniseur;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Met la base à jour avec le catalogue de src/Catalogue/CatalogueReference.php,
 * sans toucher aux clients, commandes, demandes et alertes.
 *
 *   php bin/console app:catalogue:synchroniser           (catalogue, sans toucher au stock existant)
 *   php bin/console app:catalogue:synchroniser --stock   (impose aussi le stock de référence,
 *                                                         moins les paires réservées par des commandes en attente)
 */
#[AsCommand(name: 'app:catalogue:synchroniser', description: 'Met à jour collections, modèles et stock depuis CatalogueReference')]
final class SynchroniserCatalogueCommand
{
    public function __construct(private readonly CatalogueSynchroniseur $synchroniseur)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Impose le stock de CatalogueReference (moins les paires réservées par des commandes en attente)')]
        bool $stock = false,
    ): int {
        $io->title('Synchronisation du catalogue L\'ÂME PEÏ');
        if (!$stock) {
            $io->note('Le stock existant n\'est pas modifié. Ajoutez --stock pour imposer le stock de référence.');
        }
        $io->listing($this->synchroniseur->synchroniser($stock));
        $io->success('Catalogue à jour.');

        return Command::SUCCESS;
    }
}
