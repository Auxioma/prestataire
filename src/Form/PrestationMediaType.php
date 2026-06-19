<?php

namespace App\Form;

use App\Entity\PrestationMedia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestationMediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', FileType::class, [
                'label' => 'Photo',
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => false,
            ])
            ->add('altText', TextType::class, [
                'label' => 'Texte alternatif',
                'required' => false,
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('delete', CheckboxType::class, [
                'label' => 'Supprimer cette photo',
                'required' => false,
                'mapped' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $media = $event->getData();
            $form = $event->getForm();

            if (!$media instanceof PrestationMedia || !$media->getId()) {
                $form->remove('delete');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestationMedia::class,
        ]);
    }
}