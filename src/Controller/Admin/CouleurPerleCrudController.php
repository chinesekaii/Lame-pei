<?php

namespace App\Controller\Admin;

use App\Entity\CouleurPerle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CouleurPerleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CouleurPerle::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Couleur de perle')
            ->setEntityLabelInPlural('Couleurs de perles')
            ->setDefaultSort(['nom' => 'ASC'])
            ->setSearchFields(['nom', 'codeHex']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ColorField::new('codeHex', 'Couleur')->showValue();
        yield TextField::new('nom')->setHelp('Ex. « Nacré pêche 6 mm ».');
        yield AssociationField::new('modeles', 'Utilisée par')->onlyOnIndex();
    }
}
