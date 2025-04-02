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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
            ]);

        // Si l'utilisateur est administrateur OU pilote, ajouter le champ de sélection d'entreprise
        if ($options['is_admin'] || $options['is_pilot']) {
            // Définir des options spécifiques selon le rôle
            $companyOptions = [
                'class' => Company::class,
                'choice_label' => 'name',
                'label' => 'Entreprise',
                'placeholder' => 'Sélectionnez une entreprise',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ];
            
            $builder->add('company', EntityType::class, $companyOptions);
        }

        $builder
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'is_admin' => false,     // Option pour les administrateurs
            'is_pilot' => false,     // Option pour les pilotes
            'pilot_company' => null, // Entreprise du pilote
        ]);
    }
}