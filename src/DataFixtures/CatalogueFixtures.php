<?php

namespace App\DataFixtures;

use App\Entity\CouleurPerle;
use App\Entity\Drop;
use App\Entity\Materiau;
use App\Entity\Modele;
use App\Entity\Variante;
use App\Enum\StatutModele;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Catalogue repris de la maquette (docs/maquette/maquette.html) :
 * 9 modèles, leurs palettes de perles, les matériaux et le drop « Bientôt à l'atelier ».
 */
class CatalogueFixtures extends Fixture
{
    public const PRIX = 4500; // 45,00 € (prix de la maquette)

    private const MATERIAUX = [
        'Perles rondes nacrées (6 mm)',
        'Perles rocailles (2 mm)',
        'Perles dorées (2 mm)',
        'Fil nylon ou polyester résistant',
        'Tongs Havaianas',
    ];

    /** Stock des paires déjà brodées, par pointure (pièces uniques : peu d'exemplaires). */
    private const STOCK_DISPONIBLE = [36 => 1, 37 => 2, 38 => 2, 39 => 3, 40 => 2, 41 => 1, 42 => 1];

    private const MODELES = [
        [
            'nom' => 'Capucine', 'image' => 'capucine.png', 'disponible' => true,
            'poeme' => 'Terre cuite et perles pêche — la couleur des toits chauffés par le soleil.',
            'perles' => [['Nacré pêche 6 mm', '#EFB39A'], ['Rocailles ivoire 2 mm', '#F6EADF'], ['Perles dorées 2 mm', '#C79A3E'], ['Nacré rosé 6 mm', '#E8A98F']],
        ],
        [
            'nom' => 'Bougainvilliers', 'image' => 'bougainvilliers.png', 'disponible' => true,
            'poeme' => 'Fuchsia profond et perles roses — les bougainvilliers en pleine saison.',
            'perles' => [['Fuchsia 6 mm', '#C4276B'], ['Rose nacré 6 mm', '#E58AAE'], ['Rocailles framboise 2 mm', '#A02A55'], ['Perles dorées 2 mm', '#C79A3E']],
        ],
        [
            'nom' => 'Lagon', 'image' => 'lagon.png', 'disponible' => false,
            'poeme' => 'Le premier reflet du soleil sur une eau cristalline.',
            'perles' => [['Bleu lagon 6 mm', '#57BFC9'], ['Bleu ciel 2 mm', '#8AD5DC'], ['Nacré blanc 2 mm', '#F4EFE6'], ['Doré 2 mm', '#D9AE55'], ['Turquoise 2 mm', '#4FB5A8']],
        ],
        [
            'nom' => 'Mint Lagoon', 'image' => 'mint-lagoon.png', 'disponible' => false,
            'poeme' => "Le souffle des jardins tropicaux qui rejoint l'océan.",
            'perles' => [['Mint 6 mm', '#7FBE8E'], ["Vert d'eau 2 mm", '#AFD6A6'], ['Nacré blanc 2 mm', '#F4EFE6'], ['Doré 2 mm', '#D9AE55'], ['Vert sauge 2 mm', '#6E8F5B']],
        ],
        [
            'nom' => 'Azure Sea', 'image' => 'azure-sea.png', 'disponible' => false,
            'poeme' => "L'océan, immense et profond, où le bleu semble infini.",
            'perles' => [['Bleu azur 6 mm', '#2F5FA8'], ['Bleu marine 2 mm', '#1E3F73'], ['Nacré blanc 2 mm', '#EFE9DD'], ['Argenté 2 mm', '#B9BFC6'], ['Bleu roi 2 mm', '#2B6BC4']],
        ],
        [
            'nom' => 'Fleur de sable', 'image' => 'fleur-de-sable.png', 'disponible' => false,
            'poeme' => 'Le sable chaud illuminé par des reflets nacrés.',
            'perles' => [['Blanc 6 mm', '#F3EEE3'], ['Nacré blanc 2 mm', '#E9E1D2'], ['Beige 2 mm', '#E0D2BC'], ['Doré 2 mm', '#D9AE55'], ['Champagne 2 mm', '#EDE0C8']],
        ],
        [
            'nom' => 'Butter Bloom', 'image' => 'butter-bloom.png', 'disponible' => false,
            'poeme' => 'Les premières fleurs baignées par la lumière du matin.',
            'perles' => [['Jaune beurre 6 mm', '#F2CE63'], ['Nacré blanc 2 mm', '#EFD9A0'], ['Doré 2 mm', '#D9AE55'], ['Miel 2 mm', '#E0A93F'], ['Corail clair 2 mm', '#E9A05A']],
        ],
        [
            'nom' => 'Coral Kiss', 'image' => 'coral-kiss.png', 'disponible' => false,
            'poeme' => "La chaleur des récifs coralliens embrasse l'horizon.",
            'perles' => [['Rose blush 6 mm', '#DE6E72'], ['Saumon 2 mm', '#E1908E'], ['Nacré blanc 2 mm', '#F1E7DD'], ['Nacré 2 mm', '#D9AE55'], ['Terracotta 2 mm', '#D2603C']],
        ],
        [
            'nom' => 'Lavender Shell', 'image' => 'lavender-shell.png', 'disponible' => false,
            'poeme' => 'Quand le crépuscule habille la mer de nuances lavande.',
            'perles' => [['Lavande 6 mm', '#A48ACB'], ['Lilas 2 mm', '#BFAEDD'], ['Nacré blanc 2 mm', '#F1EBE3'], ['Doré 2 mm', '#D9AE55'], ['Améthyste 2 mm', '#7E5FA6']],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        // Matériaux (communs à tous les modèles)
        $materiaux = [];
        foreach (self::MATERIAUX as $libelle) {
            $materiau = (new Materiau())->setLibelle($libelle);
            $manager->persist($materiau);
            $materiaux[] = $materiau;
        }

        // Drop regroupant les modèles en production
        $drop = (new Drop())
            ->setNom("Bientôt à l'atelier")
            ->setTheme('Sept coloris en cours de broderie')
            ->setDescription('Sept coloris en cours de broderie. Dites-nous celui qui vous plaît : nous vous prévenons dès sa sortie.')
            ->setBanniere('planche.png');
        $manager->persist($drop);

        // Couleurs de perles : une même couleur (nom + code) est partagée entre les modèles
        $couleurs = [];

        foreach (self::MODELES as $i => $data) {
            $modele = (new Modele())
                ->setNom($data['nom'])
                ->setAccroche($data['poeme'])
                ->setDescription($data['poeme'])
                ->setPrix(self::PRIX)
                ->setImage($data['image'])
                ->setStatut($data['disponible'] ? StatutModele::Disponible : StatutModele::EnProduction)
                ->setOrdre(($i + 1) * 10);

            if (!$data['disponible']) {
                $modele->setDrop($drop);
            }

            foreach ($data['perles'] as [$nom, $hex]) {
                $cle = $nom.$hex;
                if (!isset($couleurs[$cle])) {
                    $couleurs[$cle] = (new CouleurPerle())->setNom($nom)->setCodeHex($hex);
                    $manager->persist($couleurs[$cle]);
                }
                $modele->addCouleursPerle($couleurs[$cle]);
            }

            foreach ($materiaux as $materiau) {
                $modele->addMateriau($materiau);
            }

            // Une variante par pointure : du stock pour les paires prêtes, 0 pour celles en production
            for ($pointure = Variante::POINTURE_MIN; $pointure <= Variante::POINTURE_MAX; ++$pointure) {
                $variante = (new Variante())
                    ->setPointure($pointure)
                    ->setStock($data['disponible'] ? self::STOCK_DISPONIBLE[$pointure] : 0);
                $modele->addVariante($variante); // persistée en cascade
                $this->addReference(sprintf('variante-%s-%d', $data['image'], $pointure), $variante);
            }

            $manager->persist($modele);
            $this->addReference('modele-'.$data['image'], $modele);
        }

        $manager->flush();
    }
}
