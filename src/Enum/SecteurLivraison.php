<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Secteurs de livraison à La Réunion (article 6 des CGV).
 * Les livraisons sont faites en main propre par la créatrice.
 */
enum SecteurLivraison: string implements TranslatableInterface
{
    case Ouest = 'ouest';
    case Nord = 'nord';
    case Sud = 'sud';
    case Est = 'est';

    /** Délai annoncé dans les CGV. */
    public const DELAI = '24 h à 72 h';

    public function label(): string
    {
        return match ($this) {
            self::Ouest => 'Ouest',
            self::Nord => 'Nord',
            self::Sud => 'Sud',
            self::Est => 'Est',
        };
    }

    /** Frais de livraison en centimes. */
    public function frais(): int
    {
        return match ($this) {
            self::Ouest => 0,
            self::Nord => 500,
            self::Sud, self::Est => 800,
        };
    }

    /**
     * Communes de chaque secteur (découpage des micro-régions de l'île).
     *
     * @return string[]
     */
    public function communes(): array
    {
        return match ($this) {
            self::Ouest => ['La Possession', 'Le Port', 'Saint-Paul', 'Trois-Bassins', 'Saint-Leu'],
            self::Nord => ['Saint-Denis', 'Sainte-Marie', 'Sainte-Suzanne'],
            self::Sud => ['Les Avirons', 'L\'Étang-Salé', 'Saint-Louis', 'Cilaos', 'L\'Entre-Deux', 'Saint-Pierre', 'Le Tampon', 'Petite-Île', 'Saint-Joseph', 'Saint-Philippe'],
            self::Est => ['Saint-André', 'Bras-Panon', 'Saint-Benoît', 'La Plaine-des-Palmistes', 'Sainte-Rose', 'Salazie'],
        };
    }

    public static function pourCommune(?string $commune): ?self
    {
        foreach (self::cases() as $secteur) {
            if (\in_array($commune, $secteur->communes(), true)) {
                return $secteur;
            }
        }

        return null;
    }

    /** @return string[] toutes les communes desservies */
    public static function toutesLesCommunes(): array
    {
        return array_merge(...array_map(fn (self $s) => $s->communes(), self::cases()));
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }
}
