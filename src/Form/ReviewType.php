<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', HiddenType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez sélectionner une note.'),
                    new Assert\Range(
                        min: 0,
                        max: 5,
                        notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}.'
                    ),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaire',
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 2000,
                    'placeholder' => 'Décrivez votre expérience avec ce prestataire (optionnel).',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
