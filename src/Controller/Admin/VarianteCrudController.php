<?php

namespace App\Controller\Admin;

use App\Entity\Variante;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

/**
 * Utilisé uniquement comme formulaire imbriqué dans la fiche d'un modèle (stock par pointure).
 */
class VarianteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Variante::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('pointure')
            ->setFormTypeOption('disabled', true)
            ->setColumns(6);
        yield IntegerField::new('stock')
            ->setFormTypeOption('attr', ['min' => 0])
            ->setColumns(6);
    }
}
