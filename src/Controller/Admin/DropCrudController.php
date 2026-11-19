<?php

namespace App\Controller\Admin;

use App\Entity\Drop;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Collections (Fleur Peï, Éclat Peï…). L'entité s'appelle Drop dans le code.
 */
class DropCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Drop::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Collection')
            ->setEntityLabelInPlural('Collections')
            ->setDefaultSort(['ordre' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Chaque collection a sa section sur l\'accueil et sa page de présentation. Les modèles se rattachent à une collection depuis leur propre fiche.');
    }

    public function configureActions(Actions $actions): Actions
    {
        $voir = Action::new('voirSurLeSite', 'Voir sur le site', 'fa fa-eye')
            ->linkToRoute('app_collection_show', fn (Drop $collection) => ['slug' => $collection->getSlug()])
            ->displayIf(fn (Drop $collection) => $collection->getSlug() !== null);

        return $actions
            ->add(Crud::PAGE_INDEX, $voir)
            ->add(Crud::PAGE_EDIT, $voir);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom');
        yield SlugField::new('slug')
            ->setTargetFieldName('nom')
            ->setHelp('Adresse de la page : /collection/…')
            ->hideOnIndex();
        yield TextField::new('theme', 'Sous-titre')
            ->setHelp('Petit texte au-dessus du nom, ex. « Collection 1 ».');
        yield TextareaField::new('description', 'Présentation')->hideOnIndex();
        yield IntegerField::new('ordre', 'Ordre d\'affichage');
        yield AssociationField::new('modeles', 'Modèles')->hideOnForm();
        yield IntegerField::new('stockTotal', 'Stock')->onlyOnIndex();
        yield ImageField::new('banniere', 'Bannière')
            ->setBasePath('uploads/drops')
            ->setUploadDir('public/uploads/drops')
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield DateField::new('dateSortie', 'Date de sortie')->hideOnIndex();
    }
}
