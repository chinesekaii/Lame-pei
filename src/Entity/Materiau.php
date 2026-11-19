<?php

namespace App\Entity;

use App\Repository\MateriauRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un matériau utilisé (perles nacrées 6 mm, rocailles, fil nylon…).
 */
#[ORM\Entity(repositoryClass: MateriauRepository::class)]
#[UniqueEntity(fields: ['libelle'], message: 'Ce matériau existe déjà.')]
class Materiau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Assert\Length(max: 100)]
    private ?string $libelle = null;

    /** @var Collection<int, Modele> */
    #[ORM\ManyToMany(targetEntity: Modele::class, mappedBy: 'materiaux')]
    private Collection $modeles;

    public function __construct()
    {
        $this->modeles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->libelle;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /** @return Collection<int, Modele> */
    public function getModeles(): Collection
    {
        return $this->modeles;
    }

    public function addModele(Modele $modele): static
    {
        if (!$this->modeles->contains($modele)) {
            $this->modeles->add($modele);
            $modele->addMateriau($this);
        }

        return $this;
    }

    public function removeModele(Modele $modele): static
    {
        if ($this->modeles->removeElement($modele)) {
            $modele->removeMateriau($this);
        }

        return $this;
    }
}
