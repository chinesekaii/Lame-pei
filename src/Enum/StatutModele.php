<?php

namespace App\Enum;

enum StatutModele: string
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
}
