<?php

namespace App\Repository;

use App\Entity\Variante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Variante>
 */
class VarianteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Variante::class);
    }

    /**
     * Variantes demandées avec leur modèle, triées par modèle puis pointure.
     *
     * @param int[] $ids
     *
     * @return Variante[]
     */
    public function findAvecModele(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('v')
            ->join('v.modele', 'm')
            ->addSelect('m')
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('m.ordre', 'ASC')
            ->addOrderBy('v.pointure', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
