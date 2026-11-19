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

    /**
     * Modèle visible sur le site (non archivé), avec ses variantes,
     * sa palette et ses matériaux chargés en une seule requête.
     */
    public function findOneVisibleBySlug(string $slug): ?Modele
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.variantes', 'v')->addSelect('v')
            ->leftJoin('m.couleursPerle', 'c')->addSelect('c')
            ->leftJoin('m.materiaux', 'mat')->addSelect('mat')
            ->andWhere('m.slug = :slug')
            ->andWhere('m.statut != :archive')
            ->setParameter('slug', $slug)
            ->setParameter('archive', StatutModele::Archive)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Les autres modèles visibles, pour le bloc « Autres modèles » de la fiche.
     *
     * @return Modele[]
     */
    public function findAutres(Modele $modele): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m != :modele')
            ->andWhere('m.statut != :archive')
            ->setParameter('modele', $modele)
            ->setParameter('archive', StatutModele::Archive)
            ->orderBy('m.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
