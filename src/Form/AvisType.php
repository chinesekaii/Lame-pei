<?php

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Avis laissé par un client depuis son espace (note obligatoire).
 */
class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Votre note',
                // De 5 à 1 : affichées comme des étoiles par le CSS (voir .choix-etoiles)
                'choices' => [
                    '★★★★★ Excellent' => 5,
                    '★★★★ Très bien' => 4,
                    '★★★ Bien' => 3,
                    '★★ Moyen' => 2,
                    '★ Décevant' => 1,
                ],
                'expanded' => true,
                'attr' => ['class' => 'choix-etoiles'],
                'constraints' => [new Assert\NotNull(message: 'Merci de choisir une note.')],
            ])
            ->add('texte', TextareaType::class, [
                'label' => 'Votre avis',
                'empty_data' => '',
                'attr' => ['rows' => 5, 'placeholder' => 'Confort, finitions, taille… racontez-nous !', 'maxlength' => 1000],
            ])
            ->add('auteur', TextType::class, [
                'label' => 'Nom affiché',
                'empty_data' => '',
                'help' => 'Par exemple votre prénom et l\'initiale de votre nom.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Avis::class]);
    }
}
