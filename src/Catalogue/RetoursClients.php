<?php

namespace App\Catalogue;

/**
 * Retours clients reçus avant le site (stories Instagram, messages privés),
 * retranscrits depuis les captures de docs/avis/.
 *
 * Importés EN ATTENTE par « php bin/console app:avis:importer » : la marque vérifie
 * le nom affiché et l'accord de la personne, puis publie depuis l'admin.
 * Pas de note : ces personnes n'en ont pas donné (on n'invente pas d'étoiles).
 */
final class RetoursClients
{
    public const RETOURS = [
        [
            'auteur' => 'Shania D.',
            'texte' => 'Le meilleur achat de ce mois-ci 🤍',
            'source' => 'instagram',
            'modele' => null, // perles nacrées sur semelle brune : modèle hors catalogue actuel
            'capture' => 'IMG_0431.jpeg',
        ],
        [
            'auteur' => 'Emmanuelle C.',
            'texte' => 'Merci, c\'est juste magnifique 😍😍 Je vous recommande, elle est très agréable en prime.',
            'source' => 'instagram',
            'modele' => null, // Capucine et Bougainvilliers sur la photo
            'capture' => 'IMG_0432.jpeg',
        ],
        [
            'auteur' => 'Méline',
            'texte' => 'Magnifique, mes Havaianas faites main, la déco par L\'ÂME PEÏ 😍 Merci beaucoup 💗',
            'source' => 'instagram',
            'modele' => 'soley-glitter',
            'capture' => 'IMG_0435.jpeg',
        ],
        [
            'auteur' => 'Une cliente',
            'texte' => 'Coucou ma belle, colis bien reçu… J\'adore !',
            'source' => 'message',
            'modele' => null,
            'capture' => 'IMG_0433.jpeg',
        ],
    ];
}
