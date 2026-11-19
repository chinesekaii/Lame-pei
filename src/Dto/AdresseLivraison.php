<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Données du formulaire d'adresse de livraison.
 * Elles sont ensuite mises en forme dans Commande::adresseLivraison.
 */
class AdresseLivraison
{
    public const REUNION = 'reunion';
    public const METROPOLE = 'metropole';

    #[Assert\NotBlank(message: 'Merci d\'indiquer le nom du destinataire.')]
    #[Assert\Length(max: 100)]
    public ?string $nomComplet = null;

    #[Assert\NotBlank(message: 'Merci d\'indiquer l\'adresse.')]
    #[Assert\Length(max: 255)]
    public ?string $adresse = null;

    #[Assert\Length(max: 255)]
    public ?string $complement = null;

    #[Assert\NotBlank(message: 'Merci d\'indiquer le code postal.')]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres.')]
    public ?string $codePostal = null;

    #[Assert\NotBlank(message: 'Merci d\'indiquer la ville.')]
    #[Assert\Length(max: 100)]
    public ?string $ville = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::REUNION, self::METROPOLE], message: 'Nous livrons La Réunion et la France métropolitaine.')]
    public ?string $destination = self::REUNION;

    #[Assert\Regex(pattern: '/^[0-9 +().-]{10,20}$/', message: 'Ce numéro de téléphone n\'est pas valide.')]
    public ?string $telephone = null;

    /**
     * Le code postal doit correspondre à la destination choisie.
     */
    #[Assert\Callback]
    public function validerCodePostal(ExecutionContextInterface $context): void
    {
        if ($this->codePostal === null || !preg_match('/^\d{5}$/', $this->codePostal)) {
            return; // déjà signalé par la contrainte Regex
        }

        $estReunion = str_starts_with($this->codePostal, '974');

        if ($this->destination === self::REUNION && !$estReunion) {
            $context->buildViolation('Un code postal de La Réunion commence par 974.')
                ->atPath('codePostal')
                ->addViolation();
        }

        if ($this->destination === self::METROPOLE && str_starts_with($this->codePostal, '97')) {
            $context->buildViolation('Ce code postal n\'est pas en France métropolitaine.')
                ->atPath('codePostal')
                ->addViolation();
        }
    }

    /** Adresse mise en forme pour l'étiquette d'envoi. */
    public function enTexte(): string
    {
        $lignes = [
            $this->nomComplet,
            $this->adresse,
            $this->complement,
            $this->codePostal.' '.mb_strtoupper((string) $this->ville),
            $this->destination === self::REUNION ? 'La Réunion' : 'France métropolitaine',
            $this->telephone ? 'Tél. '.$this->telephone : null,
        ];

        return implode("\n", array_filter(array_map(fn ($l) => $l !== null ? trim($l) : null, $lignes)));
    }
}
