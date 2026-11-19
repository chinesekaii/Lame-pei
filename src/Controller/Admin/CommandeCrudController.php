<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Enum\StatutCommande;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

/**
 * Les commandes sont créées par les clients : ici on suit leur statut.
 */
class CommandeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['reference', 'user.email', 'user.nom', 'adresseLivraison'])
            ->setHelp(Crud::PAGE_EDIT, 'Passer une commande en « Annulée » remet automatiquement les paires en stock.');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $a) => $a->setLabel('Changer le statut'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('statut')->setChoices(StatutCommande::cases()))
            ->add(DateTimeFilter::new('createdAt', 'Date'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence')->setDisabled();
        yield DateTimeField::new('createdAt', 'Date')->setDisabled();
        yield AssociationField::new('user', 'Client')->setDisabled();
        yield MoneyField::new('total')
            ->setCurrency('EUR')
            ->setStoredAsCents()
            ->hideOnForm();
        yield ChoiceField::new('statut')
            ->renderAsBadges([
                StatutCommande::EnAttente->name => 'warning',
                StatutCommande::Payee->name => 'info',
                StatutCommande::EnFabrication->name => 'primary',
                StatutCommande::Expediee->name => 'success',
                StatutCommande::Livree->name => 'success',
                StatutCommande::Annulee->name => 'secondary',
            ]);
        yield CollectionField::new('lignes', 'Articles')->onlyOnDetail();
        yield TextareaField::new('adresseLivraison', 'Adresse de livraison')
            ->onlyOnDetail()
            ->renderAsHtml(false);
        yield DateTimeField::new('cgvAccepteesLe', 'CGV acceptées le')->onlyOnDetail();
    }

    /**
     * Annulation : les paires réservées retournent en stock (et inversement si on « dés-annule »).
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Commande) {
            $ancien = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['statut'] ?? null;
            $nouveau = $entityInstance->getStatut();

            if ($ancien !== StatutCommande::Annulee && $nouveau === StatutCommande::Annulee) {
                $this->ajusterStock($entityInstance, +1);
                $this->addFlash('success', 'Commande annulée : les paires sont remises en stock.');
            } elseif ($ancien === StatutCommande::Annulee && $nouveau !== StatutCommande::Annulee) {
                $this->ajusterStock($entityInstance, -1);
                $this->addFlash('warning', 'Commande réactivée : les paires sont à nouveau retirées du stock.');
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function ajusterStock(Commande $commande, int $sens): void
    {
        foreach ($commande->getLignes() as $ligne) {
            $variante = $ligne->getVariante();
            $variante->setStock(max(0, $variante->getStock() + $sens * $ligne->getQuantite()));
        }
    }
}
