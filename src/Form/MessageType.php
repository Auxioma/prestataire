<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => false,
            'attr' => [
                'rows' => 4,
                'placeholder' => 'Écrivez votre message...',
            ],
            'constraints' => [
                new NotBlank(
                    message: 'Veuillez saisir un message.'
                ),
                new Length(
                    max: 2000,
                    maxMessage: 'Votre message ne doit pas dépasser {{ limit }} caractères.'
                ),
                new Regex(
                    pattern: '/^(?!.*(?:[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})).*$/iu',
                    message: 'Le partage d’adresse email n’est pas autorisé dans la messagerie.'
                ),
                new Regex(
                    pattern: '/^(?!.*(?:(?:\+33|0033)\s?[1-9]|0\s?[1-9])(?:[\s.\-]?\d{2}){4}).*$/u',
                    message: 'Le partage de numéro de téléphone n’est pas autorisé dans la messagerie.'
                ),
                new Regex(
                    pattern: '/^(?!.*\b(?:https?:\/\/|www\.)\S+\b).*$/iu',
                    message: 'Le partage de lien externe n’est pas autorisé dans la messagerie.'
                ),
                new Regex(
                    pattern: '/^(?!.*\b(?:whatsapp|telegram|discord|snapchat|instagram|insta|facebook|messenger|mail|email|e-mail|téléphone|telephone|portable|numéro|numero)\b).*$/iu',
                    message: 'Le partage de coordonnées directes n’est pas autorisé dans la messagerie.'
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