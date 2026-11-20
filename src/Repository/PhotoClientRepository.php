<?php

namespace App\Repository;

use App\Entity\PhotoClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoClient>
 */
class PhotoClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoClient::class);
    }

    /**
     * Photos affichées sur le site, dans l'ordre choisi.
     *
     * @return PhotoClient[]
     */
    public function findVisibles(?int $limite = null): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.modele', 'm')->addSelect('m')
            ->andWhere('p.visible = true')
            ->orderBy('p.ordre', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
