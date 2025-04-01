<?php

namespace App\Form;

use App\Entity\Offer;
use App\Entity\Company;
use App\Entity\Skill;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class OfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'offre',
                'attr' => [
                    'placeholder' => 'Ex: Stage développeur web PHP/Symfony',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du poste',
                'attr' => [
                    'placeholder' => 'Décrivez les responsabilités, tâches et le contexte du stage...',
                    'rows' => 10,
                    'class' => 'form-control'
                ]
            ])
            ->add('remuneration', MoneyType::class, [
                'label' => 'Rémunération mensuelle (€)',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-control select2'
                ],
                'label' => 'Compétences requises'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Offre active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);
        
        // Si l'option is_admin est passée, ajouter le champ company
        if ($options['is_admin']) {
            $builder->add('company', EntityType::class, [
                'class' => Company::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une entreprise',
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Entreprise'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'is_admin' => false, // Option par défaut pour contrôler l'affichage du champ company
        ]);
    }
}