<?php

namespace App\Form;

use App\Entity\ClientProfile;
use App\Enum\ClientTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'class' => ClientTypeEnum::class,
                'label' => 'Type de compte',
                'expanded' => true,
                'choice_label' => fn (ClientTypeEnum $choice) => match ($choice) {
                    ClientTypeEnum::PARTICULIER => 'Particulier',
                    ClientTypeEnum::PROFESSIONNEL => 'Entreprise / Professionnel',
                },
                'attr' => ['class' => 'mb-3']
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: TrouveMoi Inc.']
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro de SIRET',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '14 chiffres']
            ])
            
            // --- Section Adresse de Facturation ---
            ->add('billingAddress', TextType::class, [
                'label' => 'Adresse de facturation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('billingPostalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('billingCity', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control']
            ])
            ->add('billingCountry', TextType::class, [
                'label' => 'Pays',
                'data' => 'France', // Valeur par défaut
                'attr' => ['class' => 'form-control']
            ])

            // --- Section Adresse d'Intervention / Par défaut ---
            ->add('defaultAddress', TextType::class, [
                'label' => 'Adresse par défaut (Intervention)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('defaultPostalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('defaultCity', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClientProfile::class,
        ]);
    }
}