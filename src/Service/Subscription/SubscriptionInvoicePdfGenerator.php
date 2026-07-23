<?php

namespace App\Service\Subscription;

use App\Entity\Subscription\SubscriptionInvoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class SubscriptionInvoicePdfGenerator
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function generatePdfOutput(
        SubscriptionInvoice $invoice,
        string $template = 'subscription/invoice_pdf.html.twig',
    ): string {
        $html = $this->twig->render($template, [
            'invoice' => $invoice,
            'subscription' => $invoice->getSubscription(),
            'prestataire' => $invoice->getSubscription()?->getPrestataireProfile(),
            'customer' => $invoice->getSubscription()?->getCustomer(),
            'platform' => [
                'name' => 'TrouveMoi',
                'legal_name' => 'TrouveMoi',
                'email' => 'contact@trouvemoi.fr',
                'website' => 'www.trouvemoi.fr',
            ],
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $this->applyDocumentMetadata($dompdf, $invoice);
        $dompdf->render();

        return $dompdf->output();
    }

    private function applyDocumentMetadata(Dompdf $dompdf, SubscriptionInvoice $invoice): void
    {
        $invoiceNumber = $invoice->getInvoiceNumber() ?: ('ABO-' . ($invoice->getId() ?? 'draft'));
        $prestataire = $invoice->getSubscription()?->getPrestataireProfile();
        $sellerName = $prestataire?->getCompanyName() ?: $prestataire?->getLegalName() ?: 'Prestataire';

        $dompdf->addInfo('Title', sprintf('Facture abonnement %s', $invoiceNumber));
        $dompdf->addInfo('Author', 'TrouveMoi');
        $dompdf->addInfo('Creator', 'TrouveMoi');
        $dompdf->addInfo('Subject', sprintf('Facture d’abonnement %s pour %s', $invoiceNumber, $sellerName));
        $dompdf->addInfo('Keywords', 'abonnement, facture, trouvemoi');
    }
}
