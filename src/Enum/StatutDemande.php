<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TranslatableInterface : les formulaires et EasyAdmin affichent label() au lieu du nom technique.
 */
enum StatutDemande: string implements TranslatableInterface
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

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }
}
