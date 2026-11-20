<?php

namespace App\Catalogue;

use App\Entity\CouleurPerle;
use App\Entity\Drop;
use App\Entity\LigneCommande;
use App\Entity\Materiau;
use App\Entity\Modele;
use App\Entity\Variante;
use App\Enum\StatutCommande;
use App\Enum\StatutModele;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applique CatalogueReference à la base : crée ou met à jour les collections,
 * les modèles, leurs palettes, leurs tailles et leur stock, et archive les anciens modèles.
 *
 * Ne supprime ni client, ni commande, ni alerte : on peut le relancer sans risque.
 */
class CatalogueSynchroniseur
{
    /** @var list<string> journal des opérations, affiché par la commande */
    private array $journal = [];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** @var array<int, int> paires réservées par des commandes en attente de paiement, par variante */
    private array $reservations = [];

    /**
     * @param bool $imposerStock false (par défaut) : seul le stock des nouvelles tailles est fixé,
     *                           le stock existant (géré dans l'admin et par les commandes) est conservé.
     *                           true : le stock de référence est imposé, moins les paires réservées
     *                           par les commandes en attente de paiement.
     *
     * @return list<string>
     */
    public function synchroniser(bool $imposerStock = false): array
    {
        $this->journal = [];
        $this->reservations = $imposerStock ? $this->reservationsEnCours() : [];

        $materiaux = $this->materiaux();
        $modeles = $this->modeles($materiaux, $imposerStock);
        $this->collections($modeles);
        $this->archiver();

        $this->em->flush();
        $this->supprimerCollectionObsolete();
        $this->em->flush();

        return $this->journal;
    }

    /** @return Materiau[] */
    private function materiaux(): array
    {
        $repo = $this->em->getRepository(Materiau::class);
        $materiaux = [];
        foreach (CatalogueReference::MATERIAUX as $libelle) {
            $materiau = $repo->findOneBy(['libelle' => $libelle]);
            if ($materiau === null) {
                $materiau = (new Materiau())->setLibelle($libelle);
                $this->em->persist($materiau);
                $this->journal[] = "Matériau créé : $libelle";
            }
            $materiaux[] = $materiau;
        }

        return $materiaux;
    }

    /**
     * @param Materiau[] $materiaux
     *
     * @return array<string, Modele> modèles indexés par slug
     */
    private function modeles(array $materiaux, bool $imposerStock): array
    {
        $repo = $this->em->getRepository(Modele::class);
        $modeles = [];
        $ordre = 0;

        foreach (CatalogueReference::MODELES as $slug => $data) {
            $modele = $repo->findOneBy(['slug' => $slug]);
            $nouveau = $modele === null;
            if ($nouveau) {
                $modele = (new Modele())->setSlug($slug);
                $this->em->persist($modele);
            }

            $modele
                ->setNom($data['nom'])
                ->setImage($data['image'])
                ->setGalerie($data['galerie'])
                ->setCouleur($data['couleur'])
                ->setAccroche($data['accroche'])
                ->setDescription($data['accroche'])
                ->setPrix(CatalogueReference::PRIX)
                ->setStatut(StatutModele::Disponible)
                ->setOrdre($ordre += 10)
                ->setCouleursPerle($this->palette($data['perles']))
                ->setMateriaux($materiaux);

            // Tailles Havaianas : on crée celles qui manquent (stock de référence),
            // et on ne touche au stock existant que si on le demande explicitement
            $modele->completerVariantes();
            $stockTotal = 0;
            foreach ($modele->getVariantes() as $variante) {
                $reference = $data['stock'][$variante->getTaille()] ?? 0;
                if ($variante->getId() === null) {
                    $variante->setStock($reference);
                } elseif ($imposerStock) {
                    $reserve = $this->reservations[$variante->getId()] ?? 0;
                    $variante->setStock(max(0, $reference - $reserve));
                    if ($reserve > 0) {
                        $this->journal[] = sprintf('%s %s : %d paire(s) déduite(s), réservée(s) par une commande en attente', $data['nom'], $variante->getTaille(), $reserve);
                    }
                }
                $stockTotal += $variante->getStock();
            }

            $this->journal[] = sprintf('%s %s : %d paire(s) en stock', $nouveau ? 'Modèle créé' : 'Modèle mis à jour', $data['nom'], $stockTotal);
            $modeles[$slug] = $modele;
        }

        return $modeles;
    }

