<?php

namespace App\Catalogue;

/**
 * Catalogue de référence de la marque : collections, modèles, palettes et stock.
 *
 * Source unique utilisée par :
 *  - les fixtures (base de démonstration) ;
 *  - la commande « app:catalogue:synchroniser » (mise à jour de la vraie base sans rien effacer).
 *
 * Pour mettre à jour le catalogue : modifier ce fichier puis lancer la commande.
 * Prix en centimes. Tailles Havaianas : voir Variante::TAILLES.
 */
final class CatalogueReference
{
    public const PRIX = 4500; // 45,00 €

    public const MATERIAUX = [
        'Perles rondes nacrées (6 mm)',
        'Perles rocailles (2 mm)',
        'Perles dorées (2 mm)',
        'Fil nylon ou polyester résistant',
        'Tongs Havaianas',
    ];

    /**
     * Collections affichées sur le site, dans l'ordre.
     * Les textes de présentation sont modifiables ensuite dans l'admin (Collections).
     */
    public const COLLECTIONS = [
        'fleur-pei' => [
            'nom' => 'Fleur Peï',
            'theme' => 'Collection 1',
            'description' => 'Nos premières créations : des fleurs de perles brodées une à une, aux couleurs de la terre cuite et des bougainvilliers.',
            'ordre' => 10,
            'modeles' => ['capucine', 'bougainvilliers'],
        ],
        'eclat-pei' => [
            'nom' => 'Éclat Peï',
            'theme' => 'Collection 2',
            'description' => 'Des perles nacrées, des reflets dorés et des touches de couleur sur des savates douces : rose poudré, crème et paillettes.',
            'ordre' => 20,
            'modeles' => ['zarlor-pei', 'soley-glitter', 'soley-square'],
        ],
    ];

    /**
     * Modèles en vente. Stock par taille : les tailles absentes sont à 0.
     */
    public const MODELES = [
        'capucine' => [
            'nom' => 'Capucine',
            'image' => 'capucine.jpg',
            'galerie' => [],
            'couleur' => '#B4472A',
            'accroche' => 'Terre cuite et perles pêche — la couleur des toits chauffés par le soleil.',
            'perles' => [['Nacré pêche 6 mm', '#EFB39A'], ['Rocailles ivoire 2 mm', '#F6EADF'], ['Perles dorées 2 mm', '#C79A3E'], ['Nacré rosé 6 mm', '#E8A98F']],
            'stock' => [],
        ],
        'bougainvilliers' => [
            'nom' => 'Bougainvilliers',
            'image' => 'bougainvilliers.jpg',
            'galerie' => [],
            'couleur' => '#A02A55',
            'accroche' => 'Fuchsia profond et perles roses — les bougainvilliers en pleine saison.',
            'perles' => [['Fuchsia 6 mm', '#C4276B'], ['Rose nacré 6 mm', '#E58AAE'], ['Rocailles framboise 2 mm', '#A02A55'], ['Perles dorées 2 mm', '#C79A3E']],
            'stock' => [],
        ],
        'zarlor-pei' => [
            'nom' => 'Zarlor Peï',
            'image' => 'zarlor-pei.jpg',
            'galerie' => [],
            'couleur' => '#2C7A5B',
            'accroche' => 'Semelle crème, perles nacrées blanches et touches de fuchsia et de vert émeraude.',
            'perles' => [['Nacré blanc 6 mm', '#F3EEE6'], ['Rocailles blanches 2 mm', '#F7F3EE'], ['Perles fuchsia', '#D61F8C'], ['Perles vert émeraude', '#1E8A5E']],
            'stock' => ['35/36' => 1],
        ],
        'soley-glitter' => [
            'nom' => 'Soley Glitter',
            'image' => 'soley-glitter.jpg',
            'galerie' => ['soley-glitter-roz.jpg'],
            'couleur' => '#B9786E',
            'accroche' => 'Semelle rose poudré pailletée, brides bronze, perles nacrées et perles dorées.',
            'perles' => [['Nacré blanc 6 mm', '#F3EEE6'], ['Rose nacré 6 mm', '#E8B4BE'], ['Rocailles crème 2 mm', '#F2E3D3'], ['Perles dorées 2 mm', '#C79A3E']],
            'stock' => ['35/36' => 1, '37/38' => 1, '39/40' => 0],
        ],
        'soley-square' => [
            'nom' => 'Soley Square',
            'image' => 'soley-square.jpg',
            'galerie' => [],
            'couleur' => '#C07A86',
            'accroche' => 'Semelle carrée rose pâle, perles rose nacré, rocailles blanches et perles dorées.',
            'perles' => [['Rose nacré 6 mm', '#E8B4BE'], ['Rocailles blanches 2 mm', '#F7F3EE'], ['Perles dorées 2 mm', '#C79A3E']],
            'stock' => ['37/38' => 1, '39/40' => 2],
        ],
    ];

    /**
     * Anciens modèles (planche de la maquette) : conservés mais archivés,
     * donc invisibles sur le site. Leurs alertes de sortie restent dans l'admin.
     */
    public const MODELES_ARCHIVES = ['lagon', 'mint-lagoon', 'azure-sea', 'fleur-de-sable', 'butter-bloom', 'coral-kiss', 'lavender-shell'];

    /** Ancienne collection de la maquette, supprimée si elle ne contient plus de modèle visible. */
    public const COLLECTION_OBSOLETE = 'Bientôt à l\'atelier';
}
