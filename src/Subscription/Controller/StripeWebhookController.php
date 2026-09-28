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

namespace App\Subscription\Controller;

use App\Subscription\Service\StripeWebhookEventRecorder;
use App\Subscription\Service\StripeWebhookManager;
use App\Subscription\Service\StripeWebhookSignatureVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function __invoke(
        Request $request,
        StripeWebhookSignatureVerifier $signatureVerifier,
        StripeWebhookEventRecorder $stripeWebhookEventRecorder,
        StripeWebhookManager $stripeWebhookManager,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        if (!$signatureVerifier->verify($payload, $signature)) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $event = json_decode($payload, true);
        if (!\is_array($event)) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        if ($stripeWebhookEventRecorder->isAlreadyProcessed($event)) {
            return new Response('ok', Response::HTTP_OK);
        }

        $stripeWebhookManager->handle($event);
        $stripeWebhookEventRecorder->recordProcessed($event);

        return new Response('ok', Response::HTTP_OK);
    }
}
