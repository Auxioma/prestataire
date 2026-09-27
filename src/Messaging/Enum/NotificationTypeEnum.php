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

namespace App\Messaging\Enum;

enum NotificationTypeEnum: string
{
    case MESSAGE_RECEIVED = 'message_received';
    case QUOTE_REQUEST_RECEIVED = 'quote_request_received';
    case QUOTE_REQUEST_ACCEPTED = 'quote_request_accepted';
    case QUOTE_REQUEST_DENIED = 'quote_request_denied';
    case QUOTE_PROPOSAL_RECEIVED = 'quote_proposal_received';
    case INVOICE_RECEIVED = 'invoice_received';
    case DOCUMENT_SENT = 'document_sent';
    case REVIEW_RECEIVED = 'review_received';

    public function getLabel(): string
    {
        return match ($this) {
            self::MESSAGE_RECEIVED => 'Nouveau message',
            self::QUOTE_REQUEST_RECEIVED => 'Nouvelle demande de prestation',
            self::QUOTE_REQUEST_ACCEPTED => 'Demande acceptée',
            self::QUOTE_REQUEST_DENIED => 'Demande refusée',
            self::QUOTE_PROPOSAL_RECEIVED => 'Nouveau devis reçu',
            self::INVOICE_RECEIVED => 'Nouvelle facture reçue',
            self::DOCUMENT_SENT => 'Document envoyé',
            self::REVIEW_RECEIVED => 'Nouvel avis',
        };
    }
}
