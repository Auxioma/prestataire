<?php

namespace App\Form;

use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\QuoteRequest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PrestataireProfile|null $prestataire */
        $prestataire = $options['prestataire'];
        $lockedPrestation = $options['locked_prestation'];

        $prestations = [];

        if ($prestataire) {
            $prestations = $prestataire
                ->getPrestataireServices()
                ->filter(static fn (PrestataireService $ps): bool => $ps->isActive())
                ->toArray();
        }

        if (!$lockedPrestation) {
            $builder->add('prestation', EntityType::class, [
                'class' => PrestataireService::class,
                'label' => 'Service concerné',
                'choices' => $prestations,
                'choice_label' => static function (PrestataireService $prestation): string {
                    return $prestation->getService()?->getName() ?? ('Prestation #' . $prestation->getId());
                },
                'placeholder' => 'Choisir un service',
                'required' => true,
            ]);
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 6,
                ],
            ])
            ->add('desiredDate', DateType::class, [
                'label' => 'Date souhaitée',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('budgetAmount', MoneyType::class, [
                'label' => 'Budget estimé',
                'currency' => 'EUR',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteRequest::class,
            'prestataire' => null,
            'locked_prestation' => false,
        ]);

        $resolver->setAllowedTypes('prestataire', ['null', PrestataireProfile::class]);
        $resolver->setAllowedTypes('locked_prestation', ['bool']);
    }
}