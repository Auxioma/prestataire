<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Invoice;
use App\Service\InvoiceDocumentResolver;
use App\Service\InvoicePdfGenerator;
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

        if ($resolvedDocument instanceof \App\Service\InvoiceResolvedDocument) {
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
