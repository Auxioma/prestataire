<?php

namespace App\Enum;

enum InvoiceSourceTypeEnum: string
{
    case GENERATED_FROM_QUOTE = 'generated_from_quote';
    case MANUAL_FROM_EXTERNAL_QUOTE = 'manual_from_external_quote';
    case EXTERNAL_IMPORT = 'external_import';

    public function getLabel(): string
    {
        return match ($this) {
            self::GENERATED_FROM_QUOTE => 'Facture générée depuis le devis',
            self::MANUAL_FROM_EXTERNAL_QUOTE => 'Facture saisie manuellement depuis un devis PDF importé',
            self::EXTERNAL_IMPORT => 'Facture PDF importée par le prestataire',
        };
    }

    public function isExternalImport(): bool
    {
        return $this === self::EXTERNAL_IMPORT;
    }
}
