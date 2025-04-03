<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class StudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Construction du formulaire avec les champs de base
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('school', TextType::class, [
                'label' => 'École',
                'required' => false,
            ])
            ->add('searchStatus', ChoiceType::class, [
                'label' => 'Statut de recherche',
                'required' => false,
                'choices' => [
                    'En recherche' => 'En recherche',
                    'Stage trouvé' => 'Stage trouvé',
                    'Non disponible' => 'Non disponible',
                ],
                'placeholder' => 'Sélectionnez un statut',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo de profil',
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png',
                    'class' => 'custom-file-input'
                ],
            ]);
            
        // Gestion du mot de passe selon le contexte (création ou édition)
        if (!$options['is_edit']) {
            // Cas d'un nouveau compte
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'constraints' => [
                        new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Le mot de passe doit comporter au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ]);
        } else {
            // Cas d'une édition de compte existant
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Nouveau mot de passe (laisser vide pour ne pas changer)',
                    'constraints' => [
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Le mot de passe doit comporter au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Configuration des options par défaut du formulaire
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false, // Option pour différencier mode création et édition
        ]);
    }
}