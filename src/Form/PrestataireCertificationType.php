<?php

namespace App\Form;

use App\Entity\PrestataireDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class PrestataireCertificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('documentFile', FileType::class, [
                'label' => 'Certification ou diplôme',
                'required' => true,
                'mapped' => true,
                'constraints' => [
                    new NotNull(message: 'Veuillez sélectionner un fichier.'),
                    new File(
                        maxSize: '8M',
                        mimeTypes: [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Veuillez envoyer un PDF, JPG, PNG ou WEBP.',
                    ),
                ],
                'attr' => [
                    'accept' => '.pdf,image/jpeg,image/png,image/webp',
                ],
            ])
            ->add('issuedAt', DateType::class, [
                'label' => 'Date d’obtention',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('expiresAt', DateType::class, [
                'label' => 'Date de fin de validité (si applicable)',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Précisions',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Exemple : CAP plomberie, Qualibat, RGE, organisme certificateur...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireDocument::class,
        ]);
    }
}
