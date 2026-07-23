<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PrestataireProfile;
use App\Entity\PrestataireRevenueEntry;
use App\Entity\PrestataireService;
use App\Repository\PrestataireServiceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PrestataireRevenueEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PrestataireProfile $prestataire */
        $prestataire = $options['prestataire'];

        $builder
            ->add('label', TextType::class, [
                'label' => 'Libellé',
            ])
            ->add('prestataireService', EntityType::class, [
                'class' => PrestataireService::class,
                'label' => 'Prestation liée',
                'required' => false,
                'placeholder' => 'Aucune prestation liée',
                'choice_label' => static fn (PrestataireService $service): string => $service->getDisplayTitle(),
                'query_builder' => static fn (PrestataireServiceRepository $repository) => $repository->createQueryBuilder('ps')
                    ->andWhere('ps.prestataire = :prestataire')
                    ->setParameter('prestataire', $prestataire)
                    ->orderBy('ps.title', 'ASC')
                    ->addOrderBy('ps.id', 'ASC'),
            ])
            ->add('serviceLabel', TextType::class, [
                'label' => 'Libellé service libre',
                'required' => false,
            ])
            ->add('clientName', TextType::class, [
                'label' => 'Client',
                'required' => false,
            ])
            ->add('invoiceNumber', TextType::class, [
                'label' => 'Numéro de facture',
                'required' => false,
            ])
            ->add('issuedAt', DateType::class, [
                'label' => 'Date de facturation',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('paidAt', DateType::class, [
                'label' => 'Date de paiement (optionnel)',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('subtotalHt', NumberType::class, [
                'label' => 'Montant HT',
                'scale' => 2,
            ])
            ->add('taxAmount', NumberType::class, [
                'label' => 'TVA',
                'scale' => 2,
            ])
            ->add('totalTtc', NumberType::class, [
                'label' => 'Montant TTC',
                'scale' => 2,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireRevenueEntry::class,
        ]);
        $resolver->setRequired('prestataire');
        $resolver->setAllowedTypes('prestataire', PrestataireProfile::class);
    }
}
