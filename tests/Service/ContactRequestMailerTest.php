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

namespace App\Tests\Service;

use App\Website\Dto\ContactRequest;
use App\Website\Enum\ContactHelpCategoryEnum;
use App\Website\Service\ContactRequestMailer;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

final class ContactRequestMailerTest extends TestCase
{
    public function testSendsRequestToAdministratorWithSenderAsReplyTo(): void
    {
        $sentEmail = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $message) use (&$sentEmail): void {
                $sentEmail = $message;
            });

        $contactRequest = new ContactRequest();
        $contactRequest->email = 'visiteur@example.test';
        $contactRequest->category = ContactHelpCategoryEnum::TECHNICAL_SUPPORT;
        $contactRequest->message = 'Je rencontre un problème de connexion à mon compte.';

        (new ContactRequestMailer($mailer, 'admin@example.test'))->send($contactRequest);

        self::assertInstanceOf(TemplatedEmail::class, $sentEmail);
        self::assertSame('admin@example.test', $sentEmail->getTo()[0]->getAddress());
        self::assertSame('visiteur@example.test', $sentEmail->getReplyTo()[0]->getAddress());
        self::assertSame(
            'Demande de contact - Support technique (bug, problème d’affichage, connexion)',
            $sentEmail->getSubject()
        );
        self::assertSame('contact/emails/admin_notification.html.twig', $sentEmail->getHtmlTemplate());
        self::assertSame('contact/emails/admin_notification.txt.twig', $sentEmail->getTextTemplate());
        self::assertSame($contactRequest->message, $sentEmail->getContext()['message']);
    }

    public function testRejectsMissingAdministratorAddress(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $contactRequest = new ContactRequest();
        $contactRequest->email = 'visiteur@example.test';
        $contactRequest->category = ContactHelpCategoryEnum::OTHER;
        $contactRequest->message = 'Voici une demande suffisamment détaillée.';

        $this->expectException(\LogicException::class);

        (new ContactRequestMailer($mailer, null))->send($contactRequest);
    }
}
