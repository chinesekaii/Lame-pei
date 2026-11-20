<?php

namespace App\Controller\Admin;

use App\Entity\Avis;
use App\Enum\SourceAvis;
use App\Enum\StatutAvis;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Modération des avis clients, et ajout des retours reçus ailleurs (Instagram, messages…).
 */
class AvisCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $urlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Avis::class;
    }

    public function createEntity(string $entityFqcn): Avis
    {
        // Un avis ajouté par la marque est un retour reçu ailleurs que sur le site
        return (new Avis())->setSource(SourceAvis::Instagram);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Avis')
            ->setEntityLabelInPlural('Avis')
            ->setDefaultSort(['statut' => 'ASC', 'createdAt' => 'DESC'])
            ->setSearchFields(['auteur', 'texte', 'modele.nom'])
            ->setHelp(Crud::PAGE_INDEX, 'Un avis n\'apparaît sur le site qu\'une fois publié. Avant de publier un retour reçu sur Instagram ou par message, assurez-vous d\'avoir l\'accord de la personne.')
            ->setHelp(Crud::PAGE_NEW, 'Pour un retour reçu sur Instagram ou par message : recopiez le texte tel quel. Ne mettez une note que si la personne en a donné une.');
    }

    public function configureActions(Actions $actions): Actions
    {
        $publier = Action::new('publier', 'Publier', 'fa fa-check')
            ->linkToCrudAction('publier')
            ->renderAsForm()
            ->displayIf(fn (Avis $avis) => $avis->getStatut() !== StatutAvis::Publie);
        $refuser = Action::new('refuser', 'Refuser', 'fa fa-ban')
            ->linkToCrudAction('refuser')
            ->renderAsForm()
            ->displayIf(fn (Avis $avis) => $avis->getStatut() !== StatutAvis::Refuse);

        return $actions
            ->add(Crud::PAGE_INDEX, $publier)
            ->add(Crud::PAGE_INDEX, $refuser)
            ->add(Crud::PAGE_DETAIL, $publier)
            ->add(Crud::PAGE_DETAIL, $refuser)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('statut')->setChoices(StatutAvis::cases()))
            ->add(ChoiceFilter::new('source', 'Origine')->setChoices(SourceAvis::cases()));
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('statut')
            ->renderAsBadges([
                StatutAvis::EnAttente->name => 'warning',
                StatutAvis::Publie->name => 'success',
                StatutAvis::Refuse->name => 'secondary',
            ]);
        yield TextField::new('auteur', 'Nom affiché')
            ->setHelp('Prénom et initiale du nom, ex. « Maëva P. ».');
        yield ChoiceField::new('note')
            ->setChoices(['★★★★★ (5)' => 5, '★★★★ (4)' => 4, '★★★ (3)' => 3, '★★ (2)' => 2, '★ (1)' => 1])
            ->setRequired(false)
            ->setHelp('Laisser vide si la personne n\'a pas donné de note : l\'avis s\'affichera sans étoiles.');
        yield TextareaField::new('texte')->setMaxLength(80)->hideOnForm();
        yield TextareaField::new('texte')->onlyOnForms();
        yield AssociationField::new('modele', 'Modèle')->setRequired(false);
        yield ChoiceField::new('source', 'Origine');
        yield BooleanField::new('achatVerifie', 'Achat vérifié')
            ->renderAsSwitch(false)
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Date')
            ->setHelp('Date à laquelle le retour a été reçu.');
        yield AssociationField::new('user', 'Compte client')->onlyOnDetail();
        yield DateTimeField::new('publieLe', 'Publié le')->onlyOnDetail();
    }

    #[AdminRoute('/{id}/publier', name: 'publier', options: ['methods' => ['POST']])]
    public function publier(Avis $avis): RedirectResponse
    {
        $avis->setStatut(StatutAvis::Publie);
        $this->em->flush();
        $this->addFlash('success', sprintf('Avis de %s publié sur le site.', $avis->getAuteur()));

        return $this->retourListe();
    }

    #[AdminRoute('/{id}/refuser', name: 'refuser', options: ['methods' => ['POST']])]
    public function refuser(Avis $avis): RedirectResponse
    {
        $avis->setStatut(StatutAvis::Refuse);
        $this->em->flush();
        $this->addFlash('info', sprintf('Avis de %s refusé : il n\'apparaît pas sur le site.', $avis->getAuteur()));

        return $this->retourListe();
    }

    private function retourListe(): RedirectResponse
    {
        return $this->redirect($this->urlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
    }
}
