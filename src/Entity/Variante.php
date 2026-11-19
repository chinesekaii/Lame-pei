<?php

namespace App\Entity;

use App\Repository\VarianteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une taille d'un modèle, avec son stock.
 * Les tailles sont celles des tongs Havaianas (doubles : 35/36, 37/38…).
 */
#[ORM\Entity(repositoryClass: VarianteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_modele_taille', columns: ['modele_id', 'taille'])]
#[UniqueEntity(fields: ['modele', 'taille'], message: 'Cette taille existe déjà pour ce modèle.')]
class Variante
{
    /** Tailles Havaianas proposées, de la plus petite à la plus grande. */
    public const TAILLES = ['33/34', '35/36', '37/38', '39/40', '41/42'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::TAILLES, message: 'Taille inconnue (tailles possibles : 33/34 à 41/42).')]
    private ?string $taille = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le stock ne peut pas être négatif.')]
    private int $stock = 0;

    #[ORM\ManyToOne(inversedBy: 'variantes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Modele $modele = null;

    public function __toString(): string
    {
        return sprintf('%s – %s', $this->modele?->getNom() ?? '?', $this->taille);
    }

    public function isEnStock(): bool
    {
        return $this->stock > 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(string $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getModele(): ?Modele
    {
        return $this->modele;
    }

    public function setModele(?Modele $modele): static
    {
        $this->modele = $modele;

        return $this;
    }
}
