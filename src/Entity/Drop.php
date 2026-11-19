<?php

namespace App\Entity;

use App\Repository\DropRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une collection de modèles (ex. Fleur Peï, Éclat Peï), avec sa page de présentation.
 * Table nommée « drops » car DROP est un mot réservé SQL.
 */
#[ORM\Entity(repositoryClass: DropRepository::class)]
#[ORM\Table(name: 'drops')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug est déjà utilisé par une autre collection.')]
class Drop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la collection est obligatoire.')]
    #[Assert\Length(max: 100)]
    private ?string $nom = null;

    /** Adresse de la page : /collection/{slug} */
    #[ORM\Column(length: 120, unique: true, nullable: true)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Le slug ne doit contenir que des minuscules, chiffres et tirets.')]
    private ?string $slug = null;

    /** Ordre d'affichage sur l'accueil (plus petit = en premier). */
    #[ORM\Column(options: ['default' => 0])]
    private int $ordre = 0;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $theme = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Nom du fichier image de la bannière. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateSortie = null;

    /** @var Collection<int, Modele> */
    #[ORM\OneToMany(targetEntity: Modele::class, mappedBy: 'drop')]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $modeles;

    public function __construct()
    {
        $this->modeles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->nom;
    }

    /** Génère le slug à partir du nom s'il n'a pas été saisi (« Fleur Peï » → « fleur-pei »). */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function genererSlug(): void
    {
        if (empty($this->slug) && $this->nom !== null) {
            $this->slug = (new AsciiSlugger('fr'))->slug($this->nom)->lower()->toString();
        }
    }

    /** Nombre de paires en stock dans toute la collection. */
    public function getStockTotal(): int
    {
        return array_sum($this->modeles->map(fn (Modele $m) => $m->getStockTotal())->toArray());
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

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

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBanniere(): ?string
    {
        return $this->banniere;
    }

    public function setBanniere(?string $banniere): static
    {
        $this->banniere = $banniere;

        return $this;
    }

    public function getDateSortie(): ?\DateTimeImmutable
    {
        return $this->dateSortie;
    }

    public function setDateSortie(?\DateTimeImmutable $dateSortie): static
    {
        $this->dateSortie = $dateSortie;

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
            $modele->setDrop($this);
        }

        return $this;
    }

    public function removeModele(Modele $modele): static
    {
        if ($this->modeles->removeElement($modele) && $modele->getDrop() === $this) {
            $modele->setDrop(null);
        }

        return $this;
    }
}
