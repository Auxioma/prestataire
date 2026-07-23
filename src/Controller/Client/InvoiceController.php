<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Controller\AbstractInvoiceController;
use App\Entity\Invoice;
use App\Entity\User;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceDocumentResolver;
use App\Service\InvoicePdfGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InvoiceController extends AbstractInvoiceController
{
    #[Route('/demandes-de-devis/factures/{publicReference}', name: 'app_quote_request_invoice_show', methods: ['GET'])]
    public function showForClient(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
    ): Response {
        $invoice = $this->getClientInvoice($publicReference, $invoiceRepository);

        if (!$invoice instanceof Invoice) {
            throw $this->createNotFoundException('Facture introuvable.');
        }

        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
            'proposal' => $invoice->getQuoteProposal(),
            'viewerContext' => 'client',
        ]);
    }

    #[Route('/demandes-de-devis/factures/{publicReference}/pdf', name: 'app_quote_request_invoice_pdf', methods: ['GET'])]
    public function showPdfForClient(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
        InvoiceDocumentResolver $documentResolver,
        InvoicePdfGenerator $pdfGenerator,
    ): Response {
        $invoice = $this->getClientInvoice($publicReference, $invoiceRepository);

        return $this->createPdfResponse($invoice, $documentResolver, $pdfGenerator);
    }

    private function getClientInvoice(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
    ): Invoice {
        $user = $this->getCurrentClientUser();
        $invoice = $invoiceRepository->findOneVisibleForClientByProposalReference($publicReference, $user->getClientProfile());

        if (!$invoice instanceof Invoice) {
            throw $this->createNotFoundException('Facture introuvable.');
        }

        return $invoice;
    }

    private function getCurrentClientUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        return $user;
    }
}
