<?php

namespace App\DataFixtures;

use App\Catalogue\CatalogueReference;
use App\Catalogue\CatalogueSynchroniseur;
use App\Entity\CouleurPerle;
use App\Entity\Modele;
use App\Enum\StatutModele;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Catalogue de démonstration :
 *  - les 7 anciens modèles de la maquette, archivés (invisibles sur le site) ;
 *  - le catalogue actuel (collections Fleur Peï et Éclat Peï), créé par le même
 *    synchroniseur que la commande app:catalogue:synchroniser.
 */
class CatalogueFixtures extends Fixture
{
    /** Anciens modèles « en production » de la maquette (archivés). */
    private const ANCIENS_MODELES = [
        'lagon' => ['Lagon', '#2E7C86', 'Le premier reflet du soleil sur une eau cristalline.', [['Bleu lagon 6 mm', '#57BFC9'], ['Turquoise 2 mm', '#4FB5A8'], ['Doré 2 mm', '#D9AE55']]],
        'mint-lagoon' => ['Mint Lagoon', '#5C8348', 'Le souffle des jardins tropicaux qui rejoint l\'océan.', [['Mint 6 mm', '#7FBE8E'], ['Vert sauge 2 mm', '#6E8F5B'], ['Doré 2 mm', '#D9AE55']]],
        'azure-sea' => ['Azure Sea', '#284D82', 'L\'océan, immense et profond, où le bleu semble infini.', [['Bleu azur 6 mm', '#2F5FA8'], ['Bleu marine 2 mm', '#1E3F73'], ['Argenté 2 mm', '#B9BFC6']]],
        'fleur-de-sable' => ['Fleur de sable', '#8C7148', 'Le sable chaud illuminé par des reflets nacrés.', [['Blanc 6 mm', '#F3EEE3'], ['Beige 2 mm', '#E0D2BC'], ['Doré 2 mm', '#D9AE55']]],
        'butter-bloom' => ['Butter Bloom', '#B58A24', 'Les premières fleurs baignées par la lumière du matin.', [['Jaune beurre 6 mm', '#F2CE63'], ['Miel 2 mm', '#E0A93F'], ['Doré 2 mm', '#D9AE55']]],
        'coral-kiss' => ['Coral Kiss', '#C15A50', 'La chaleur des récifs coralliens embrasse l\'horizon.', [['Rose blush 6 mm', '#DE6E72'], ['Terracotta 2 mm', '#D2603C'], ['Doré 2 mm', '#D9AE55']]],
        'lavender-shell' => ['Lavender Shell', '#7658A6', 'Quand le crépuscule habille la mer de nuances lavande.', [['Lavande 6 mm', '#A48ACB'], ['Améthyste 2 mm', '#7E5FA6'], ['Doré 2 mm', '#D9AE55']]],
    ];

    public function __construct(private readonly CatalogueSynchroniseur $synchroniseur)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Anciens modèles, archivés
        $couleurs = [];
        $ordre = 100;
        foreach (self::ANCIENS_MODELES as $slug => [$nom, $couleur, $texte, $perles]) {
            $modele = (new Modele())
                ->setNom($nom)
                ->setSlug($slug)
                ->setImage($slug.'.png')
                ->setCouleur($couleur)
                ->setAccroche($texte)
                ->setDescription($texte)
                ->setPrix(CatalogueReference::PRIX)
                ->setStatut(StatutModele::Archive)
                ->setOrdre($ordre += 10)
                ->completerVariantes();

            foreach ($perles as [$nomPerle, $hex]) {
                $couleurs[$nomPerle.$hex] ??= (new CouleurPerle())->setNom($nomPerle)->setCodeHex($hex);
                $manager->persist($couleurs[$nomPerle.$hex]);
                $modele->addCouleursPerle($couleurs[$nomPerle.$hex]);
            }

            $manager->persist($modele);
            $this->addReference('modele-'.$slug, $modele);
        }
        $manager->flush();

        // 2. Catalogue actuel : collections, modèles, palettes et stock
        $this->synchroniseur->synchroniser();

        foreach (array_keys(CatalogueReference::MODELES) as $slug) {
            $modele = $manager->getRepository(Modele::class)->findOneBy(['slug' => $slug]);
            $this->addReference('modele-'.$slug, $modele);
            foreach ($modele->getVariantes() as $variante) {
                $this->addReference(sprintf('variante-%s-%s', $slug, $variante->getTaille()), $variante);
            }
        }
    }
}
