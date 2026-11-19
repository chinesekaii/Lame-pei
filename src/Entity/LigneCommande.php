<?php

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une ligne de commande : une variante (modèle + taille) et sa quantité.
 */
#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'La quantité doit être d\'au moins 1.')]
    private int $quantite = 1;

    /** Prix unitaire en centimes, copié au moment de l'achat. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $prixUnitaire = 0;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Commande $commande = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Variante $variante = null;

    public function __toString(): string
    {
        return sprintf('%s × %d — %s €', $this->variante, $this->quantite, number_format($this->getSousTotal() / 100, 2, ',', ' '));
    }

    public function getSousTotal(): int
    {
        return $this->quantite * $this->prixUnitaire;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): int
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(int $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }

    public function getVariante(): ?Variante
    {
        return $this->variante;
    }

    /** Associe la variante et fige le prix du modèle à cet instant. */
    public function setVariante(?Variante $variante): static
    {
        $this->variante = $variante;
        if ($variante !== null && $this->prixUnitaire === 0) {
            $this->prixUnitaire = (int) $variante->getModele()?->getPrix();
        }

        return $this;
    }
}
