<?php

namespace App\Controller\Admin;

use App\Entity\Alerte;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

/**
 * Inscriptions « Me prévenir de sa sortie ».
 */
class AlerteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Alerte::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Alerte de sortie')
            ->setEntityLabelInPlural('Alertes de sortie')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['email', 'modele.nom'])
            ->setHelp(Crud::PAGE_INDEX, 'Filtrez par modèle pour obtenir la liste des personnes à prévenir. Cochez « Prévenu(e) » une fois l\'e-mail envoyé.');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Les inscriptions viennent du site : pas de création ici.
        // L'action « Modifier » reste active car l'interrupteur « Prévenu(e) » de la liste en a besoin.
        return $actions->disable(Action::NEW);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('modele'))
            ->add(BooleanFilter::new('envoyee', 'Prévenu(e)'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email', 'E-mail')->setDisabled();
        yield AssociationField::new('modele', 'Modèle')->setDisabled();
        yield DateTimeField::new('createdAt', 'Inscrit(e) le')->hideOnForm();
        // Interrupteur cliquable directement dans la liste
        yield BooleanField::new('envoyee', 'Prévenu(e)');
    }
}
