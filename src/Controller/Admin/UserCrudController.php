<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Comptes clients. Les comptes sont créés par l'inscription sur le site ;
 * l'admin peut corriger les informations et donner le rôle administrateur.
 */
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->setDefaultSort(['nom' => 'ASC'])
            ->setSearchFields(['email', 'nom', 'prenom']);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Pas de création ici (inscription sur le site) ni de suppression (les commandes sont rattachées au client)
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom');
        yield EmailField::new('email', 'E-mail');
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices(['Administrateur' => 'ROLE_ADMIN'])
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges(['ROLE_ADMIN' => 'danger'])
            ->setHelp('Un administrateur a accès à ce back-office. Tous les comptes ont le rôle client.');
        yield AssociationField::new('commandes')->hideOnForm();
    }
}
