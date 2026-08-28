<?php

namespace App\Tests\Security\Voter;

use App\Entity\ClientProfile;
use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteRequest;
use App\Entity\Review;
use App\Entity\User;
use App\Security\Voter\ReportAccessVoter;
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

        $quoteRequest = (new QuoteRequest())
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

        $conversation = (new Conversation())
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

        $review = (new Review())
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
