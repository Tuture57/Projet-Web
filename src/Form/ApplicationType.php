<?php

namespace App\Form;

use App\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour la lettre de motivation
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'required' => true, // Champ obligatoire
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez fournir une lettre de motivation'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Expliquez pourquoi vous êtes intéressé par cette offre...',
                    'rows' => 8
                ]
            ])
            // Champ pour le CV avec validation du format
            ->add('cvFile', FileType::class, [
                'label' => 'CV (PDF recommandé)',
                'mapped' => false,
                'required' => true, // Champ obligatoire
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez télécharger votre CV'
                    ]),
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un CV valide (PDF, DOC ou DOCX)'
                    ])
                ],
                'attr' => [
                    'class' => 'custom-file-input'
                ],
                'help' => 'Format PDF, DOC ou DOCX - 2 Mo maximum'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Configuration du formulaire avec l'entité associée
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }
}
