<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * D'où vient l'avis : affiché à côté de l'avis, par transparence.
 */
enum SourceAvis: string implements TranslatableInterface
{
    case Site = 'site';
    case Instagram = 'instagram';
    case Message = 'message';
    case Marche = 'marche';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Avis laissé sur le site',
            self::Instagram => 'Retour reçu sur Instagram',
            self::Message => 'Message reçu par la marque',
            self::Marche => 'Retour recueilli sur un marché',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }
}
