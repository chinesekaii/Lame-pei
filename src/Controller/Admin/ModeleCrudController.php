<?php

namespace App\Controller\Admin;

use App\Entity\Modele;
use App\Enum\StatutModele;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Doctrine\ORM\EntityManagerInterface;

class ModeleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Modele::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Modèle')
            ->setEntityLabelInPlural('Modèles')
            ->setPageTitle(Crud::PAGE_INDEX, 'Modèles')
            ->setDefaultSort(['ordre' => 'ASC'])
            ->setSearchFields(['nom', 'accroche', 'description'])
            ->setHelp(Crud::PAGE_NEW, 'Les 5 tailles Havaianas (33/34 à 41/42) sont créées automatiquement avec un stock à 0 : renseignez le stock ensuite.');
    }

    public function configureActions(Actions $actions): Actions
    {
        $voir = Action::new('voirSurLeSite', 'Voir sur le site', 'fa fa-eye')
            ->linkToRoute('app_modele_show', fn (Modele $modele) => ['slug' => $modele->getSlug()])
            ->displayIf(fn (Modele $modele) => $modele->getStatut() !== StatutModele::Archive);

        return $actions
            ->add(Crud::PAGE_INDEX, $voir)
            ->add(Crud::PAGE_EDIT, $voir)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('statut')->setChoices(StatutModele::cases()))
            ->add(EntityFilter::new('drop', 'Collection'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(8);
        yield FormField::addFieldset('Présentation');
        yield IdField::new('id')->onlyOnDetail();
        yield ImageField::new('image', 'Photo')
            ->setBasePath('uploads/modeles')
            ->setUploadDir('public/uploads/modeles')
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->setHelp('Photo verticale, au format PNG ou JPG.');
        yield ImageField::new('galerie', 'Photos supplémentaires')
            ->setBasePath('uploads/modeles')
            ->setUploadDir('public/uploads/modeles')
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->setFormTypeOption('multiple', true)
            ->setRequired(false)
            ->setHelp('Autres vues du modèle, affichées en vignettes sur la fiche.')
            ->hideOnIndex();
        yield TextField::new('nom');
        yield SlugField::new('slug')
            ->setTargetFieldName('nom')
            ->setHelp('Adresse de la fiche : /modele/…')
            ->hideOnIndex();
        yield TextField::new('accroche')
            ->setHelp('Phrase courte affichée sous la photo sur l\'accueil.')
            ->hideOnIndex();
        yield TextareaField::new('description', 'Texte poétique')
            ->setHelp('Affiché en italique sur la fiche produit.')
            ->hideOnIndex();

        yield FormField::addFieldset('Palette et matériaux');
        yield AssociationField::new('couleursPerle', 'Couleurs de perles')
            ->setFormTypeOption('by_reference', false)
            ->autocomplete()
            ->hideOnIndex();
        yield AssociationField::new('materiaux', 'Matériaux')
            ->setFormTypeOption('by_reference', false)
            ->hideOnIndex();

        yield FormField::addFieldset('Stock par taille')
            ->setHelp('Nombre de paires prêtes à partir dans chaque taille.')
            ->onlyWhenUpdating();
        yield CollectionField::new('variantes', false)
            ->useEntryCrudForm(VarianteCrudController::class)
            ->allowAdd(false)
            ->allowDelete(false)
            ->renderExpanded()
            ->onlyWhenUpdating();
        yield CollectionField::new('variantes', 'Stock par taille')
            ->onlyOnDetail();

        yield FormField::addColumn(4);
        yield FormField::addFieldset('Vente');
        yield ChoiceField::new('statut')
            ->renderAsBadges([
                StatutModele::Disponible->name => 'success',
                StatutModele::EnProduction->name => 'warning',
                StatutModele::Archive->name => 'secondary',
            ]);
        yield MoneyField::new('prix')
            ->setCurrency('EUR')
            ->setStoredAsCents();
        yield IntegerField::new('stockTotal', 'Stock')
            ->onlyOnIndex();
        yield ColorField::new('couleur', 'Couleur du titre')
            ->setHelp('Couleur du nom du modèle sur le site.')
            ->hideOnIndex();
        yield AssociationField::new('drop', 'Collection')
            ->setHelp('Collection à laquelle appartient ce modèle (Fleur Peï, Éclat Peï…).');
        yield IntegerField::new('ordre', 'Ordre d\'affichage')
            ->setHelp('Le plus petit s\'affiche en premier.');
    }

    /**
     * À la création, on ajoute automatiquement les 5 tailles.
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Modele) {
            $entityInstance->completerVariantes();
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
