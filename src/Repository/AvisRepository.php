<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Modele;
use App\Entity\User;
use App\Enum\StatutAvis;
use App\Enum\StatutCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Avis publiés, les plus récents d'abord (optionnellement pour un seul modèle).
     *
     * @return Avis[]
     */
    public function findPublies(?Modele $modele = null, ?int $limite = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.modele', 'm')->addSelect('m')
            ->andWhere('a.statut = :publie')
            ->setParameter('publie', StatutAvis::Publie)
            ->orderBy('a.createdAt', 'DESC');

        if ($modele !== null) {
            $qb->andWhere('a.modele = :modele')->setParameter('modele', $modele);
        }

        return $qb->setMaxResults($limite)->getQuery()->getResult();
    }

    /**
     * Note moyenne et nombre d'avis notés (les témoignages sans note ne comptent pas).
     *
     * @return array{moyenne: float|null, nombre: int}
     */
    public function statistiques(?Modele $modele = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('AVG(a.note) AS moyenne', 'COUNT(a.id) AS nombre')
            ->andWhere('a.statut = :publie')
            ->andWhere('a.note IS NOT NULL')
            ->setParameter('publie', StatutAvis::Publie);

        if ($modele !== null) {
            $qb->andWhere('a.modele = :modele')->setParameter('modele', $modele);
        }

        $resultat = $qb->getQuery()->getSingleResult();

        return [
            'moyenne' => $resultat['moyenne'] !== null ? round((float) $resultat['moyenne'], 1) : null,
            'nombre' => (int) $resultat['nombre'],
        ];
    }

    /**
     * Le client a-t-il reçu ce modèle (commande livrée) ? Condition pour laisser un avis vérifié.
     */
    public function aRecuLeModele(User $user, Modele $modele): bool
    {
        return (bool) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(\App\Entity\LigneCommande::class, 'l')
            ->join('l.commande', 'c')
            ->join('l.variante', 'v')
            ->andWhere('c.user = :user')
            ->andWhere('c.statut = :livree')
            ->andWhere('v.modele = :modele')
            ->setParameter('user', $user)
            ->setParameter('livree', StatutCommande::Livree)
            ->setParameter('modele', $modele)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
