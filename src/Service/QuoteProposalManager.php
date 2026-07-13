<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ClientProfile;
use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\QuoteProposalItem;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Enum\QuoteProposalStatusEnum;
use App\Repository\QuoteProposalRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuoteProposalManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuoteProposalRepository $quoteProposalRepository,
        private readonly QuoteProposalNumberGenerator $numberGenerator,
    ) {}

    public function findActiveProposal(
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire
    ): ?QuoteProposal {
        return $this->quoteProposalRepository->findOneActiveByQuoteRequestAndPrestataire($quoteRequest, $prestataire);
    }


    public function getOrCreateDraft(
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire,
        ?Conversation $conversation = null
    ): QuoteProposal {
        $existing = $this->findActiveProposal($quoteRequest, $prestataire);

        if ($existing instanceof QuoteProposal) {
            if ($conversation instanceof Conversation && $existing->getConversation() === null) {
                $existing->setConversation($conversation);
                $existing->touch();
                $this->entityManager->flush();
            }

            return $existing;
        }

        $client = $quoteRequest->getClient();

        $proposal = new QuoteProposal();
        $proposal
            ->setQuoteRequest($quoteRequest)
            ->setPrestataire($prestataire)
            ->setClient($client)
            ->setConversation($conversation)
            ->setStatus(QuoteProposalStatusEnum::DRAFT)
            ->setTitle($this->buildDefaultTitle($quoteRequest))
            ->setCurrency($this->resolveQuoteRequestCurrency($quoteRequest) ?? 'EUR');

        if ($proposal->getPublicReference() === null) {
            do {
                $reference = $this->generatePublicReference();
            } while ($this->quoteProposalRepository->findOneBy(['publicReference' => $reference]) !== null);

            $proposal->setPublicReference($reference);
        }

        $this->freezeSnapshot($proposal, $quoteRequest, $prestataire, $client, $conversation);
        $this->recalculateTotals($proposal);

        $this->entityManager->persist($proposal);
        $this->entityManager->flush();

        return $proposal;
    }

    public function refreshDraftSnapshot(QuoteProposal $proposal): QuoteProposal
    {
        if (!$proposal->getStatus()->isDraft()) {
            return $proposal;
        }

        $this->freezeSnapshot(
            $proposal,
            $proposal->getQuoteRequest(),
            $proposal->getPrestataire(),
            $proposal->getClient(),
            $proposal->getConversation()
        );

        return $proposal;
    }

    public function finalize(QuoteProposal $proposal): QuoteProposal
    {
        $this->normalizeDocumentConfiguration($proposal);
        $this->assertCanFinalize($proposal);

        $this->freezeSnapshot(
            $proposal,
            $proposal->getQuoteRequest(),
            $proposal->getPrestataire(),
            $proposal->getClient(),
            $proposal->getConversation()
        );

        $this->recalculateTotals($proposal);

        if ($this->isBlank($proposal->getProposalNumber())) {
            $generatedNumber = $this->numberGenerator->generate($proposal->getPrestataire());
            $proposal
                ->setProposalNumber($generatedNumber['number'])
                ->setProposalSequenceNumber($generatedNumber['sequence']);
        }

        $proposal->setStatus(QuoteProposalStatusEnum::FINALIZED);
        $proposal->setFinalizedAt(new \DateTime());
        $proposal->touch();

        return $proposal;
    }

    public function canFinalize(QuoteProposal $proposal): bool
    {
        if ($proposal->usesExternalPdfDocument()) {
            return true;
        }

        return !$proposal->getItems()->isEmpty();
    }

    public function assertCanFinalize(QuoteProposal $proposal): void
    {
        if ($this->canFinalize($proposal)) {
            return;
        }

        if ($proposal->getDocumentMode()->isExternalPdf()) {
            throw new \DomainException('Ajoutez un PDF externe avant de finaliser le devis.');
        }

        throw new \DomainException('Ajoutez au moins une ligne avant de finaliser le devis.');
    }

    public function softDelete(QuoteProposal $proposal): QuoteProposal
    {
        $proposal->setStatus(QuoteProposalStatusEnum::DELETED);
        $proposal->setDeletedAt(new \DateTime());
        $proposal->touch();

        return $proposal;
    }

    public function save(QuoteProposal $proposal, bool $flush = true): void
    {
        $this->normalizeDocumentConfiguration($proposal);
        $this->removeEmptyItems($proposal);
        $this->normalizeItemPositions($proposal);
        $this->recalculateTotals($proposal);
        $this->entityManager->persist($proposal);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function recalculateTotals(QuoteProposal $proposal): QuoteProposal
    {
        $subtotal = '0.00';
        $taxAmount = '0.00';

        foreach ($proposal->getItems() as $item) {
            $lineTotalHt = $this->calculateItemTotalHt($item);
            $item->setTotalHt($lineTotalHt);

            $subtotal = bcadd($subtotal, $lineTotalHt, 2);

            $vatRate = $this->normalizeDecimal($item->getVatRate(), '0.00');
            $lineTax = bcmul(
                $lineTotalHt,
                bcdiv($vatRate, '100', 4),
                2
            );

            $taxAmount = bcadd($taxAmount, $lineTax, 2);
        }

        $proposal->setSubtotalHt($subtotal);
        $proposal->setTaxAmount($taxAmount);
        $proposal->setTotalTtc(bcadd($subtotal, $taxAmount, 2));
        $proposal->touch();

        return $proposal;
    }

    private function normalizeDocumentConfiguration(QuoteProposal $proposal): void
    {
        if (!$proposal->hasExternalPdf() && $proposal->getDocumentMode()->isExternalPdf()) {
            $proposal->setDocumentMode(\App\Enum\QuoteProposalDocumentModeEnum::PLATFORM);
        }
    }

    public function freezeSnapshot(
        QuoteProposal $proposal,
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire,
        ?ClientProfile $client = null,
        ?Conversation $conversation = null
    ): QuoteProposal {
        $client ??= $quoteRequest->getClient();

        $proposal->setQuoteRequest($quoteRequest);
        $proposal->setPrestataire($prestataire);
        $proposal->setClient($client);

        if ($conversation instanceof Conversation) {
            $proposal->setConversation($conversation);
        }

        $this->freezePrestataireSnapshot($proposal, $prestataire);
        $this->freezeClientSnapshot($proposal, $client);
        $this->freezeInterventionSnapshot($proposal, $client);

        $proposal->touch();

        return $proposal;
    }

    private function freezePrestataireSnapshot(
        QuoteProposal $proposal,
        PrestataireProfile $prestataire
    ): void {
        $proposal->setPrestataireCompanyName($this->clean($prestataire->getCompanyName()));
        $proposal->setPrestataireLegalName($this->clean($prestataire->getLegalName()));
        $proposal->setPrestataireStructureType($this->clean($prestataire->getStructureType()));
        $proposal->setPrestataireSiret($this->clean($prestataire->getSiret()));
        $proposal->setPrestataireVatNumber($this->clean($prestataire->getVatNumber()));
        $proposal->setPrestataireAddress($this->clean($prestataire->getAddress()));
        $proposal->setPrestataireAddressComplement($this->clean($prestataire->getAddressComplement()));
        $proposal->setPrestatairePostalCode($this->clean($prestataire->getPostalCode()));
        $proposal->setPrestataireCity($this->clean($prestataire->getCity()));
        $proposal->setPrestataireCountry($this->clean($prestataire->getCountry()));
        $proposal->setPrestatairePhone($this->firstFilled(
            $prestataire->getPhonePublic(),
            $prestataire->getPhonePrivate()
        ));
        $proposal->setPrestataireEmail($this->clean($prestataire->getAccount()?->getEmail()));
    }

    private function freezeClientSnapshot(
        QuoteProposal $proposal,
        ?ClientProfile $client
    ): void {
        if (!$client instanceof ClientProfile) {
            $proposal->setClientTypeLabel(null);
            $proposal->setClientFullName(null);
            $proposal->setClientCompanyName(null);
            $proposal->setClientSiret(null);
            $proposal->setClientPhone(null);
            $proposal->setClientEmail(null);
            $proposal->setClientBillingAddress(null);
            $proposal->setClientBillingPostalCode(null);
            $proposal->setClientBillingCity(null);
            $proposal->setClientBillingCountry(null);

            return;
        }

        $account = $client->getAccount();

        $fullName = null;
        $email = null;

        if ($account instanceof User) {
            $firstName = trim((string) $account->getFirstName());
            $lastName = trim((string) $account->getLastName());
            $email = trim((string) $account->getEmail());

            $candidate = trim($firstName . ' ' . $lastName);
            $fullName = $candidate !== '' ? $candidate : null;
        }

        $proposal->setClientTypeLabel($this->resolveClientTypeLabel($client));
        $proposal->setClientFullName(
            $fullName
                ?? $this->clean($client->getCompanyName())
                ?? $email
        );
        $proposal->setClientCompanyName($this->clean($client->getCompanyName()));
        $proposal->setClientSiret($this->clean($client->getSiret()));
        $proposal->setClientPhone($this->resolveClientPhone($client));
        $proposal->setClientEmail($this->resolveClientEmail($client) ?? $email);
        $proposal->setClientBillingAddress($this->clean($client->getBillingAddress()));
        $proposal->setClientBillingPostalCode($this->clean($client->getBillingPostalCode()));
        $proposal->setClientBillingCity($this->clean($client->getBillingCity()));
        $proposal->setClientBillingCountry($this->clean($client->getBillingCountry()));
    }

    private function freezeInterventionSnapshot(
        QuoteProposal $proposal,
        ?ClientProfile $client
    ): void {
        if (!$client instanceof ClientProfile) {
            $proposal->setClientInterventionAddress(null);
            $proposal->setClientInterventionAddressComplement(null);
            $proposal->setClientInterventionPostalCode(null);
            $proposal->setClientInterventionCity(null);
            $proposal->setClientInterventionCountry('France');

            return;
        }

        $proposal->setClientInterventionAddress($this->clean($client->getDefaultAddress()));
        $proposal->setClientInterventionAddressComplement(null);
        $proposal->setClientInterventionPostalCode($this->clean($client->getDefaultPostalCode()));
        $proposal->setClientInterventionCity($this->clean($client->getDefaultCity()));
        $proposal->setClientInterventionCountry(
            $this->firstFilled(
                $client->getBillingCountry(),
                'France'
            )
        );
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            $value = $this->clean($value);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function calculateItemTotalHt(QuoteProposalItem $item): string
    {
        $quantity = $this->normalizeDecimal($item->getQuantity(), '0.00');
        $unitPriceHt = $this->normalizeDecimal($item->getUnitPriceHt(), '0.00');

        return bcmul($quantity, $unitPriceHt, 2);
    }

    private function buildDefaultTitle(QuoteRequest $quoteRequest): string
    {
        $title = $this->readObjectGetter($quoteRequest, ['getTitle']);

        if (!$this->isBlank($title)) {
            return 'Devis - ' . $title;
        }

        return 'Devis prestation';
    }

    private function resolveQuoteRequestCurrency(QuoteRequest $quoteRequest): ?string
    {
        $currency = $this->readObjectGetter($quoteRequest, ['getCurrency']);

        if ($this->isBlank($currency)) {
            return null;
        }

        return strtoupper((string) $currency);
    }

    private function resolveClientTypeLabel(?ClientProfile $client): ?string
    {
        if (!$client instanceof ClientProfile) {
            return null;
        }

        $type = $this->readRawObjectGetter($client, ['getType']);

        if ($type === null) {
            return null;
        }

        if ($type instanceof \BackedEnum) {
            return (string) $type->value;
        }

        if ($type instanceof \UnitEnum) {
            return $type->name;
        }

        return $this->getStringValue($type);
    }

    private function resolveClientFullName(?ClientProfile $client): ?string
    {
        if (!$client instanceof ClientProfile) {
            return null;
        }

        $account = $client->getAccount();

        $companyName = $this->clean($client->getCompanyName());

        if (!$account instanceof object) {
            return $companyName;
        }

        $firstName = null;
        foreach (['getFirstname', 'getFirstName'] as $method) {
            if (method_exists($account, $method)) {
                $firstName = $this->clean($account->{$method}());
                if ($firstName !== null) {
                    break;
                }
            }
        }

        $lastName = null;
        foreach (['getLastname', 'getLastName'] as $method) {
            if (method_exists($account, $method)) {
                $lastName = $this->clean($account->{$method}());
                if ($lastName !== null) {
                    break;
                }
            }
        }

        $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        $fullName = $this->clean($fullName);

        return $fullName ?? $companyName;
    }

    private function resolveClientPhone(?ClientProfile $client): ?string
    {
        if (!$client instanceof ClientProfile) {
            return null;
        }

        $account = $this->readRawObjectGetter($client, ['getAccount']);

        if (!$account instanceof object) {
            return null;
        }

        return $this->readObjectGetter($account, ['getPhoneNumber', 'getPhonenumber']);
    }

    private function resolveClientEmail(?ClientProfile $client): ?string
    {
        if (!$client instanceof ClientProfile) {
            return null;
        }

        $account = $this->readRawObjectGetter($client, ['getAccount']);

        if (!$account instanceof object) {
            return null;
        }

        return $this->readObjectGetter($account, ['getEmail']);
    }

    private function resolvePrestataireEmail(PrestataireProfile $prestataire): ?string
    {
        $account = $this->readRawObjectGetter($prestataire, ['getAccount']);

        if (!$account instanceof object) {
            return null;
        }

        return $this->readObjectGetter($account, ['getEmail']);
    }

    private function readObjectGetter(?object $object, array $methods): ?string
    {
        $value = $this->readRawObjectGetter($object, $methods);

        return $this->getStringValue($value);
    }

    private function readRawObjectGetter(?object $object, array $methods): mixed
    {
        if (!$object instanceof object) {
            return null;
        }

        foreach ($methods as $method) {
            if (!method_exists($object, $method)) {
                continue;
            }

            return $object->{$method}();
        }

        return null;
    }

    private function getStringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            $value = trim((string) $value);

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function normalizeDecimal(mixed $value, string $default = '0.00'): string
    {
        $value = $this->getStringValue($value);

        if ($value === null) {
            return $default;
        }

        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return $default;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function trimJoin(array $parts): ?string
    {
        $parts = array_filter(
            array_map(fn(mixed $value) => $this->getStringValue($value), $parts),
            fn(?string $value) => !$this->isBlank($value)
        );

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    private function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            $stringValue = $this->getStringValue($value);

            if (!$this->isBlank($stringValue)) {
                return $stringValue;
            }
        }

        return null;
    }

    private function generatePublicReference(): string
    {
        return sprintf('DEV-%s-%s', date('Y'), strtoupper(substr(bin2hex(random_bytes(6)), 0, 12)));
    }

    private function normalizeItemPositions(QuoteProposal $proposal): void
    {
        $position = 1;

        foreach ($proposal->getItems() as $item) {
            $item->setPosition($position++);
        }
    }

    private function removeEmptyItems(QuoteProposal $proposal): void
    {
        foreach ($proposal->getItems()->toArray() as $item) {
            $description = $item->getDescription();
            $label = $item->getLabel();

            $isLabelBlank = $label === null || trim($label) === '';
            $isDescriptionBlank = $description === null || trim($description) === '';
            $isQuantityEmpty = $item->getQuantity() === null;
            $isUnitPriceEmpty = $item->getUnitPriceHt() === null;
            $isVatRateEmpty = $item->getVatRate() === null;

            if ($isLabelBlank && $isDescriptionBlank && $isQuantityEmpty && $isUnitPriceEmpty && $isVatRateEmpty) {
                $proposal->removeItem($item);
            }
        }
    }

    private function isBlank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}
