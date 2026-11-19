<?php

namespace App\Form;

use App\Entity\DemandeSurMesure;
use App\Entity\Modele;
use App\Entity\Variante;
use App\Enum\StatutModele;
use App\Repository\ModeleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire « Sur-mesure & sur demande » de la page d'accueil.
 */
class DemandeSurMesureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modele', EntityType::class, [
                'label' => 'Coloris souhaité',
                'class' => Modele::class,
                'required' => false,
                // Option vide = création libre, sans modèle de référence
                'placeholder' => 'Sur-mesure — création libre',
                'query_builder' => fn (ModeleRepository $r) => $r->createQueryBuilder('m')
                    ->andWhere('m.statut != :archive')
                    ->setParameter('archive', StatutModele::Archive)
                    ->orderBy('m.ordre', 'ASC'),
                'choice_label' => fn (Modele $m) => $m->isDisponible() ? $m->getNom().' (disponible)' : $m->getNom(),
            ])
            ->add('taille', ChoiceType::class, [
                'label' => 'Taille',
                'choices' => array_combine(
                    range(Variante::POINTURE_MIN, Variante::POINTURE_MAX),
                    range(Variante::POINTURE_MIN, Variante::POINTURE_MAX),
                ),
                'expanded' => true,
                'attr' => ['class' => 'choix-tailles'],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'empty_data' => '',
                'attr' => ['rows' => 3, 'placeholder' => 'Occasion, couleurs, date souhaitée…'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Votre nom',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre e-mail',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'email'],
            ]);

        // Le coloris enregistré est le nom du modèle choisi, ou « Sur-mesure » (avant la validation)
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var DemandeSurMesure $demande */
            $demande = $event->getData();
            $demande->setColoris($demande->getModele()?->getNom() ?? 'Sur-mesure');
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeSurMesure::class,
        ]);
    }
}
