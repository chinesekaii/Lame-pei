<?php

namespace App\Command;

use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use App\Service\CommandeManager;
use App\Service\PaiementStripe;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Filet de sécurité si le webhook Stripe n'arrive pas : annule les commandes
 * restées impayées après le délai de réservation et remet les paires en stock.
 *
 *   php bin/console app:commandes:expirer
 *
 * En production, à lancer régulièrement (tâche planifiée toutes les 15 minutes).
 */
#[AsCommand(name: 'app:commandes:expirer', description: 'Annule les commandes impayées après le délai de réservation et libère le stock')]
final class ExpirerCommandesCommand
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly CommandeManager $commandeManager,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        // Délai Stripe + 10 minutes de marge, pour laisser au webhook le temps d'arriver
        $limite = new \DateTimeImmutable(sprintf('-%d minutes', PaiementStripe::DUREE_RESERVATION_MINUTES + 10));

        $commandes = $this->commandeRepository->createQueryBuilder('c')
            ->andWhere('c.statut = :attente')
            ->andWhere('c.createdAt < :limite')
            ->setParameter('attente', StatutCommande::EnAttente)
            ->setParameter('limite', $limite)
            ->getQuery()
            ->getResult();

        $annulees = [];
        foreach ($commandes as $commande) {
            if ($this->commandeManager->annulerCommandeImpayee($commande)) {
                $annulees[] = $commande->getReference();
            }
        }

        $annulees === []
            ? $io->success('Aucune commande impayée à annuler.')
            : $io->success(sprintf('%d commande(s) annulée(s), stock libéré : %s', \count($annulees), implode(', ', $annulees)));

        return Command::SUCCESS;
    }
}
