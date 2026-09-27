<?php

declare(strict_types=1);

namespace App\Invoice\Controller;

use App\Invoice\Entity\Invoice;
use App\Invoice\Service\InvoiceDocumentResolver;
use App\Invoice\Service\InvoicePdfGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

abstract class AbstractInvoiceController extends AbstractController
{
    protected function createPdfResponse(
        Invoice $invoice,
        InvoiceDocumentResolver $documentResolver,
        InvoicePdfGenerator $pdfGenerator,
    ): Response {
        $resolvedDocument = $documentResolver->resolve($invoice);

        if ($resolvedDocument instanceof \App\Invoice\Service\InvoiceResolvedDocument) {
            $response = new BinaryFileResponse($resolvedDocument->getFilesystemPath());
            $response->headers->set('Content-Type', $resolvedDocument->getMimeType());
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $resolvedDocument->getDownloadFilename()
            );

            return $response;
        }

        return new Response(
            $pdfGenerator->generatePdfOutput($invoice),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'inline; filename="%s.pdf"',
                    $invoice->getInvoiceNumber() ?: 'facture'
                ),
            ]
        );
    }
}
