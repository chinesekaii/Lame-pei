<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Modele;
use App\Repository\AlerteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Prévient par e-mail les personnes inscrites à « Me prévenir de sa sortie ».
 */
class AlertesSortie
{
    public function __construct(
        private readonly AlerteRepository $alerteRepository,
        private readonly Courrier $courrier,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** Nombre de personnes encore à prévenir pour ce modèle. */
    public function nombreEnAttente(Modele $modele): int
    {
        return $this->alerteRepository->count(['modele' => $modele, 'envoyee' => false]);
    }

    /**
     * Envoie l'e-mail à chaque inscrit pas encore prévenu, puis marque l'alerte comme envoyée.
     *
     * @return array{envoyes: int, echecs: int}
     */
    public function prevenir(Modele $modele): array
    {
        if (!$modele->isDisponible()) {
            throw new \LogicException(sprintf('%s n\'est pas encore disponible.', $modele->getNom()));
        }

        $resultat = ['envoyes' => 0, 'echecs' => 0];

        /** @var Alerte $alerte */
        foreach ($this->alerteRepository->findBy(['modele' => $modele, 'envoyee' => false]) as $alerte) {
            if ($this->courrier->sortieModele($alerte)) {
                $alerte->setEnvoyee(true);
                ++$resultat['envoyes'];
            } else {
                ++$resultat['echecs']; // restera « à prévenir » : on pourra réessayer
            }
        }

        $this->em->flush();

        return $resultat;
    }
}
