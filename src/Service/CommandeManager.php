<?php

namespace App\Service;

use App\Dto\AdresseLivraison;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transforme le panier en commande.
 */
class CommandeManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Panier $panier,
    ) {
    }

    /**
     * Crée la commande, réserve le stock et vide le panier.
     *
     * Tout se passe dans une transaction : chaque variante est verrouillée
     * (SELECT … FOR UPDATE) puis son stock relu, pour que deux clients
     * ne puissent pas acheter la même paire unique en même temps.
     *
     * @throws StockInsuffisantException si une paire n'est plus disponible
     */
    public function creerDepuisPanier(User $client, AdresseLivraison $adresse): Commande
    {
        $lignesPanier = $this->panier->getLignes();
        if ($lignesPanier === []) {
            throw new \LogicException('Impossible de commander un panier vide.');
        }

        $commande = $this->em->wrapInTransaction(function () use ($client, $adresse, $lignesPanier): Commande {
            if (!$adresse->accepteCgv) {
                throw new \LogicException('Les CGV doivent être acceptées avant de commander.');
            }

            $commande = (new Commande())
                ->setAdresseLivraison($adresse->enTexte())
                ->setCgvAccepteesLe(new \DateTimeImmutable())
                ->setSecteurLivraison($adresse->getSecteur());
            $client->addCommande($commande);

            foreach ($lignesPanier as $lignePanier) {
                $variante = $lignePanier->variante;
                $this->em->refresh($variante, LockMode::PESSIMISTIC_WRITE);

                if ($variante->getStock() < $lignePanier->quantite) {
                    throw new StockInsuffisantException($variante);
                }

                $variante->setStock($variante->getStock() - $lignePanier->quantite);

                $commande->addLigne(
                    (new LigneCommande())
                        ->setVariante($variante) // copie le prix du modèle à cet instant
                        ->setQuantite($lignePanier->quantite)
                );
            }

            $this->em->persist($commande);

            return $commande;
        });

        $this->panier->vider();

        return $commande;
    }
}
