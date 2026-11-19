<?php

namespace App\Controller\Admin;

use App\Entity\Materiau;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MateriauCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Materiau::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Matériau')
            ->setEntityLabelInPlural('Matériaux')
            ->setDefaultSort(['libelle' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('libelle', 'Libellé');
        yield AssociationField::new('modeles', 'Utilisé par')->onlyOnIndex();
    }
}
