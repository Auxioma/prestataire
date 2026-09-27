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

use App\Prestataire\Entity\PrestataireAppointment;
use App\Prestataire\Enum\PrestataireAppointmentStatusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireAppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du rendez-vous',
                'attr' => [
                    'placeholder' => 'Ex. Intervention chaudière Dupont',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Notes et contexte',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ajoutez ici les détails utiles : accès, matériel, consignes, contact...',
                    'rows' => 5,
                ],
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Début',
                'widget' => 'single_text',
                'model_timezone' => 'Europe/Paris',
                'view_timezone' => 'Europe/Paris',
                'attr' => [
                    'data-prestataire--appointment-form-target' => 'startsAt',
                ],
                'help' => 'Date et heure de début du rendez-vous.',
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => 'Fin',
                'widget' => 'single_text',
                'model_timezone' => 'Europe/Paris',
                'view_timezone' => 'Europe/Paris',
                'attr' => [
                    'data-prestataire--appointment-form-target' => 'endsAt',
                ],
                'help' => 'Date et heure de fin du rendez-vous.',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Confirmé' => PrestataireAppointmentStatusEnum::CONFIRMED,
                    'En attente' => PrestataireAppointmentStatusEnum::PENDING,
                    'Annulé' => PrestataireAppointmentStatusEnum::CANCELLED,
                ],
                'choice_value' => static fn (?PrestataireAppointmentStatusEnum $choice) => $choice?->value,
                'choice_label' => static fn (?PrestataireAppointmentStatusEnum $choice) => match ($choice) {
                    PrestataireAppointmentStatusEnum::CONFIRMED => 'Confirmé',
                    PrestataireAppointmentStatusEnum::PENDING => 'En attente',
                    PrestataireAppointmentStatusEnum::CANCELLED => 'Annulé',
                    default => '',
                },
                'placeholder' => 'Choisir un statut',
                'help' => 'Indique l’état d’avancement du rendez-vous.',
            ])
            ->add('locationLabel', TextType::class, [
                'label' => 'Lieu / adresse courte',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. Chez le client, Atelier, Chantier...',
                    'autocomplete' => 'off',
                ],
                'help' => 'Texte court visible dans le planning et la fiche.',
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