    /** @return array<int, int> quantité réservée par identifiant de variante */
    private function reservationsEnCours(): array
    {
        $lignes = $this->em->createQueryBuilder()
            ->select('IDENTITY(l.variante) AS variante', 'SUM(l.quantite) AS quantite')
            ->from(LigneCommande::class, 'l')
            ->join('l.commande', 'c')
            ->where('c.statut = :attente')
            ->setParameter('attente', StatutCommande::EnAttente)
            ->groupBy('l.variante')
            ->getQuery()
            ->getArrayResult();

        return array_column(array_map(fn ($l) => [(int) $l['variante'], (int) $l['quantite']], $lignes), 1, 0);
    }

    /**
     * @param list<array{0: string, 1: string}> $perles
     *
     * @return CouleurPerle[]
     */
    private function palette(array $perles): array
    {
        $repo = $this->em->getRepository(CouleurPerle::class);
        $palette = [];
        foreach ($perles as [$nom, $hex]) {
            $couleur = $repo->findOneBy(['nom' => $nom, 'codeHex' => $hex])
                ?? $this->couleurEnAttente($nom, $hex);
            $palette[] = $couleur;
        }

        return $palette;
    }

    /** @var array<string, CouleurPerle> couleurs créées pendant cette synchronisation (pas encore en base) */
    private array $nouvellesCouleurs = [];

    private function couleurEnAttente(string $nom, string $hex): CouleurPerle
    {
        $cle = $nom.$hex;
        if (!isset($this->nouvellesCouleurs[$cle])) {
            $this->nouvellesCouleurs[$cle] = (new CouleurPerle())->setNom($nom)->setCodeHex($hex);
            $this->em->persist($this->nouvellesCouleurs[$cle]);
            $this->journal[] = "Couleur de perle créée : $nom ($hex)";
        }

        return $this->nouvellesCouleurs[$cle];
    }

    /** @param array<string, Modele> $modeles */
    private function collections(array $modeles): void
    {
        $repo = $this->em->getRepository(Drop::class);

        foreach (CatalogueReference::COLLECTIONS as $slug => $data) {
            $collection = $repo->findOneBy(['slug' => $slug]);
            if ($collection === null) {
                $collection = (new Drop())->setSlug($slug);
                $this->em->persist($collection);
                $this->journal[] = 'Collection créée : '.$data['nom'];
            }

            $collection
                ->setNom($data['nom'])
                ->setTheme($data['theme'])
                ->setOrdre($data['ordre']);

            // La description n'est écrite qu'une fois : ensuite, elle se modifie dans l'admin
            if ($collection->getDescription() === null) {
                $collection->setDescription($data['description']);
            }

            foreach ($data['modeles'] as $slugModele) {
                $collection->addModele($modeles[$slugModele]);
            }
        }
    }

    private function archiver(): void
    {
        $repo = $this->em->getRepository(Modele::class);
        foreach (CatalogueReference::MODELES_ARCHIVES as $slug) {
            $modele = $repo->findOneBy(['slug' => $slug]);
            if ($modele !== null && $modele->getStatut() !== StatutModele::Archive) {
                $modele->setStatut(StatutModele::Archive)->setDrop(null);
                $this->journal[] = 'Modèle archivé : '.$modele->getNom();
            }
        }
    }

    private function supprimerCollectionObsolete(): void
    {
        $collection = $this->em->getRepository(Drop::class)->findOneBy(['nom' => CatalogueReference::COLLECTION_OBSOLETE]);
        if ($collection !== null) {
            $this->em->refresh($collection);
            if ($collection->getModeles()->isEmpty()) {
                $this->em->remove($collection);
                $this->journal[] = 'Collection supprimée (vide) : '.CatalogueReference::COLLECTION_OBSOLETE;
            }
        }
    }
}
