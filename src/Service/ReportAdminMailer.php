<?php

namespace App\Service;

use App\Entity\Report;
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
        $recipient = trim((string) ($this->adminNotificationEmail ?? ''));

        if ('' === $recipient) {
            return;
        }

        $reporter = $report->getReporter();
        $reporterLabel = trim(sprintf(
            '%s %s',
            $reporter?->getFirstName() ?? '',
            $reporter?->getLastName() ?? ''
        ));

        if ('' === $reporterLabel) {
            $reporterLabel = $reporter?->getEmail() ?? 'Un utilisateur';
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
            ->to($recipient)
            ->subject(sprintf('Nouveau signalement - %s', $report->getContextLabel()))
            ->htmlTemplate('emails/report_admin_notification.html.twig')
            ->context([
                'report' => $report,
                'reporterLabel' => $reporterLabel,
            ]);

        $this->mailer->send($email);
    }
}
