<?php

namespace App\Entity;

use App\Enum\SourceAvis;
use App\Enum\StatutAvis;
use App\Repository\AvisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Avis client : laissé sur le site par un client (achat vérifié) ou ajouté par la marque
 * (retour reçu sur Instagram, par message…). Publié seulement après validation.
 */
#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\Index(name: 'idx_avis_statut', columns: ['statut'])]
#[UniqueEntity(fields: ['user', 'modele'], message: 'Vous avez déjà laissé un avis sur ce modèle.', ignoreNull: true)]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nom affiché sous l'avis, ex. « Maëva P. » (jamais le nom complet). */
    #[ORM\Column(length: 60)]
    #[Assert\NotBlank(message: 'Merci d\'indiquer le nom affiché.')]
    #[Assert\Length(max: 60)]
    private ?string $auteur = null;

    /** Note de 1 à 5, ou null pour un témoignage sans note (retour Instagram…). */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}.', min: 1, max: 5)]
    private ?int $note = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Merci d\'écrire quelques mots.')]
    #[Assert\Length(min: 3, max: 1000, minMessage: 'Votre avis doit faire au moins {{ limit }} caractères.')]
    private ?string $texte = null;

    #[ORM\Column(length: 20, enumType: StatutAvis::class)]
    private StatutAvis $statut = StatutAvis::EnAttente;

    #[ORM\Column(length: 20, enumType: SourceAvis::class)]
    private SourceAvis $source = SourceAvis::Site;

    /** Vrai si l'auteur a acheté et reçu le modèle sur le site. */
    #[ORM\Column]
    private bool $achatVerifie = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publieLe = null;

    /** Modèle concerné (facultatif pour un témoignage général). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Modele $modele = null;

    /** Client auteur (avis laissés depuis l'espace client). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s — %s', $this->auteur, mb_strimwidth((string) $this->texte, 0, 40, '…'));
    }

    public function isPublie(): bool
    {
        return $this->statut === StatutAvis::Publie;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(?string $texte): static
    {
        $this->texte = $texte;

        return $this;
    }

    public function getStatut(): StatutAvis
    {
        return $this->statut;
    }

    /** Publier fixe la date de publication (une seule fois). */
    public function setStatut(StatutAvis $statut): static
    {
        $this->statut = $statut;
        if ($statut === StatutAvis::Publie && $this->publieLe === null) {
            $this->publieLe = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getSource(): SourceAvis
    {
        return $this->source;
    }

    public function setSource(SourceAvis $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function isAchatVerifie(): bool
    {
        return $this->achatVerifie;
    }

    public function setAchatVerifie(bool $achatVerifie): static
    {
        $this->achatVerifie = $achatVerifie;

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

    public function getPublieLe(): ?\DateTimeImmutable
    {
        return $this->publieLe;
    }

    public function setPublieLe(?\DateTimeImmutable $publieLe): static
    {
        $this->publieLe = $publieLe;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
