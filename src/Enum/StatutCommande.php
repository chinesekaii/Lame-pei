<?php

namespace App\Enum;

enum StatutCommande: string
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
}
