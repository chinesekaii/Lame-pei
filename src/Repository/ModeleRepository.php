<?php

namespace App\Repository;

use App\Entity\Modele;
use App\Enum\StatutModele;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Modele>
 */
class ModeleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Modele::class);
    }

    /**
     * Modèles d'un statut donné, triés par ordre d'affichage,
     * avec leur palette de perles chargée en une seule requête.
     *
     * @return Modele[]
     */
    public function findByStatut(StatutModele $statut): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.couleursPerle', 'c')
            ->addSelect('c')
            ->andWhere('m.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('m.ordre', 'ASC')
            ->addOrderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
