<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Enum\InvoiceSourceTypeEnum;

final class InvoiceTotalsCalculator
{
    public function recalculate(Invoice $invoice): Invoice
    {
        if ($invoice->getSourceType() === InvoiceSourceTypeEnum::EXTERNAL_IMPORT) {
            $invoice
                ->setSubtotalHt('0.00')
                ->setTaxAmount('0.00')
                ->setTotalTtc('0.00');

            return $invoice;
        }

        $subtotal = '0.00';
        $taxAmount = '0.00';

        foreach ($invoice->getItems() as $item) {
            $lineTotalHt = $this->calculateItemTotalHt($item);
            $item->setTotalHt($lineTotalHt);

            $subtotal = bcadd($subtotal, $lineTotalHt, 2);

            $vatRate = $this->normalizeDecimal($item->getVatRate(), '0.00');
            $lineTax = bcmul($lineTotalHt, bcdiv($vatRate, '100', 4), 2);
            $taxAmount = bcadd($taxAmount, $lineTax, 2);
        }

        $invoice
            ->setSubtotalHt($subtotal)
            ->setTaxAmount($taxAmount)
            ->setTotalTtc(bcadd($subtotal, $taxAmount, 2));

        return $invoice;
    }

    public function calculateItemTotalHt(InvoiceItem $item): string
    {
        $quantity = $this->normalizeDecimal($item->getQuantity(), '0.00');
        $unitPriceHt = $this->normalizeDecimal($item->getUnitPriceHt(), '0.00');

        return bcmul($quantity, $unitPriceHt, 2);
    }

    private function normalizeDecimal(?string $value, string $default): string
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        return number_format((float) str_replace(',', '.', $value), 2, '.', '');
    }
}
