<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Jean',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Dupont',
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 06 12 34 56 78',
                ],
            ]);

        if ($options['profile_type'] === 'prestataire') {
            $builder->add('prestataireProfile', PrestatairePublicProfileTabType::class, [
                'label' => false,
            ]);
        }

        if ($options['profile_type'] === 'client') {
            $builder->add('clientProfile', ClientProfileType::class, [
                'label' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'profile_type' => null,
        ]);

        $resolver->setAllowedValues('profile_type', [null, 'prestataire', 'client']);
    }
}
