<?php

namespace App\Service\Subscription;

class StripeWebhookSignatureVerifier
{
    private const DEFAULT_TOLERANCE = 300;

    public function __construct(
        private readonly string $webhookSecret,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->webhookSecret);
    }

    public function verify(string $payload, ?string $signatureHeader, int $tolerance = self::DEFAULT_TOLERANCE): bool
    {
        if (!$this->isConfigured() || null === $signatureHeader || '' === $signatureHeader) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if (null !== $key && null !== $value) {
                $parts[trim($key)][] = trim($value);
            }
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : null;
        $signatures = $parts['v1'] ?? [];

        if (null === $timestamp || [] === $signatures) {
            return false;
        }

        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }
}
