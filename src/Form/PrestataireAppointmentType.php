<?php

namespace App\Form;

use App\Entity\PrestataireAppointment;
use App\Enum\PrestataireAppointmentStatusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireAppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Début',
                'widget' => 'single_text',
                'attr' => [
                    'data-appointment-form-target' => 'startsAt',
                ],
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => 'Fin',
                'widget' => 'single_text',
                'attr' => [
                    'data-appointment-form-target' => 'endsAt',
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => PrestataireAppointmentStatusEnum::class,
                'label' => 'Statut',
                'choice_label' => static fn(PrestataireAppointmentStatusEnum $choice) => $choice->getLabel(),
            ])
            ->add('locationLabel', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('isAllDay', CheckboxType::class, [
                'label' => 'Toute la journée',
                'required' => false,
                'attr' => [
                    'data-appointment-form-target' => 'allDay',
                    'data-action' => 'change->appointment-form#toggleAllDay',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireAppointment::class,
        ]);
    }
}
