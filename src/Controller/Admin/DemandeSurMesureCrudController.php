<?php

namespace App\Controller\Admin;

use App\Entity\DemandeSurMesure;
use App\Enum\StatutDemande;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

/**
 * Demandes envoyées depuis le formulaire « Sur-mesure » de l'accueil.
 */
class DemandeSurMesureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DemandeSurMesure::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande sur-mesure')
            ->setEntityLabelInPlural('Demandes sur-mesure')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['nom', 'email', 'coloris', 'message']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $repondre = Action::new('repondre', 'Répondre par e-mail', 'fa fa-reply')
            ->linkToUrl(fn (DemandeSurMesure $d) => sprintf(
                'mailto:%s?subject=%s',
                $d->getEmail(),
                rawurlencode('Votre demande sur-mesure L\'ÂME PEÏ'),
            ));

        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $repondre)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $a) => $a->setLabel('Changer le statut'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(ChoiceFilter::new('statut')->setChoices(StatutDemande::cases()));
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('createdAt', 'Reçue le')->setDisabled();
        yield TextField::new('nom')->setDisabled();
        yield EmailField::new('email', 'E-mail')->setDisabled();
        yield TextField::new('coloris')->setDisabled();
        yield AssociationField::new('modele', 'Modèle de référence')->onlyOnDetail();
        yield TextField::new('taille')->setDisabled();
        yield TextareaField::new('message')->setDisabled()->hideOnIndex();
        yield ChoiceField::new('statut')
            ->renderAsBadges([
                StatutDemande::Nouvelle->name => 'danger',
                StatutDemande::EnCours->name => 'warning',
                StatutDemande::Acceptee->name => 'success',
                StatutDemande::Refusee->name => 'secondary',
            ]);
    }
}
