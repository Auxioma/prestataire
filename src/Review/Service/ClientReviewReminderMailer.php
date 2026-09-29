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

namespace App\Review\Service;

use App\Account\Entity\User;
use App\Invoice\Entity\Invoice;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ClientReviewReminderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ReviewManager $reviewManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendIfEligible(Invoice $invoice): void
    {
        $client = $invoice->getClient();
        $quoteRequest = $invoice->getQuoteRequest();
        $prestataire = $invoice->getPrestataire();
        $clientUser = $client?->getAccount();
        $proposalReference = $invoice->getQuoteProposal()?->getPublicReference();
        $quoteRequestSlug = $quoteRequest?->getSlug();

        if (
            null === $client
            || !$clientUser instanceof User
            || null === $quoteRequest
            || null === $prestataire
            || null === $proposalReference
            || null === $quoteRequestSlug
            || !$this->reviewManager->canClientReviewQuoteRequest($client, $quoteRequest)
        ) {
            return;
        }

        $recipient = mb_trim((string) $clientUser->getEmail());

        if ('' === $recipient) {
            return;
        }

        $prestataireName = mb_trim((string) ($prestataire->getCompanyName() ?: $prestataire->getLegalName() ?: 'votre prestataire'));
        $clientFirstName = mb_trim((string) ($clientUser->getFirstName() ?? ''));

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
            ->to($recipient)
            ->subject('Votre facture est disponible - laissez votre avis')
            ->htmlTemplate('review/emails/client_review_reminder.html.twig')
            ->context([
                'clientFirstName' => $clientFirstName,
                'prestataireName' => $prestataireName,
                'quoteRequestTitle' => $quoteRequest->getTitle(),
                'invoiceNumber' => $invoice->getInvoiceNumber(),
                'invoiceUrl' => $this->urlGenerator->generate('app_quote_request_invoice_show', [
                    'publicReference' => $proposalReference,
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'reviewUrl' => $this->urlGenerator->generate('app_review_create', [
                    'quoteRequestSlug' => $quoteRequestSlug,
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning('Unable to send client review reminder email after invoice issuance.', [
                'invoiceId' => $invoice->getId(),
                'quoteRequestId' => $quoteRequest->getId(),
                'clientUserId' => $clientUser->getId(),
                'email' => $recipient,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
