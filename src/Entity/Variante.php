<?php

namespace App\Entity;

use App\Repository\VarianteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une pointure d'un modèle, avec son stock.
 */
#[ORM\Entity(repositoryClass: VarianteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_modele_pointure', columns: ['modele_id', 'pointure'])]
#[UniqueEntity(fields: ['modele', 'pointure'], message: 'Cette pointure existe déjà pour ce modèle.')]
class Variante
{
    public const POINTURE_MIN = 36;
    public const POINTURE_MAX = 42;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotNull]
    #[Assert\Range(
        notInRangeMessage: 'La pointure doit être comprise entre {{ min }} et {{ max }}.',
        min: self::POINTURE_MIN,
        max: self::POINTURE_MAX,
    )]
    private ?int $pointure = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le stock ne peut pas être négatif.')]
    private int $stock = 0;

    #[ORM\ManyToOne(inversedBy: 'variantes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Modele $modele = null;

    public function __toString(): string
    {
        return sprintf('%s – %d', $this->modele?->getNom() ?? '?', $this->pointure);
    }

    public function isEnStock(): bool
    {
        return $this->stock > 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPointure(): ?int
    {
        return $this->pointure;
    }

    public function setPointure(int $pointure): static
    {
        $this->pointure = $pointure;

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
