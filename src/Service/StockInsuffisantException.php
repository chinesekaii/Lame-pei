<?php

namespace App\Service;

use App\Entity\Variante;

/**
 * Levée quand une paire du panier n'est plus en stock au moment de commander.
 */
class StockInsuffisantException extends \RuntimeException
{
    public function __construct(public readonly Variante $variante)
    {
        parent::__construct(sprintf(
            'Il ne reste plus assez de paires %s en taille %d.',
            $variante->getModele()?->getNom(),
            $variante->getPointure(),
        ));
    }
}
