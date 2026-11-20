<?php

namespace App\Controller\Admin;

use App\Entity\PhotoClient;
use App\Service\ImageWeb;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PhotoClientCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ImageWeb $imageWeb,
        #[Autowire('%kernel.project_dir%/public/uploads/clients')] private readonly string $dossier,
    ) {
    }

    /** Après l'envoi : photo redressée, réduite et sans métadonnées (GPS…). */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->preparerImage($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->preparerImage($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function preparerImage(mixed $photo): void
    {
        if ($photo instanceof PhotoClient && $photo->getImage() && is_file($this->dossier.'/'.$photo->getImage())) {
            $this->imageWeb->preparer($this->dossier.'/'.$photo->getImage());
        }
    }

    public static function getEntityFqcn(): string
    {
        return PhotoClient::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Photo client')
            ->setEntityLabelInPlural('Photos clients')
            ->setDefaultSort(['ordre' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Galerie « Elles l\'ont adoptée ». Règle de la marque : jamais de photo où l\'on voit un visage, et toujours avec l\'accord de la personne.')
            ->setHelp(Crud::PAGE_NEW, 'Pas de visage, pas de pseudo ni de photo de profil Instagram visibles : recadrez la capture avant de l\'envoyer.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('image', 'Photo')
            ->setBasePath('uploads/clients')
            ->setUploadDir('public/uploads/clients')
            ->setUploadedFileNamePattern('client-[randomhash].[extension]')
            ->setRequired($pageName === Crud::PAGE_NEW);
        yield TextField::new('legende', 'Légende')->setRequired(false);
        yield AssociationField::new('modele', 'Modèle porté')->setRequired(false);
        yield IntegerField::new('ordre', 'Ordre d\'affichage');
        yield BooleanField::new('visible', 'Visible sur le site');
    }
}
