<?php

namespace App\Form;

use App\Entity\Report;
use App\Enum\ReportReasonEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'Motif du signalement',
                'choices' => [
                    ReportReasonEnum::INAPPROPRIATE_BEHAVIOR->getLabel() => ReportReasonEnum::INAPPROPRIATE_BEHAVIOR,
                    ReportReasonEnum::HARASSMENT->getLabel() => ReportReasonEnum::HARASSMENT,
                    ReportReasonEnum::SCAM_OR_FRAUD->getLabel() => ReportReasonEnum::SCAM_OR_FRAUD,
                    ReportReasonEnum::FAKE_OR_MISLEADING_CONTENT->getLabel() => ReportReasonEnum::FAKE_OR_MISLEADING_CONTENT,
                    ReportReasonEnum::SPAM->getLabel() => ReportReasonEnum::SPAM,
                    ReportReasonEnum::OTHER->getLabel() => ReportReasonEnum::OTHER,
                ],
                'placeholder' => 'Choisir un motif',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Précisions complémentaires',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'maxlength' => 5000,
                    'placeholder' => 'Décrivez brièvement les faits à signaler.',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}
