<?php

namespace App\Form;

use App\Entity\PrestataireService;
use App\Entity\Service;
use App\Entity\ServiceCategory;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireServiceCreateType extends AbstractType
{
    public function __construct(
        private ServiceCategoryRepository $categoryRepository,
        private ServiceRepository $serviceRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => ServiceCategory::class,
                'choice_label' => 'name',
                'mapped' => false,
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'query_builder' => fn(ServiceCategoryRepository $repo) => $repo->createQueryBuilder('c')
                    ->andWhere('c.parent IS NULL')
                    ->andWhere('c.isActive = true')
                    ->orderBy('c.position', 'ASC'),
            ])
            ->add('subcategory', EntityType::class, [
                'class' => ServiceCategory::class,
                'choice_label' => 'name',
                'mapped' => false,
                'label' => 'Sous-catégorie',
                'placeholder' => 'Choisir une sous-catégorie',
                'choices' => [],
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'name',
                'label' => 'Service',
                'placeholder' => 'Choisir un service',
                'choices' => [],
            ]);

        $formModifier = function ($form, ?int $categoryId, ?int $subcategoryId): void {
            $subcategories = [];
            $services = [];

            if ($categoryId) {
                $subcategories = $this->categoryRepository->findBy([
                    'parent' => $categoryId,
                    'isActive' => true,
                ], ['position' => 'ASC']);
            }

            if ($subcategoryId) {
                $services = $this->serviceRepository->findBy([
                    'category' => $subcategoryId,
                    'isActive' => true,
                ], ['position' => 'ASC']);
            }

            $form->add('subcategory', EntityType::class, [
                'class' => ServiceCategory::class,
                'choice_label' => 'name',
                'mapped' => false,
                'label' => 'Sous-catégorie',
                'placeholder' => 'Choisir une sous-catégorie',
                'choices' => $subcategories,
            ]);

            $form->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'name',
                'label' => 'Service',
                'placeholder' => 'Choisir un service',
                'choices' => $services,
            ]);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($formModifier) {
            $formModifier($event->getForm(), null, null);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier) {
            $data = $event->getData() ?? [];

            $categoryId = isset($data['category']) && $data['category'] !== '' ? (int) $data['category'] : null;
            $subcategoryId = isset($data['subcategory']) && $data['subcategory'] !== '' ? (int) $data['subcategory'] : null;

            $formModifier($event->getForm(), $categoryId, $subcategoryId);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireService::class,
        ]);
    }
}