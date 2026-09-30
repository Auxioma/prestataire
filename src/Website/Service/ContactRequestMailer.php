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

namespace App\Website\Service;

use App\Website\Dto\ContactRequest;
use App\Website\Enum\ContactHelpCategoryEnum;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class ContactRequestMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ?string $adminNotificationEmail,
    ) {
    }

    public function send(ContactRequest $contactRequest): void
    {
        $recipient = mb_trim((string) ($this->adminNotificationEmail ?? ''));
        $senderEmail = mb_trim((string) $contactRequest->email);
        $category = $contactRequest->category;

        if ('' === $recipient) {
            throw new \LogicException('L’adresse de notification administrateur n’est pas configurée.');
        }
        if ('' === $senderEmail || !$category instanceof ContactHelpCategoryEnum) {
            throw new \LogicException('La demande de contact est incomplète.');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
            ->to($recipient)
            ->replyTo($senderEmail)
            ->subject('Demande de contact - '.$category->getLabel())
            ->htmlTemplate('contact/emails/admin_notification.html.twig')
            ->textTemplate('contact/emails/admin_notification.txt.twig')
            ->context([
                'categoryLabel' => $category->getLabel(),
                'message' => $contactRequest->message,
                'senderEmail' => $senderEmail,
                'submittedAt' => new \DateTimeImmutable(),
            ]);

        $this->mailer->send($email);
    }
}
