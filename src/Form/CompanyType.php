<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    /**
     * Configuration du formulaire pour les entreprises
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ nom de l'entreprise
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr' => ['placeholder' => 'Ex: Acme Inc.'],
                'required' => true
            ])
            // Champ email de l'entreprise
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'contact@entreprise.com'],
                'required' => true
            ])
            // Champ téléphone
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => '+33 1 23 45 67 89'],
                'required' => true
            ])
            // Champ description
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 5, 'placeholder' => 'Description de l\'entreprise...'],
                'required' => true
            ])
            // Formulaire imbriqué pour l'adresse
            ->add('address', AddressType::class, [
                'label' => false,
                'required' => true,
            ])
            // Champ pour l'upload du logo
            ->add('imageFile', FileType::class, [
                'label' => 'Logo de l\'entreprise',
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png',
                    'class' => 'custom-file-input'
                ],
            ]);
    }

    /**
     * Configuration des options par défaut du formulaire
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}