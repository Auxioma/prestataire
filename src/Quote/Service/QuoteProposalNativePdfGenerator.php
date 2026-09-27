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

namespace App\Quote\Service;

use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\Entity\QuoteProposal;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final class QuoteProposalNativePdfGenerator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UploaderHelper $uploaderHelper,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function generatePdfOutput(QuoteProposal $proposal, string $template): string
    {
        $html = $this->twig->render($template, [
            'proposal' => $proposal,
            'quoteRequest' => $proposal->getQuoteRequest(),
            'prestataireLogoUrl' => $this->resolvePrestataireLogoUrl($proposal),
            'prestataireSignatureUrl' => $this->resolvePrestataireSignatureUrl($proposal),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function resolvePrestataireLogoUrl(QuoteProposal $proposal): ?string
    {
        $prestataire = $proposal->getQuoteRequest()?->getPrestataire();

        if (!$prestataire instanceof PrestataireProfile || !$prestataire->getLogo()) {
            return null;
        }

        $relativePath = $this->uploaderHelper->asset($prestataire, 'logoFile');
        $request = $this->requestStack->getCurrentRequest();

        if (null === $relativePath || !$request instanceof Request) {
            return null;
        }

        return $request->getSchemeAndHttpHost().$relativePath;
    }

    private function resolvePrestataireSignatureUrl(QuoteProposal $proposal): ?string
    {
        $prestataire = $proposal->getQuoteRequest()?->getPrestataire();

        if (!$prestataire instanceof PrestataireProfile || !$prestataire->getSignatureImage()) {
            return null;
        }

        $relativePath = $this->uploaderHelper->asset($prestataire, 'signatureImageFile');
        $request = $this->requestStack->getCurrentRequest();

        if (null === $relativePath || !$request instanceof Request) {
            return null;
        }

        return $request->getSchemeAndHttpHost().$relativePath;
    }
}
