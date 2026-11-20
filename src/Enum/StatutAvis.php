<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Un avis n'apparaît sur le site qu'une fois publié par la marque (modération).
 */
enum StatutAvis: string implements TranslatableInterface
{
    case EnAttente = 'en_attente';
    case Publie = 'publie';
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Publie => 'Publié',
            self::Refuse => 'Refusé',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }
}
