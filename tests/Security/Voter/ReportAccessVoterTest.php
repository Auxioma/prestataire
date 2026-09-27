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

namespace App\Tests\Security\Voter;

use App\Account\Entity\ClientProfile;
use App\Account\Entity\User;
use App\Messaging\Entity\Conversation;
use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\Entity\QuoteRequest;
use App\Report\Security\Voter\ReportAccessVoter;
use App\Review\Entity\Review;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class ReportAccessVoterTest extends TestCase
{
    public function testClientCanAccessOwnQuoteRequestReportForm(): void
    {
        $clientUser = new User();
        $clientProfile = new ClientProfile();
        $clientProfile->setAccount($clientUser);
        $clientUser->setClientProfile($clientProfile);

        $quoteRequest = new QuoteRequest()
            ->setClient($clientProfile);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote($clientUser, $quoteRequest)
        );
    }

    public function testPrestataireCanAccessOwnConversationReportForm(): void
    {
        $prestataireUser = new User();
        $prestataireProfile = new PrestataireProfile();
        $prestataireProfile->setAccount($prestataireUser);
        $prestataireProfile->setCompanyName('Acme');
        $prestataireProfile->setSlug('acme');
        $prestataireUser->setPrestataireProfile($prestataireProfile);

        $conversation = new Conversation()
            ->setPrestataire($prestataireProfile);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote($prestataireUser, $conversation)
        );
    }

    public function testPrestataireCannotAccessAnotherPrestataireReviewReportForm(): void
    {
        $prestataireUser = new User();
        $prestataireProfile = new PrestataireProfile();
        $prestataireProfile->setAccount($prestataireUser);
        $prestataireProfile->setCompanyName('Acme');
        $prestataireProfile->setSlug('acme');
        $prestataireUser->setPrestataireProfile($prestataireProfile);

        $otherPrestataireProfile = new PrestataireProfile();
        $otherPrestataireProfile->setCompanyName('Other');
        $otherPrestataireProfile->setSlug('other');

        $review = new Review()
            ->setPrestataireProfile($otherPrestataireProfile);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote($prestataireUser, $review)
        );
    }

    private function vote(User $user, mixed $subject): int
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voter = new ReportAccessVoter();

        return $voter->vote($token, $subject, [ReportAccessVoter::ACCESS]);
    }
}
