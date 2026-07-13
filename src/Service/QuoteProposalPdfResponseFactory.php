<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class QuoteProposalPdfResponseFactory
{
    public function createInlineResponse(QuoteProposalResolvedDocument $document): BinaryFileResponse
    {
        $path = $document->getFilesystemPath();

        if ($path === null || !is_file($path)) {
            throw new \RuntimeException('Le fichier PDF demandé est introuvable.');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $document->getMimeType() ?: 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $document->getDownloadFilename()
        );

        return $response;
    }
}
