<?php

namespace App\Entity;

use App\Repository\CouleurPerleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une couleur de perle, affichée dans la palette de la fiche produit.
 */
#[ORM\Entity(repositoryClass: CouleurPerleRepository::class)]
class CouleurPerle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank(message: 'Le nom de la couleur est obligatoire.')]
    #[Assert\Length(max: 60)]
    private ?string $nom = null;

    #[ORM\Column(length: 7)]
    #[Assert\NotBlank]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'Le code doit être au format #RRGGBB.')]
    private ?string $codeHex = null;

    /** @var Collection<int, Modele> */
    #[ORM\ManyToMany(targetEntity: Modele::class, mappedBy: 'couleursPerle')]
    private Collection $modeles;

    public function __construct()
    {
        $this->modeles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->nom;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCodeHex(): ?string
    {
        return $this->codeHex;
    }

    public function setCodeHex(string $codeHex): static
    {
        $this->codeHex = $codeHex;

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
            $modele->addCouleursPerle($this);
        }

        return $this;
    }

    public function removeModele(Modele $modele): static
    {
        if ($this->modeles->removeElement($modele)) {
            $modele->removeCouleursPerle($this);
        }

        return $this;
    }
}
