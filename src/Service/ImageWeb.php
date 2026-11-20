<?php

namespace App\Service;

/**
 * Prépare une photo envoyée depuis l'admin pour le web :
 *  - la redresse selon l'orientation enregistrée par le téléphone ;
 *  - la réduit (largeur maximale LARGEUR_MAX) ;
 *  - la réenregistre, ce qui supprime ses métadonnées (position GPS, appareil, date…).
 */
class ImageWeb
{
    public const LARGEUR_MAX = 900;

    public function preparer(string $chemin): void
    {
        $infos = @getimagesize($chemin);
        if ($infos === false || !\in_array($infos[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return; // format non géré : on laisse le fichier tel quel
        }

        // Les photos de téléphone sont lourdes une fois décompressées
        $memoire = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        try {
            $image = match ($infos[2]) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($chemin),
                IMAGETYPE_PNG => imagecreatefrompng($chemin),
                IMAGETYPE_WEBP => imagecreatefromwebp($chemin),
            };

            if ($infos[2] === IMAGETYPE_JPEG && \function_exists('exif_read_data')) {
                $orientation = @exif_read_data($chemin)['Orientation'] ?? 1;
                $image = match ((int) $orientation) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => $image,
                };
            }

            if (imagesx($image) > self::LARGEUR_MAX) {
                $image = imagescale($image, self::LARGEUR_MAX);
            }

            match ($infos[2]) {
                IMAGETYPE_JPEG => imagejpeg($image, $chemin, 82),
                IMAGETYPE_PNG => imagepng($image, $chemin, 8),
                IMAGETYPE_WEBP => imagewebp($image, $chemin, 82),
            };
        } finally {
            ini_set('memory_limit', $memoire);
        }
    }
}
