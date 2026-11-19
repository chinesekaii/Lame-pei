<?php

namespace App\Controller\Admin;

use App\Entity\Drop;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DropCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Drop::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Drop')
            ->setEntityLabelInPlural('Drops')
            ->setDefaultSort(['dateSortie' => 'DESC'])
            ->setHelp(Crud::PAGE_INDEX, 'Un drop regroupe des modèles qui sortent ensemble. Les modèles se rattachent à un drop depuis leur propre fiche.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('banniere', 'Bannière')
            ->setBasePath('uploads/drops')
            ->setUploadDir('public/uploads/drops')
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->setRequired(false);
        yield TextField::new('nom');
        yield TextField::new('theme', 'Thème');
        yield DateField::new('dateSortie', 'Date de sortie');
        yield TextareaField::new('description')->hideOnIndex();
        yield AssociationField::new('modeles', 'Modèles')->hideOnForm();
    }
}
