<?php

namespace App\Form;

use App\Entity\User;
use App\Form\PrestataireProfileType;
use App\Form\ClientProfileType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountSettingsType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Jean']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Dupont']
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 06 12 34 56 78'],
                'required' => false 
            ])
            // ->add('avatar') 
        ;

        if ($this->security->isGranted('ROLE_PRESTATAIRE')) {
            $builder->add('prestataireProfile', PrestataireProfileType::class, [
                'label' => false,
            ]);
        } else {
            $builder->add('clientProfile', ClientProfileType::class, [
                'label' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // 'csrf_protection' => true,
        ]);
    }
}