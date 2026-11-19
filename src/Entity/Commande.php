<?php

namespace App\Entity;

use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une commande client.
 */
#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Référence lisible, ex. LP-20261119-4F7A2C. */
    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 20, enumType: StatutCommande::class)]
    private StatutCommande $statut = StatutCommande::EnAttente;

    /** Total en centimes. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $total = 0;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'L\'adresse de livraison est obligatoire.')]
    private ?string $adresseLivraison = null;

    /** Date à laquelle le client a accepté les CGV (preuve en cas de litige). */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cgvAccepteesLe = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var Collection<int, LigneCommande> */
    #[ORM\OneToMany(targetEntity: LigneCommande::class, mappedBy: 'commande', cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Count(min: 1, minMessage: 'La commande doit contenir au moins un article.')]
    #[Assert\Valid]
    private Collection $lignes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->reference = 'LP-'.$this->createdAt->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        $this->lignes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->reference;
    }

    /** Recalcule le total à partir des lignes. */
    public function recalculerTotal(): static
    {
        $this->total = 0;
        foreach ($this->lignes as $ligne) {
            $this->total += $ligne->getSousTotal();
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatut(): StatutCommande
    {
        return $this->statut;
    }

    public function setStatut(StatutCommande $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(string $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }

    public function getCgvAccepteesLe(): ?\DateTimeImmutable
    {
        return $this->cgvAccepteesLe;
    }

    public function setCgvAccepteesLe(?\DateTimeImmutable $cgvAccepteesLe): static
    {
        $this->cgvAccepteesLe = $cgvAccepteesLe;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /** @return Collection<int, LigneCommande> */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneCommande $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }

        return $this->recalculerTotal();
    }

    public function removeLigne(LigneCommande $ligne): static
    {
        if ($this->lignes->removeElement($ligne) && $ligne->getCommande() === $this) {
            $ligne->setCommande(null);
        }

        return $this->recalculerTotal();
    }
}
