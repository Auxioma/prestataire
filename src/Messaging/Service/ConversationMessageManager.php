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

namespace App\Messaging\Service;

use App\Account\Entity\User;
use App\Messaging\Entity\Message;
use App\Messaging\Entity\MessageAttachment;
use App\Messaging\Enum\MessageTypeEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ConversationMessageManager
{
    /**
     * @param array<UploadedFile>|null $uploadedFiles
     */
    public function prepareMessage(
        Message $message,
        User $author,
        ?string $content,
        ?array $uploadedFiles,
        MessageTypeEnum $type = MessageTypeEnum::USER,
    ): bool {
        $trimmedContent = \is_string($content) ? mb_trim($content) : '';
        $uploadedFiles = \is_array($uploadedFiles) ? $uploadedFiles : [];

        $message->setAuthor($author);
        $message->setType($type);

        if ('' === $trimmedContent && 0 === \count($uploadedFiles)) {
            return false;
        }

        $message->setContent('' !== $trimmedContent ? $trimmedContent : '');

        $position = 0;

        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $attachment = new MessageAttachment();
            $attachment->setMessage($message);
            $attachment->setFile($uploadedFile);
            $attachment->setPosition($position++);

            $message->addAttachment($attachment);
        }

        return true;
    }
}
