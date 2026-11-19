<?php

namespace App\Controller\Admin;

use App\Entity\Variante;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Utilisé uniquement comme formulaire imbriqué dans la fiche d'un modèle (stock par taille).
 */
class VarianteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Variante::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('taille')
            ->setFormTypeOption('disabled', true)
            ->setColumns(6);
        yield IntegerField::new('stock')
            ->setFormTypeOption('attr', ['min' => 0])
            ->setColumns(6);
    }
}
