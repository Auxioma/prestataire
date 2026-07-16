<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PrestataireNotificationPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notifyOnQuoteRequestReceived', CheckboxType::class, [
                'label' => 'Nouvelle demande de devis',
                'help' => 'Recevoir une notification lorsqu’un client envoie une nouvelle demande.',
                'required' => false,
            ])
            ->add('notifyOnMessageReceived', CheckboxType::class, [
                'label' => 'Nouveau message',
                'help' => 'Recevoir une notification lors d’un nouveau message dans une conversation.',
                'required' => false,
            ])
            ->add('notifyOnQuoteRequestAccepted', CheckboxType::class, [
                'label' => 'Acceptation d’un devis / d’une demande',
                'help' => 'Recevoir une notification lorsqu’une demande liée à votre activité passe au statut accepté.',
                'required' => false,
            ])
            ->add('notifyOnReviewReceived', CheckboxType::class, [
                'label' => 'Nouvel avis',
                'help' => 'Recevoir une notification lorsqu’un client publie un nouvel avis.',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
