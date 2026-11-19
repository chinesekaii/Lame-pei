<?php

namespace App\Service;

use App\Entity\Variante;

/**
 * Une ligne du panier en session : une variante et sa quantité.
 */
final readonly class LignePanier
{
    public function __construct(
        public Variante $variante,
        public int $quantite,
    ) {
    }

    public function getModele(): \App\Entity\Modele
    {
        return $this->variante->getModele();
    }

    /** Prix unitaire actuel du modèle, en centimes. */
    public function getPrixUnitaire(): int
    {
        return (int) $this->getModele()->getPrix();
    }

    public function getSousTotal(): int
    {
        return $this->getPrixUnitaire() * $this->quantite;
    }
}
