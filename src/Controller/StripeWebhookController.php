<?php

namespace App\Controller;

use App\Service\Subscription\StripeWebhookManager;
use App\Service\Subscription\StripeWebhookSignatureVerifier;
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
        StripeWebhookManager $stripeWebhookManager,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');

        if (!$signatureVerifier->verify($payload, $signature)) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        $stripeWebhookManager->handle($event);

        return new Response('ok', Response::HTTP_OK);
    }
}
