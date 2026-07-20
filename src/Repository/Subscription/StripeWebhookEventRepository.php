<?php

namespace App\Repository\Subscription;

use App\Entity\Subscription\StripeWebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StripeWebhookEvent>
 */
class StripeWebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StripeWebhookEvent::class);
    }

    public function findOneByStripeEventId(string $stripeEventId): ?StripeWebhookEvent
    {
        return $this->findOneBy(['stripeEventId' => $stripeEventId]);
    }
}
