<?php

namespace App\Form;

use App\Entity\PrestataireAvailability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireAvailabilityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('morningEnabled', CheckboxType::class, [
                'label' => 'Matin ouvert',
                'required' => false,
            ])
            ->add('morningStart', TimeType::class, [
                'label' => 'Début matin',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('morningEnd', TimeType::class, [
                'label' => 'Fin matin',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('afternoonEnabled', CheckboxType::class, [
                'label' => 'Après-midi ouvert',
                'required' => false,
            ])
            ->add('afternoonStart', TimeType::class, [
                'label' => 'Début après-midi',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('afternoonEnd', TimeType::class, [
                'label' => 'Fin après-midi',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireAvailability::class,
        ]);
    }
}
