<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\QuoteProposal;
use App\Entity\User;
use setasign\Fpdi\Fpdi;

final class QuoteProposalAcceptancePdfGenerator
{
    public function __construct(
        private readonly QuoteProposalDocumentResolver $documentResolver,
        private readonly string $projectDir,
    ) {}

    public function generateFromExternalPdf(QuoteProposal $proposal, ?User $acceptedBy): void
    {
        $sourcePath = $this->documentResolver->getExternalPdfPath($proposal);

        if ($sourcePath === null || !is_file($sourcePath)) {
            throw new \RuntimeException('Impossible de générer le PDF accepté : PDF externe introuvable.');
        }

        $targetDirectory = $this->projectDir . '/var/uploads/quote-proposals/accepted';

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException('Impossible de créer le répertoire de stockage des devis acceptés.');
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; ++$pageNumber) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        $pdf->AddPage('P', 'A4');
        $pdf->SetMargins(20, 24, 20);
        $pdf->SetAutoPageBreak(true, 24);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 12, $this->toPdfEncoding('Devis accepte par le client'), 0, 1);
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->MultiCell(0, 8, $this->toPdfEncoding(
            sprintf(
                "Devis accepte par le client le %s.\nReference : %s\nClient : %s",
                $proposal->getAcceptedAt()?->format('d/m/Y à H:i') ?? '—',
                $proposal->getProposalNumber() ?: $proposal->getPublicReference() ?: '—',
                $this->resolveClientIdentity($proposal, $acceptedBy)
            )
        ));

        $targetName = sprintf(
            'accepted-quote-%s-%s.pdf',
            $proposal->getPublicReference() ?: $proposal->getId(),
            bin2hex(random_bytes(6))
        );
        $targetPath = $targetDirectory . '/' . $targetName;

        $pdf->Output('F', $targetPath);

        $proposal
            ->setAcceptedPdfName($targetName)
            ->setAcceptedPdfOriginalName(($proposal->getExternalPdfOriginalName() ?: 'devis') . '-accepted.pdf')
            ->setAcceptedPdfMimeType('application/pdf')
            ->setAcceptedPdfSize((int) filesize($targetPath))
            ->setAcceptedPdfGeneratedAt(new \DateTimeImmutable())
            ->touch();
    }

    private function resolveClientIdentity(QuoteProposal $proposal, ?User $acceptedBy): string
    {
        $fullName = trim(sprintf(
            '%s %s',
            $acceptedBy?->getFirstName() ?? '',
            $acceptedBy?->getLastName() ?? ''
        ));

        if ($fullName !== '') {
            return $fullName;
        }

        return $proposal->getClientFullName()
            ?: $proposal->getClientCompanyName()
            ?: $proposal->getClientEmail()
            ?: 'Client';
    }

    private function toPdfEncoding(string $value): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $value) ?: $value;
    }
}
