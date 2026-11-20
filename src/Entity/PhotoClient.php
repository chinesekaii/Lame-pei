<?php

namespace App\Entity;

use App\Repository\PhotoClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Photo envoyée par une cliente (savates portées, sac L'ÂME PEÏ…), affichée dans la galerie.
 * Règle de la marque : aucune photo où l'on voit le visage d'une personne.
 */
#[ORM\Entity(repositoryClass: PhotoClientRepository::class)]
class PhotoClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nom du fichier dans public/uploads/clients. */
    #[ORM\Column(length: 255)]
    private ?string $image = null;

    /** Petite légende facultative, ex. « Soley Glitter » ou « Au marché de Saint-Paul ». */
    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $legende = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Modele $modele = null;

    #[ORM\Column]
    private bool $visible = true;

    /** Ordre d'affichage (plus petit = en premier). */
    #[ORM\Column]
    private int $ordre = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->legende ?? (string) $this->image;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getLegende(): ?string
    {
        return $this->legende;
    }

    public function setLegende(?string $legende): static
    {
        $this->legende = $legende;

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

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
