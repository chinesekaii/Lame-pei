<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TranslatableInterface : les formulaires et EasyAdmin affichent label() au lieu du nom technique.
 */
enum StatutCommande: string implements TranslatableInterface
{
    case EnAttente = 'en_attente';
    case Payee = 'payee';
    case EnFabrication = 'en_fabrication';
    case Expediee = 'expediee';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de paiement',
            self::Payee => 'Payée',
            self::EnFabrication => 'En fabrication',
            self::Expediee => 'Expédiée',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->label();
    }
}
