<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ConversationMessageManager
{
    /**
     * @param array<UploadedFile>|null $uploadedFiles
     */
    public function prepareMessage(
        Message $message,
        User $author,
        string|null $content,
        array|null $uploadedFiles,
        MessageTypeEnum $type = MessageTypeEnum::USER,
    ): bool {
        $trimmedContent = is_string($content) ? trim($content) : '';
        $uploadedFiles = is_array($uploadedFiles) ? $uploadedFiles : [];

        $message->setAuthor($author);
        $message->setType($type);

        if ($trimmedContent === '' && count($uploadedFiles) === 0) {
            return false;
        }

        $message->setContent($trimmedContent !== '' ? $trimmedContent : '');

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
