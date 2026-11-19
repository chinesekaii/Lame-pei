<?php

namespace App\Entity;

use App\Enum\StatutDemande;
use App\Repository\DemandeSurMesureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Demande de paire sur-mesure (mariage, naissance, cadeau…).
 */
#[ORM\Entity(repositoryClass: DemandeSurMesureRepository::class)]
class DemandeSurMesure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Merci d\'indiquer votre nom.')]
    #[Assert\Length(max: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Merci d\'indiquer votre adresse e-mail.')]
    #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    /** Coloris souhaité (tongs et/ou perles). */
    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Merci de préciser le coloris souhaité.')]
    #[Assert\Length(max: 150)]
    private ?string $coloris = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotNull(message: 'Merci de choisir une taille.')]
    #[Assert\Range(
        notInRangeMessage: 'La taille doit être comprise entre {{ min }} et {{ max }}.',
        min: Variante::POINTURE_MIN,
        max: Variante::POINTURE_MAX,
    )]
    private ?int $taille = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Racontez-nous votre projet.')]
    #[Assert\Length(min: 10, max: 2000, minMessage: 'Votre message doit faire au moins {{ limit }} caractères.')]
    private ?string $message = null;

    #[ORM\Column(length: 20, enumType: StatutDemande::class)]
    private StatutDemande $statut = StatutDemande::Nouvelle;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** Modèle dont la demande s'inspire (facultatif). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Modele $modele = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getColoris(): ?string
    {
        return $this->coloris;
    }

    public function setColoris(string $coloris): static
    {
        $this->coloris = $coloris;

        return $this;
    }

    public function getTaille(): ?int
    {
        return $this->taille;
    }

    public function setTaille(?int $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatut(): StatutDemande
    {
        return $this->statut;
    }

    public function setStatut(StatutDemande $statut): static
    {
        $this->statut = $statut;

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
