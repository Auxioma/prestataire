<?php

namespace App\Security\Voter;

use App\Entity\Conversation;
use App\Entity\QuoteRequest;
use App\Entity\Review;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ReportAccessVoter extends Voter
{
    public const ACCESS = 'REPORT_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (self::ACCESS !== $attribute) {
            return false;
        }

        return $subject instanceof QuoteRequest
            || $subject instanceof Conversation
            || $subject instanceof Review;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match (true) {
            $subject instanceof QuoteRequest => $this->canAccessQuoteRequest($user, $subject),
            $subject instanceof Conversation => $this->canAccessConversation($user, $subject),
            $subject instanceof Review => $this->canAccessReview($user, $subject),
            default => false,
        };
    }

    private function canAccessQuoteRequest(User $user, QuoteRequest $quoteRequest): bool
    {
        $clientProfile = $user->getClientProfile();
        if ($this->sameEntity($quoteRequest->getClient(), $clientProfile)) {
            return true;
        }

        $prestataireProfile = $user->getPrestataireProfile();
        if ($this->sameEntity($quoteRequest->getPrestataire(), $prestataireProfile)) {
            return true;
        }

        return false;
    }

    private function canAccessConversation(User $user, Conversation $conversation): bool
    {
        $clientProfile = $user->getClientProfile();
        if ($this->sameEntity($conversation->getClient(), $clientProfile)) {
            return true;
        }

        $prestataireProfile = $user->getPrestataireProfile();
        if ($this->sameEntity($conversation->getPrestataire(), $prestataireProfile)) {
            return true;
        }

        return false;
    }

    private function canAccessReview(User $user, Review $review): bool
    {
        $prestataireProfile = $user->getPrestataireProfile();

        return $this->sameEntity($review->getPrestataireProfile(), $prestataireProfile);
    }

    private function sameEntity(object|null $left, object|null $right): bool
    {
        if (null === $left || null === $right) {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        if (!method_exists($left, 'getId') || !method_exists($right, 'getId')) {
            return false;
        }

        $leftId = $left->getId();
        $rightId = $right->getId();

        return null !== $leftId && null !== $rightId && $leftId === $rightId;
    }
}
