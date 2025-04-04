<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    /**
     * Configuration du formulaire pour les adresses
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ rue
            ->add('street', TextType::class, [
                'label' => 'Rue',
                'attr' => ['placeholder' => '123 rue des Exemples'],
                'required' => true
            ])
            // Champ ville
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['placeholder' => 'Paris'],
                'required' => true
            ])
            // Champ pays avec liste prédéfinie
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'preferred_choices' => ['FR', 'BE', 'CH', 'LU', 'DE', 'ES', 'IT', 'GB', 'US', 'CA'],
                'placeholder' => 'Sélectionnez un pays',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ]);
    }

    /**
     * Configuration des options par défaut du formulaire
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}