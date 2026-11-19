<?php

namespace App\Form;

use App\Dto\AdresseLivraison;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresseLivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', ChoiceType::class, [
                'label' => 'Livraison',
                'choices' => [
                    'La Réunion' => AdresseLivraison::REUNION,
                    'France métropolitaine' => AdresseLivraison::METROPOLE,
                ],
                'expanded' => true,
            ])
            ->add('nomComplet', TextType::class, [
                'label' => 'Nom du destinataire',
                'attr' => ['autocomplete' => 'name'],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['autocomplete' => 'address-line1', 'placeholder' => 'N° et nom de rue'],
            ])
            ->add('complement', TextType::class, [
                'label' => 'Complément',
                'required' => false,
                'attr' => ['autocomplete' => 'address-line2', 'placeholder' => 'Bâtiment, lieu-dit… (facultatif)'],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['autocomplete' => 'postal-code', 'inputmode' => 'numeric', 'maxlength' => 5],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['autocomplete' => 'address-level2'],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'help' => 'Facultatif — utile au transporteur.',
                'attr' => ['autocomplete' => 'tel'],
            ])
            // Le libellé (avec le lien vers les CGV) est écrit dans le template commande/new.html.twig
            ->add('accepteCgv', CheckboxType::class, [
                'label' => false,
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdresseLivraison::class,
        ]);
    }
}
