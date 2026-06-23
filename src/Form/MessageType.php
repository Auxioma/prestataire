<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Écrivez votre message...',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le message ne peut pas être vide.',
                    ),
                    new Length(
                        min: 2,
                        max: 5000,
                        minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
