<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Filtres Twig propres au site.
 */
class AppExtension
{
    /**
     * Affiche un prix stocké en centimes : 4500 → « 45 € », 4550 → « 45,50 € ».
     */
    #[AsTwigFilter('prix')]
    public function prix(?int $centimes): string
    {
        $centimes ??= 0;
        $decimales = $centimes % 100 === 0 ? 0 : 2;

        return number_format($centimes / 100, $decimales, ',', "\u{202F}").' €';
    }
}
