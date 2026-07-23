<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClientNotificationPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notifyOnMessageReceived', CheckboxType::class, [
                'label' => 'Nouveau message',
                'help' => 'Recevoir une notification lors d’un nouveau message dans une conversation.',
                'required' => false,
            ])
            ->add('notifyOnQuoteRequestAccepted', CheckboxType::class, [
                'label' => 'Nouveau devis',
                'help' => 'Recevoir une notification lorsqu’un nouveau devis finalisé est disponible.',
                'required' => false,
            ])
            ->add('notifyOnReviewReceived', CheckboxType::class, [
                'label' => 'Nouvelle facture',
                'help' => 'Recevoir une notification lorsqu’une nouvelle facture est mise à disposition.',
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
