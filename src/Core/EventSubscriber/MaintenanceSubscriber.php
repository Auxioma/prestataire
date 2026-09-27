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

namespace App\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

final class MaintenanceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly bool $maintenanceMode,
        private readonly ?string $maintenanceSecret = null,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->maintenanceMode) {
            return;
        }

        $request = $event->getRequest();

        if ($this->canBypassMaintenance($request)) {
            return;
        }

        $response = new Response(
            $this->twig->render('Website/maintenance.html.twig'),
            Response::HTTP_SERVICE_UNAVAILABLE,
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );

        $event->setResponse($response);
    }

    private function canBypassMaintenance(Request $request): bool
    {
        if (!$this->maintenanceSecret) {
            return false;
        }

        $headerToken = $request->headers->get('X-Maintenance-Token');
        if (\is_string($headerToken) && hash_equals($this->maintenanceSecret, $headerToken)) {
            return true;
        }

        $queryToken = $request->query->get('maintenance_token');

        return \is_string($queryToken) && hash_equals($this->maintenanceSecret, $queryToken);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
        ];
    }
}
