<?php

namespace App\Service\Subscription;

use App\Entity\Subscription\StripeWebhookEvent;
use App\Repository\Subscription\StripeWebhookEventRepository;
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
        $eventId = trim((string) ($event['id'] ?? ''));
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
        $eventId = trim((string) ($event['id'] ?? ''));
        if ('' === $eventId || $this->isAlreadyProcessed($event)) {
            return;
        }

        $webhookEvent = (new StripeWebhookEvent())
            ->setStripeEventId($eventId)
            ->setEventType((string) ($event['type'] ?? 'unknown'))
            ->setPayload($event)
            ->setProcessedAt(new \DateTimeImmutable());

        $this->entityManager->persist($webhookEvent);
        $this->entityManager->flush();
    }
}
