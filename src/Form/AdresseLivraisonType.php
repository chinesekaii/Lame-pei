<?php

namespace App\Form;

use App\Dto\AdresseLivraison;
use App\Enum\SecteurLivraison;
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
        // Communes regroupées par secteur, avec le tarif dans le titre du groupe
        $communes = [];
        foreach (SecteurLivraison::cases() as $secteur) {
            $groupe = sprintf('%s — %s', $secteur->label(), $secteur->frais() === 0
                ? 'livraison offerte'
                : number_format($secteur->frais() / 100, 0, ',', ' ').' €');
            $communes[$groupe] = array_combine($secteur->communes(), $secteur->communes());
        }

        $builder
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
                'attr' => ['autocomplete' => 'address-line2', 'placeholder' => 'Lieu-dit, quartier, résidence… (facultatif)'],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['autocomplete' => 'postal-code', 'inputmode' => 'numeric', 'maxlength' => 5, 'placeholder' => '974…'],
            ])
            ->add('commune', ChoiceType::class, [
                'label' => 'Commune',
                'choices' => $communes,
                'placeholder' => 'Choisir votre commune',
                // Le tarif de chaque commune, lu par le contrôleur Stimulus « livraison » pour mettre à jour le total
                'choice_attr' => fn (string $commune) => [
                    'data-frais' => SecteurLivraison::pourCommune($commune)?->frais() ?? 0,
                    'data-secteur' => SecteurLivraison::pourCommune($commune)?->label(),
                ],
                'attr' => ['data-action' => 'livraison#changer', 'data-livraison-target' => 'commune'],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'help' => 'Pour convenir avec vous du moment de la livraison.',
                'attr' => ['autocomplete' => 'tel', 'placeholder' => '0692 …'],
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
