<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ClientProfile;
use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\QuoteProposalItem;
use App\Entity\QuoteRequest;
use App\Enum\QuoteProposalStatusEnum;
use App\Repository\QuoteProposalRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuoteProposalManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuoteProposalRepository $quoteProposalRepository,
        private readonly QuoteProposalNumberGenerator $numberGenerator,
    ) {
    }

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
        $this->freezeSnapshot(
            $proposal,
            $proposal->getQuoteRequest(),
            $proposal->getPrestataire(),
            $proposal->getClient(),
            $proposal->getConversation()
        );

        $this->recalculateTotals($proposal);

        if ($this->isBlank($proposal->getProposalNumber())) {
            $proposal->setProposalNumber($this->numberGenerator->generate());
        }

        $proposal->setStatus(QuoteProposalStatusEnum::FINALIZED);
        $proposal->setFinalizedAt(new \DateTimeImmutable());
        $proposal->touch();

        return $proposal;
    }

    public function softDelete(QuoteProposal $proposal): QuoteProposal
    {
        $proposal->setStatus(QuoteProposalStatusEnum::DELETED);
        $proposal->setDeletedAt(new \DateTimeImmutable());
        $proposal->touch();

        return $proposal;
    }

    public function save(QuoteProposal $proposal, bool $flush = true): void
    {
        $this->recalculateTotals($proposal);

        $this->entityManager->persist($proposal);

        if ($flush) {
            $this->entityManager->flush();
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
        $this->freezeInterventionSnapshot($proposal, $quoteRequest, $client);

        $proposal->touch();

        return $proposal;
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

    private function freezePrestataireSnapshot(
        QuoteProposal $proposal,
        PrestataireProfile $prestataire
    ): void {
        $proposal->setPrestataireCompanyName($this->readObjectGetter($prestataire, ['getCompanyName']));
        $proposal->setPrestataireLegalName($this->readObjectGetter($prestataire, ['getLegalName']));
        $proposal->setPrestataireStructureType($this->readObjectGetter($prestataire, ['getStructureType']));
        $proposal->setPrestataireSiret($this->readObjectGetter($prestataire, ['getSiret']));
        $proposal->setPrestataireVatNumber($this->readObjectGetter($prestataire, ['getVatNumber']));
        $proposal->setPrestataireAddress($this->readObjectGetter($prestataire, ['getAddress']));
        $proposal->setPrestataireAddressComplement($this->readObjectGetter($prestataire, ['getAddressComplement']));
        $proposal->setPrestatairePostalCode($this->readObjectGetter($prestataire, ['getPostalCode']));
        $proposal->setPrestataireCity($this->readObjectGetter($prestataire, ['getCity']));
        $proposal->setPrestataireCountry($this->readObjectGetter($prestataire, ['getCountry']));

        $proposal->setPrestatairePhone($this->firstNonEmpty([
            $this->readObjectGetter($prestataire, ['getPhonePublic']),
            $this->readObjectGetter($prestataire, ['getPhonePrivate']),
        ]));

        $proposal->setPrestataireEmail($this->resolvePrestataireEmail($prestataire));
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

        $proposal->setClientTypeLabel($this->resolveClientTypeLabel($client));
        $proposal->setClientFullName($this->resolveClientFullName($client));
        $proposal->setClientCompanyName($this->readObjectGetter($client, ['getCompanyName']));
        $proposal->setClientSiret($this->readObjectGetter($client, ['getSiret']));
        $proposal->setClientPhone($this->resolveClientPhone($client));
        $proposal->setClientEmail($this->resolveClientEmail($client));
        $proposal->setClientBillingAddress($this->readObjectGetter($client, ['getBillingAddress']));
        $proposal->setClientBillingPostalCode($this->readObjectGetter($client, ['getBillingPostalCode']));
        $proposal->setClientBillingCity($this->readObjectGetter($client, ['getBillingCity']));
        $proposal->setClientBillingCountry($this->readObjectGetter($client, ['getBillingCountry']));
    }

    private function freezeInterventionSnapshot(
        QuoteProposal $proposal,
        QuoteRequest $quoteRequest,
        ?ClientProfile $client
    ): void {
        $proposal->setClientInterventionAddress($this->firstNonEmpty([
            $this->readObjectGetter($quoteRequest, ['getAddress']),
            $this->readObjectGetter($client, ['getDefaultAddress']),
        ]));

        $proposal->setClientInterventionAddressComplement(
            $this->readObjectGetter($quoteRequest, ['getAddressComplement'])
        );

        $proposal->setClientInterventionPostalCode($this->firstNonEmpty([
            $this->readObjectGetter($quoteRequest, ['getPostalCode']),
            $this->readObjectGetter($client, ['getDefaultPostalCode']),
        ]));

        $proposal->setClientInterventionCity($this->firstNonEmpty([
            $this->readObjectGetter($quoteRequest, ['getCity']),
            $this->readObjectGetter($client, ['getDefaultCity']),
        ]));

        $proposal->setClientInterventionCountry(
            $this->firstNonEmpty([
                $this->readObjectGetter($quoteRequest, ['getCountry']),
                $this->readObjectGetter($client, ['getBillingCountry']),
                'France',
            ])
        );
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

        $account = $this->readRawObjectGetter($client, ['getAccount']);

        if (!$account instanceof object) {
            return null;
        }

        $firstName = $this->readObjectGetter($account, ['getFirstname', 'getFirstName']);
        $lastName = $this->readObjectGetter($account, ['getLastname', 'getLastName']);

        return $this->trimJoin([$firstName, $lastName]);
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
            array_map(fn (mixed $value) => $this->getStringValue($value), $parts),
            fn (?string $value) => !$this->isBlank($value)
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

    private function isBlank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}