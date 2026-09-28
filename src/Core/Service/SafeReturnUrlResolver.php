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

namespace App\Core\Service;

use Symfony\Component\HttpFoundation\Request;

final class SafeReturnUrlResolver
{
    public function resolve(Request $request, string $fallbackUrl): string
    {
        $referer = $request->headers->get('referer');

        if (!\is_string($referer) || '' === $referer) {
            return $fallbackUrl;
        }

        $refererParts = parse_url($referer);
        $requestOriginParts = parse_url($request->getSchemeAndHttpHost());

        if (
            false === $refererParts
            || false === $requestOriginParts
            || isset($refererParts['user'])
            || isset($refererParts['pass'])
            || !isset($refererParts['scheme'], $refererParts['host'])
            || !isset($requestOriginParts['scheme'], $requestOriginParts['host'])
            || !\in_array(mb_strtolower($refererParts['scheme']), ['http', 'https'], true)
            || mb_strtolower($refererParts['scheme']) !== mb_strtolower($requestOriginParts['scheme'])
            || mb_strtolower($refererParts['host']) !== mb_strtolower($requestOriginParts['host'])
            || $this->resolvePort($refererParts) !== $this->resolvePort($requestOriginParts)
        ) {
            return $fallbackUrl;
        }

        $path = $refererParts['path'] ?? '/';
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return $fallbackUrl;
        }

        $returnUrl = $path;
        if (isset($refererParts['query']) && '' !== $refererParts['query']) {
            $returnUrl .= '?'.$refererParts['query'];
        }

        return $returnUrl !== $request->getRequestUri() ? $returnUrl : $fallbackUrl;
    }

    /**
     * @param array<string, int|string> $urlParts
     */
    private function resolvePort(array $urlParts): int
    {
        if (isset($urlParts['port'])) {
            return (int) $urlParts['port'];
        }

        return 'https' === mb_strtolower((string) ($urlParts['scheme'] ?? '')) ? 443 : 80;
    }
}
