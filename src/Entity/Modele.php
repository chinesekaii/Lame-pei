<?php

namespace App\Entity;

use App\Enum\StatutModele;
use App\Repository\ModeleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un modèle de tongs brodées (ex. Capucine, Bougainvilliers).
 */
#[ORM\Entity(repositoryClass: ModeleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug est déjà utilisé par un autre modèle.')]
class Modele
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Assert\Length(max: 120)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Le slug ne doit contenir que des minuscules, chiffres et tirets.')]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $accroche = null;

    /** Texte poétique affiché sur la fiche produit. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Prix en centimes (2500 = 25,00 €). */
    #[ORM\Column]
    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0.')]
    private ?int $prix = null;

    /** Nom du fichier image (ex. capucine.png). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 20, enumType: StatutModele::class)]
    #[Assert\NotNull]
    private StatutModele $statut = StatutModele::EnProduction;

    /** Ordre d'affichage dans le catalogue (plus petit = en premier). */
    #[ORM\Column]
    private int $ordre = 0;

    /** @var Collection<int, Variante> */
    #[ORM\OneToMany(targetEntity: Variante::class, mappedBy: 'modele', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['pointure' => 'ASC'])]
    #[Assert\Valid]
    private Collection $variantes;

    /** @var Collection<int, CouleurPerle> */
    #[ORM\ManyToMany(targetEntity: CouleurPerle::class, inversedBy: 'modeles')]
    private Collection $couleursPerle;

    /** @var Collection<int, Materiau> */
    #[ORM\ManyToMany(targetEntity: Materiau::class, inversedBy: 'modeles')]
    private Collection $materiaux;

    #[ORM\ManyToOne(inversedBy: 'modeles')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Drop $drop = null;

    /** @var Collection<int, Alerte> */
    #[ORM\OneToMany(targetEntity: Alerte::class, mappedBy: 'modele', orphanRemoval: true)]
    private Collection $alertes;

    public function __construct()
    {
        $this->variantes = new ArrayCollection();
        $this->couleursPerle = new ArrayCollection();
        $this->materiaux = new ArrayCollection();
        $this->alertes = new ArrayCollection();
    }

    /** Génère le slug à partir du nom s'il n'a pas été saisi. */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function genererSlug(): void
    {
        if (empty($this->slug) && $this->nom !== null) {
            $this->slug = (new AsciiSlugger('fr'))->slug($this->nom)->lower()->toString();
        }
    }

    public function __toString(): string
    {
        return (string) $this->nom;
    }

    public function isDisponible(): bool
    {
        return $this->statut === StatutModele::Disponible;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getAccroche(): ?string
    {
        return $this->accroche;
    }

    public function setAccroche(?string $accroche): static
    {
        $this->accroche = $accroche;

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

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = $prix;

        return $this;
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

    public function getStatut(): StatutModele
    {
        return $this->statut;
    }

    public function setStatut(StatutModele $statut): static
    {
        $this->statut = $statut;

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

    /** @return Collection<int, Variante> */
    public function getVariantes(): Collection
    {
        return $this->variantes;
    }

    public function addVariante(Variante $variante): static
    {
        if (!$this->variantes->contains($variante)) {
            $this->variantes->add($variante);
            $variante->setModele($this);
        }

        return $this;
    }

    public function removeVariante(Variante $variante): static
    {
        if ($this->variantes->removeElement($variante) && $variante->getModele() === $this) {
            $variante->setModele(null);
        }

        return $this;
    }

    /** @return Collection<int, CouleurPerle> */
    public function getCouleursPerle(): Collection
    {
        return $this->couleursPerle;
    }

    public function addCouleursPerle(CouleurPerle $couleurPerle): static
    {
        if (!$this->couleursPerle->contains($couleurPerle)) {
            $this->couleursPerle->add($couleurPerle);
        }

        return $this;
    }

    public function removeCouleursPerle(CouleurPerle $couleurPerle): static
    {
        $this->couleursPerle->removeElement($couleurPerle);

        return $this;
    }

    /** @return Collection<int, Materiau> */
    public function getMateriaux(): Collection
    {
        return $this->materiaux;
    }

    public function addMateriau(Materiau $materiau): static
    {
        if (!$this->materiaux->contains($materiau)) {
            $this->materiaux->add($materiau);
        }

        return $this;
    }

    public function removeMateriau(Materiau $materiau): static
    {
        $this->materiaux->removeElement($materiau);

        return $this;
    }

    public function getDrop(): ?Drop
    {
        return $this->drop;
    }

    public function setDrop(?Drop $drop): static
    {
        $this->drop = $drop;

        return $this;
    }

    /** @return Collection<int, Alerte> */
    public function getAlertes(): Collection
    {
        return $this->alertes;
    }

    public function addAlerte(Alerte $alerte): static
    {
        if (!$this->alertes->contains($alerte)) {
            $this->alertes->add($alerte);
            $alerte->setModele($this);
        }

        return $this;
    }

    public function removeAlerte(Alerte $alerte): static
    {
        if ($this->alertes->removeElement($alerte) && $alerte->getModele() === $this) {
            $alerte->setModele(null);
        }

        return $this;
    }
}
