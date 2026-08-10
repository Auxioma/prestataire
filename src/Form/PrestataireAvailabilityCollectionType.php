<?php

namespace App\Form;

use App\Entity\PrestataireProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class PrestataireAvailabilityCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isOnVacation', CheckboxType::class, [
                'label' => 'Afficher "En vacances" à la place de mes horaires',
                'required' => false,
            ])
            ->add('vacationReturnDate', DateType::class, [
                'label' => 'Date de retour',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de retour doit être aujourd’hui ou une date future.',
                    ]),
                ],
            ])
            ->add('availabilities', CollectionType::class, [
                'entry_type' => PrestataireAvailabilityType::class,
                'label' => false,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireProfile::class,
        ]);
    }
}
