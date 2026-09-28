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

use App\Report\Entity\Report;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class ReportAdminMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ?string $adminNotificationEmail,
    ) {
    }

    public function sendNewReportNotification(Report $report): void
    {
        $recipient = mb_trim((string) ($this->adminNotificationEmail ?? ''));

        if ('' === $recipient) {
            return;
        }

        $reporter = $report->getReporter();
        $reporterLabel = mb_trim(\sprintf(
            '%s %s',
            $reporter?->getFirstName() ?? '',
            $reporter?->getLastName() ?? ''
        ));

        if ('' === $reporterLabel) {
            $reporterLabel = $reporter?->getEmail() ?? 'Un utilisateur';
        }

        $email = new TemplatedEmail()
            ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
            ->to($recipient)
            ->subject(\sprintf('Nouveau signalement - %s', $report->getContextLabel()))
            ->htmlTemplate('report/emails/report_admin_notification.html.twig')
            ->context([
                'report' => $report,
                'reporterLabel' => $reporterLabel,
            ]);

        $this->mailer->send($email);
    }
}
