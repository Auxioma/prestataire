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

namespace App\Subscription\Service;

use App\Subscription\Entity\StripeWebhookEvent;
use App\Subscription\Repository\StripeWebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StripeWebhookEventRecorder
{
    public function __construct(
        private readonly StripeWebhookEventRepository $stripeWebhookEventRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function isAlreadyProcessed(array $event): bool
    {
        $eventId = mb_trim((string) ($event['id'] ?? ''));
        if ('' === $eventId) {
            return false;
        }

        return $this->stripeWebhookEventRepository->findOneByStripeEventId($eventId) instanceof StripeWebhookEvent;
    }

    /**
     * @param array<string, mixed> $event
     */
    public function recordProcessed(array $event): void
    {
        $eventId = mb_trim((string) ($event['id'] ?? ''));
        if ('' === $eventId || $this->isAlreadyProcessed($event)) {
            return;
        }

        $webhookEvent = new StripeWebhookEvent()
            ->setStripeEventId($eventId)
            ->setEventType((string) ($event['type'] ?? 'unknown'))
            ->setPayload($event)
            ->setProcessedAt(new \DateTimeImmutable());

        $this->entityManager->persist($webhookEvent);
        $this->entityManager->flush();
    }
}
