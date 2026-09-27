<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Prestataire\Form;

use App\Catalog\Entity\Service;
use App\Catalog\Entity\ServiceCategory;
use App\Catalog\Repository\ServiceCategoryRepository;
use App\Catalog\Repository\ServiceRepository;
use App\Prestataire\Entity\PrestataireService;
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
                'query_builder' => static fn (ServiceCategoryRepository $repo) => $repo->createQueryBuilder('c')
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($formModifier) {
            $formModifier($event->getForm(), null, null);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($formModifier) {
            $data = $event->getData() ?? [];

            $categoryId = isset($data['category']) && '' !== $data['category'] ? (int) $data['category'] : null;
            $subcategoryId = isset($data['subcategory']) && '' !== $data['subcategory'] ? (int) $data['subcategory'] : null;

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
