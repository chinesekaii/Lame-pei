<?php

namespace App\Enum;

enum StatutDemande: string
{
    case Nouvelle = 'nouvelle';
    case EnCours = 'en_cours';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle',
            self::EnCours => 'En cours',
            self::Acceptee => 'Acceptée',
            self::Refusee => 'Refusée',
        };
    }
}
