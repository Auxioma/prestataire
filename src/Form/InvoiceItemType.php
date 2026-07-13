<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InvoiceItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Intitulé',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'required' => false,
                'scale' => 2,
                'html5' => true,
            ])
            ->add('unitPriceHt', NumberType::class, [
                'label' => 'PU HT',
                'required' => false,
                'scale' => 2,
                'html5' => true,
            ])
            ->add('vatRate', NumberType::class, [
                'label' => 'TVA (%)',
                'required' => false,
                'scale' => 2,
                'html5' => true,
            ])
            ->add('position', IntegerType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'd-none',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceItem::class,
        ]);
    }
}
