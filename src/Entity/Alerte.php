<?php

namespace App\Entity;

use App\Repository\AlerteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Inscription « Me prévenir de sa sortie » sur un modèle en production.
 */
#[ORM\Entity(repositoryClass: AlerteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_alerte_email_modele', columns: ['email', 'modele_id'])]
#[UniqueEntity(fields: ['email', 'modele'], message: 'Vous êtes déjà inscrit(e) pour ce modèle.', errorPath: 'email')]
class Alerte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Merci d\'indiquer votre adresse e-mail.')]
    #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** Passe à true quand l'e-mail de sortie a été envoyé. */
    #[ORM\Column]
    private bool $envoyee = false;

    #[ORM\ManyToOne(inversedBy: 'alertes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Modele $modele = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isEnvoyee(): bool
    {
        return $this->envoyee;
    }

    public function setEnvoyee(bool $envoyee): static
    {
        $this->envoyee = $envoyee;

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
