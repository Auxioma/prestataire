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
            // 1. Informations communes de la table 'users'
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => ['class' => 'form-control'],
                'required' => false 
            ])
            // ->add('avatar') 
        ;

        // 2. Imbrication conditionnelle selon le rôle de l'utilisateur connecté
        if ($this->security->isGranted('ROLE_PRESTATAIRE')) {
            $builder->add('prestataireProfile', PrestataireProfileType::class, [
                'label' => false,
            ]);
        } else {
            // Si ce n'est pas un prestataire, c'est un client connecté sur sa route dédiée
            $builder->add('clientProfile', ClientProfileType::class, [
                'label' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}