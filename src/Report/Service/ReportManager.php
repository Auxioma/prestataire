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

namespace App\Report\Service;

use App\Account\Entity\ClientProfile;
use App\Account\Entity\User;
use App\Messaging\Entity\Conversation;
use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\Entity\QuoteRequest;
use App\Report\Entity\Report;
use App\Report\Enum\ReportStatusEnum;
use App\Review\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;

final class ReportManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReportAdminMailer $reportAdminMailer,
    ) {
    }

    public function createQuoteRequestReport(User $reporter, QuoteRequest $quoteRequest, Report $report): Report
    {
        $reportedUser = null;

        if ($reporter->getClientProfile() instanceof ClientProfile) {
            if ($quoteRequest->getClient()?->getId() !== $reporter->getClientProfile()?->getId()) {
                throw new \DomainException('Vous ne pouvez pas signaler cette demande.');
            }

            $reportedUser = $quoteRequest->getPrestataire()?->getAccount();
        } elseif ($reporter->getPrestataireProfile() instanceof PrestataireProfile) {
            if ($quoteRequest->getPrestataire()?->getId() !== $reporter->getPrestataireProfile()?->getId()) {
                throw new \DomainException('Vous ne pouvez pas signaler cette demande.');
            }

            $reportedUser = $quoteRequest->getClient()?->getAccount();
        } else {
            throw new \DomainException('Seuls les utilisateurs liés à cette demande peuvent la signaler.');
        }

        if (!$reportedUser instanceof User) {
            throw new \DomainException('Aucun utilisateur ne peut être rattaché à ce signalement.');
        }

        $report
            ->setReporter($reporter)
            ->setReportedUser($reportedUser)
            ->setQuoteRequest($quoteRequest)
            ->setConversation(null)
            ->setReview(null)
            ->setStatus(ReportStatusEnum::NEW)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($report);
        $this->entityManager->flush();
        $this->reportAdminMailer->sendNewReportNotification($report);

        return $report;
    }

    public function createConversationReport(User $reporter, Conversation $conversation, Report $report): Report
    {
        $reportedUser = null;

        if ($reporter->getClientProfile() instanceof ClientProfile) {
            if ($conversation->getClient()?->getId() !== $reporter->getClientProfile()?->getId()) {
                throw new \DomainException('Vous ne pouvez pas signaler cette conversation.');
            }

            $reportedUser = $conversation->getPrestataire()?->getAccount();
        } elseif ($reporter->getPrestataireProfile() instanceof PrestataireProfile) {
            if ($conversation->getPrestataire()?->getId() !== $reporter->getPrestataireProfile()?->getId()) {
                throw new \DomainException('Vous ne pouvez pas signaler cette conversation.');
            }

            $reportedUser = $conversation->getClient()?->getAccount();
        } else {
            throw new \DomainException('Seuls les participants à cette conversation peuvent la signaler.');
        }

        if (!$reportedUser instanceof User) {
            throw new \DomainException('Aucun utilisateur ne peut être rattaché à ce signalement.');
        }

        $report
            ->setReporter($reporter)
            ->setReportedUser($reportedUser)
            ->setConversation($conversation)
            ->setQuoteRequest($conversation->getQuoteRequest())
            ->setReview(null)
            ->setStatus(ReportStatusEnum::NEW)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($report);
        $this->entityManager->flush();
        $this->reportAdminMailer->sendNewReportNotification($report);

        return $report;
    }

    public function createReviewReport(User $reporter, Review $review, Report $report): Report
    {
        if (
            !$reporter->getPrestataireProfile() instanceof PrestataireProfile
            || $review->getPrestataireProfile()?->getId() !== $reporter->getPrestataireProfile()?->getId()
        ) {
            throw new \DomainException('Vous ne pouvez pas signaler cet avis.');
        }

        $reportedUser = $review->getClientProfile()?->getAccount();
        if (!$reportedUser instanceof User) {
            throw new \DomainException('Impossible d’identifier l’auteur de cet avis.');
        }

        $report
            ->setReporter($reporter)
            ->setReportedUser($reportedUser)
            ->setReview($review)
            ->setQuoteRequest($review->getQuoteRequest())
            ->setConversation($review->getQuoteRequest()?->getConversation())
            ->setStatus(ReportStatusEnum::NEW)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($report);
        $this->entityManager->flush();
        $this->reportAdminMailer->sendNewReportNotification($report);

        return $report;
    }
}
