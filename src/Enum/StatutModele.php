<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TranslatableInterface : les formulaires et EasyAdmin affichent label() au lieu du nom technique.
 */
enum StatutModele: string implements TranslatableInterface
{
    case Disponible = 'disponible';
    case EnProduction = 'en_production';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::EnProduction => 'En production',
            self::Archive => 'Archivé',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }
}
