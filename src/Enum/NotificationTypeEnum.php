<?php

namespace App\Enum;

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
