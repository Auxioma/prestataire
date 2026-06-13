<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Form;

use App\Entity\ServiceCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HomepageSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', SearchType::class, [
                'label' => 'Que recherchez-vous ?',
                'required' => false,
                'trim' => true,
                'attr' => [
                    'class' => 'form-control search-input',
                    'placeholder' => 'Ex : plomberie, jardinage...',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('subCategory', EntityType::class, [
                'class' => ServiceCategory::class,
                'label' => 'Type de prestation',
                'required' => false,
                'placeholder' => 'Métier, spécialité...',
                'choice_label' => static function (ServiceCategory $category): string {
                    $parent = $category->getParent();

                    return $parent
                        ? \sprintf('%s > %s', $parent->getName(), $category->getName())
                        : $category->getName();
                },
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->leftJoin('c.parent', 'parent')
                        ->addSelect('parent')
                        ->andWhere('c.isActive = :active')
                        ->andWhere('c.parent IS NOT NULL')
                        ->setParameter('active', true)
                        ->orderBy('parent.position', 'ASC')
                        ->addOrderBy('c.position', 'ASC')
                        ->addOrderBy('c.name', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select search-select',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Où ?',
                'required' => false,
                'trim' => true,
                'attr' => [
                    'class' => 'form-control search-input',
                    'placeholder' => 'Ville ou code postal...',
                    'autocomplete' => 'off',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
