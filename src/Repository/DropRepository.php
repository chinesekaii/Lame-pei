<?php

namespace App\Repository;

use App\Entity\Drop;
use App\Enum\StatutModele;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Drop>
 */
class DropRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Drop::class);
    }

    /**
     * Collections qui ont au moins un modèle visible, avec ces modèles,
     * leurs tailles et leur palette (une seule requête).
     *
     * @return Drop[]
     */
    public function findVisibles(): array
    {
        return $this->avecModelesVisibles()
            ->orderBy('d.ordre', 'ASC')
            ->addOrderBy('m.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneVisibleBySlug(string $slug): ?Drop
    {
        return $this->avecModelesVisibles()
            ->andWhere('d.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('m.ordre', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Jointure filtrée : seuls les modèles non archivés sont chargés dans la collection.
     */
    private function avecModelesVisibles(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->join('d.modeles', 'm', 'WITH', 'm.statut != :archive')
            ->addSelect('m')
            ->leftJoin('m.variantes', 'v')->addSelect('v')
            ->leftJoin('m.couleursPerle', 'c')->addSelect('c')
            ->setParameter('archive', StatutModele::Archive);
    }
}
