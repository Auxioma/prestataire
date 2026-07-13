<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Enum\InvoiceSourceTypeEnum;
use App\Enum\InvoiceStatusEnum;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

final class InvoiceManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly InvoiceFactoryFromQuote $invoiceFactoryFromQuote,
        private readonly InvoiceTotalsCalculator $totalsCalculator,
        private readonly InvoiceDocumentManager $documentManager,
        private readonly InvoiceNumberGenerator $numberGenerator,
    ) {}

    public function getOrCreateFromAcceptedQuote(QuoteProposal $proposal): Invoice
    {
        $this->assertAcceptedQuote($proposal);

        $existing = $this->invoiceRepository->findOneByQuoteProposal($proposal);
        if ($existing instanceof Invoice) {
            return $existing;
        }

        $invoice = $this->invoiceFactoryFromQuote->createFromAcceptedQuote($proposal);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->documentManager->refreshGeneratedDocuments($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    public function saveDraft(Invoice $invoice, bool $flush = true): void
    {
        $this->assertEditable($invoice);
        $this->normalizeSourceConfiguration($invoice);
        $this->removeEmptyItems($invoice);
        $this->normalizeItemPositions($invoice);
        $this->totalsCalculator->recalculate($invoice);
        $invoice->setStatus(InvoiceStatusEnum::DRAFT);

        $this->entityManager->persist($invoice);

        if ($flush) {
            $this->entityManager->flush();
            $this->documentManager->refreshGeneratedDocuments($invoice);
            $this->entityManager->flush();
        }
    }

    public function issue(Invoice $invoice): Invoice
    {
        $this->assertEditable($invoice);
        $this->normalizeSourceConfiguration($invoice);
        $this->assertCanIssue($invoice);
        $this->normalizeItemPositions($invoice);
        $this->totalsCalculator->recalculate($invoice);

        if ($invoice->getInvoiceNumber() === null) {
            $prestataire = $invoice->getPrestataire();

            if (!$prestataire instanceof PrestataireProfile) {
                throw new \DomainException('Prestataire introuvable pour cette facture.');
            }

            $invoice->setInvoiceNumber($this->numberGenerator->generate($prestataire, $invoice->getQuoteProposal()));

            if ($invoice->getQuoteProposal()?->getProposalSequenceNumber() !== null) {
                $invoice->setInvoiceSequenceNumber($invoice->getQuoteProposal()->getProposalSequenceNumber());
            }
        }

        $invoice
            ->setStatus(InvoiceStatusEnum::ISSUED)
            ->setIssuedAt(new \DateTimeImmutable());

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->documentManager->refreshGeneratedDocuments($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    public function assertEditable(Invoice $invoice): void
    {
        $proposal = $invoice->getQuoteProposal();

        if (!$proposal instanceof QuoteProposal || !$proposal->isAccepted()) {
            throw new \DomainException('La facture n’est accessible que pour un devis accepté.');
        }

        if ($invoice->isIssued()) {
            throw new \DomainException('Cette facture a déjà été émise et ne peut plus être modifiée.');
        }
    }

    private function assertAcceptedQuote(QuoteProposal $proposal): void
    {
        if (!$proposal->isAccepted()) {
            throw new \DomainException('Une facture ne peut être créée que depuis un devis accepté.');
        }
    }

    private function assertCanIssue(Invoice $invoice): void
    {
        if ($invoice->getSourceType() === InvoiceSourceTypeEnum::EXTERNAL_IMPORT) {
            if (!$invoice->hasExternalPdf()) {
                throw new \DomainException('Ajoutez un PDF de facture avant d’émettre la facture.');
            }

            return;
        }

        if ($invoice->getItems()->isEmpty()) {
            throw new \DomainException('Ajoutez au moins une ligne avant d’émettre la facture.');
        }
    }

    private function normalizeSourceConfiguration(Invoice $invoice): void
    {
        if ($invoice->getSourceType() === InvoiceSourceTypeEnum::EXTERNAL_IMPORT && !$invoice->hasExternalPdf()) {
            $invoice->setSourceType($this->resolveInternalSourceType($invoice));
        }
    }

    private function resolveInternalSourceType(Invoice $invoice): InvoiceSourceTypeEnum
    {
        return $invoice->getQuoteProposal()?->usesExternalPdfDocument()
            ? InvoiceSourceTypeEnum::MANUAL_FROM_EXTERNAL_QUOTE
            : InvoiceSourceTypeEnum::GENERATED_FROM_QUOTE;
    }

    private function removeEmptyItems(Invoice $invoice): void
    {
        foreach ($invoice->getItems() as $item) {
            if ($this->isItemCompletelyEmpty($item)) {
                $invoice->removeItem($item);
            }
        }
    }

    private function normalizeItemPositions(Invoice $invoice): void
    {
        $position = 1;

        foreach ($invoice->getItems() as $item) {
            $item->setPosition($position);
            ++$position;
        }
    }

    private function isItemCompletelyEmpty(InvoiceItem $item): bool
    {
        $label = $item->getLabel();
        $description = $item->getDescription();

        return ($label === null || trim($label) === '')
            && ($description === null || trim($description) === '')
            && $item->getQuantity() === null
            && $item->getUnitPriceHt() === null
            && $item->getVatRate() === null;
    }
}
