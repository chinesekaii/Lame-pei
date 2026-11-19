<?php

namespace App\Dto;

use App\Enum\SecteurLivraison;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données du formulaire de livraison (La Réunion uniquement, voir l'article 6 des CGV).
 * Elles sont ensuite mises en forme dans Commande::adresseLivraison.
 */
class AdresseLivraison
{
    #[Assert\NotBlank(message: 'Merci d\'indiquer le nom du destinataire.')]
    #[Assert\Length(max: 100)]
    public ?string $nomComplet = null;

    #[Assert\NotBlank(message: 'Merci d\'indiquer l\'adresse.')]
    #[Assert\Length(max: 255)]
    public ?string $adresse = null;

    #[Assert\Length(max: 255)]
    public ?string $complement = null;

    #[Assert\NotBlank(message: 'Merci d\'indiquer le code postal.')]
    #[Assert\Regex(pattern: '/^974\d{2}$/', message: 'Nous livrons uniquement à La Réunion : le code postal commence par 974.')]
    public ?string $codePostal = null;

    #[Assert\NotBlank(message: 'Merci de choisir votre commune.')]
    #[Assert\Choice(callback: [SecteurLivraison::class, 'toutesLesCommunes'], message: 'Nous livrons uniquement à La Réunion.')]
    public ?string $commune = null;

    /** Obligatoire (article 6 des CGV) : la créatrice appelle pour convenir de la remise. */
    #[Assert\NotBlank(message: 'Merci d\'indiquer un numéro de téléphone pour convenir de la livraison.')]
    #[Assert\Regex(pattern: '/^[0-9 +().-]{10,20}$/', message: 'Ce numéro de téléphone n\'est pas valide.')]
    public ?string $telephone = null;

    /** Case « J'ai lu et j'accepte les CGV » (obligatoire avant de commander). */
    #[Assert\IsTrue(message: 'Merci d\'accepter les conditions générales de vente pour commander.')]
    public bool $accepteCgv = false;

    public function getSecteur(): ?SecteurLivraison
    {
        return SecteurLivraison::pourCommune($this->commune);
    }

    /** Frais de livraison en centimes (0 tant que la commune n'est pas choisie). */
    public function getFraisLivraison(): int
    {
        return $this->getSecteur()?->frais() ?? 0;
    }

    /** Adresse mise en forme pour la livraison. */
    public function enTexte(): string
    {
        $lignes = [
            $this->nomComplet,
            $this->adresse,
            $this->complement,
            $this->codePostal.' '.mb_strtoupper((string) $this->commune),
            'La Réunion',
            $this->telephone ? 'Tél. '.$this->telephone : null,
        ];

        return implode("\n", array_filter(array_map(fn ($l) => $l !== null ? trim($l) : null, $lignes)));
    }
}
