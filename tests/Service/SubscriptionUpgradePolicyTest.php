<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Subscription\SubscriptionUpgradePolicy;
use PHPUnit\Framework\TestCase;

final class SubscriptionUpgradePolicyTest extends TestCase
{
    private SubscriptionUpgradePolicy $upgradePolicy;

    protected function setUp(): void
    {
        $this->upgradePolicy = new SubscriptionUpgradePolicy();
    }

    public function testReturnsIncludedCreditsWhenRemainingCreditsAreLower(): void
    {
        self::assertSame(360, $this->upgradePolicy->calculateCappedRemainingCredits(120, 360));
        self::assertSame(30, $this->upgradePolicy->calculateCappedRemainingCredits(0, 30));
    }

    public function testPreservesRemainingCreditsWhenAboveIncludedCredits(): void
    {
        self::assertSame(390, $this->upgradePolicy->calculateCappedRemainingCredits(390, 360));
        self::assertSame(100, $this->upgradePolicy->calculateCappedRemainingCredits(100, 60));
    }

    public function testTransfersMonthlyRemainingCreditsOnUpgrade(): void
    {
        self::assertSame(390, $this->upgradePolicy->calculateCappedTransferableRemainingCredits(30, 360));
        self::assertSame(390, $this->upgradePolicy->calculateCappedTransferableRemainingCredits(390, 360));
    }

    public function testCapsAtTwiceIncludedCredits(): void
    {
        self::assertSame(720, $this->upgradePolicy->calculateCappedRemainingCredits(900, 360));
    }
}
