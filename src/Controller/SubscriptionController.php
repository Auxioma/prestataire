<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Repository\Subscription\PrestataireSubscriptionRepository;
use App\Repository\Subscription\SubscriptionInvoiceRepository;
use App\Repository\Subscription\SubscriptionPlanRepository;
use App\Service\Subscription\StripeCheckoutSessionSynchronizer;
use App\Service\Subscription\StripeApiClient;
use App\Service\Subscription\StripeCustomerManager;
use App\Service\Subscription\StripeSubscriptionCheckoutManager;
use App\Service\Subscription\SubscriptionAccessManager;
use App\Service\Subscription\SubscriptionFallbackManager;
use App\Service\Subscription\SubscriptionUpgradePolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/prestataire/abonnements', name: 'app_subscription_')]
final class SubscriptionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        PrestataireSubscriptionRepository $prestataireSubscriptionRepository,
        SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        SubscriptionAccessManager $subscriptionAccessManager,
        SubscriptionFallbackManager $subscriptionFallbackManager,
        StripeApiClient $stripeApiClient,
        StripeCheckoutSessionSynchronizer $stripeCheckoutSessionSynchronizer,
        EntityManagerInterface $entityManager,
        #[Autowire('%app.stripe.public_key%')] string $stripePublicKey,
    ): Response {
        $prestataireProfile = $this->getPrestataireProfile();

        if ('success' === $request->query->get('checkout')) {
            $checkoutSessionId = trim((string) $request->query->get('session_id', ''));

            if ('' !== $checkoutSessionId) {
                try {
                    $synchronized = $stripeCheckoutSessionSynchronizer->syncCompletedSession($checkoutSessionId, $prestataireProfile);
                    $this->addFlash(
                        $synchronized ? 'success' : 'warning',
                        $synchronized
                            ? 'Le paiement a bien ete confirme et votre abonnement a ete synchronise.'
                            : 'Le paiement est revenu de Stripe, mais la synchronisation locale reste en attente du webhook.'
                    );
                } catch (\Throwable $exception) {
                    $this->addFlash('warning', 'Le paiement a bien ete confirme par Stripe, mais la synchronisation locale a echoue : ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('info', 'Le paiement a bien ete transmis a Stripe. Votre abonnement sera active ou mis a jour apres confirmation definitive du paiement par webhook.');
            }
        }

        if ('cancel' === $request->query->get('checkout')) {
            $this->addFlash('warning', 'Le paiement a ete annule.');
        }

        $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);

        if ($stripeApiClient->isConfigured()) {
            try {
                if ($stripeCheckoutSessionSynchronizer->syncLatestSubscriptionForPrestataire($prestataireProfile)) {
                    $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);
                }
            } catch (\Throwable) {
            }
        }

        if (null === $currentSubscription) {
            $latestSubscription = $prestataireSubscriptionRepository->findLatestForPrestataire($prestataireProfile);

            if (null !== $latestSubscription && $subscriptionFallbackManager->shouldFallbackToFree($latestSubscription)) {
                $currentSubscription = $subscriptionFallbackManager->fallbackToFree(
                    $prestataireProfile,
                    $latestSubscription,
                    'subscription_page_display'
                );
                $entityManager->flush();
            } elseif (null !== $latestSubscription) {
                $currentSubscription = $subscriptionFallbackManager->ensureFreeSubscription($prestataireProfile);
                $entityManager->flush();
            } else {
                $currentSubscription = $subscriptionFallbackManager->ensureFreeSubscription($prestataireProfile);
                $entityManager->flush();
            }
        }

        $subscriptionRenewalDate = null;
        if (null !== $currentSubscription) {
            $subscriptionRenewalDate = $currentSubscription->getCurrentPeriodEnd();

            if (!$subscriptionRenewalDate instanceof \DateTimeImmutable) {
                $latestSettledInvoice = $subscriptionInvoiceRepository->findLatestSettledForSubscription($currentSubscription);
                $subscriptionRenewalDate = $latestSettledInvoice?->getPeriodEnd();
            }
        }

        $recentInvoices = $subscriptionInvoiceRepository->findRecentForPrestataire($prestataireProfile);

        return $this->render('subscription/index.html.twig', [
            'plans' => $subscriptionPlanRepository->findActiveOrdered(),
            'currentSubscription' => $currentSubscription,
            'subscriptionRenewalDate' => $subscriptionRenewalDate,
            'recentInvoices' => $recentInvoices,
            'stripeConfigured' => $stripeApiClient->isConfigured(),
            'stripePublicKey' => $stripePublicKey,
        ]);
    }

    #[Route('/setup-intent', name: 'setup_intent', methods: ['POST'])]
    public function setupIntent(
        Request $request,
        StripeApiClient $stripeApiClient,
        StripeSubscriptionCheckoutManager $stripeSubscriptionCheckoutManager,
    ): JsonResponse {
        if (!$stripeApiClient->isConfigured()) {
            return $this->json([
                'success' => false,
                'message' => 'Stripe n’est pas configuré sur cet environnement.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $token = $this->extractRequestValue($request, '_token');
        if (!$this->isCsrfTokenValid('subscription-setup-intent', $token)) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $setupIntent = $stripeSubscriptionCheckoutManager->createEmbeddedSetupIntent($this->getPrestataireProfile());
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible d’initialiser le formulaire Stripe : ' . $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json([
            'success' => true,
            'clientSecret' => $setupIntent['clientSecret'],
            'customerId' => $setupIntent['customerId'],
        ]);
    }

    #[Route('/checkout/{code}/{period}', name: 'checkout', methods: ['POST'])]
    public function checkout(
        string $code,
        string $period,
        Request $request,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionAccessManager $subscriptionAccessManager,
        SubscriptionUpgradePolicy $subscriptionUpgradePolicy,
        StripeApiClient $stripeApiClient,
        StripeCheckoutSessionSynchronizer $stripeCheckoutSessionSynchronizer,
        StripeSubscriptionCheckoutManager $stripeSubscriptionCheckoutManager,
    ): JsonResponse {
        $prestataireProfile = $this->getPrestataireProfile();

        if (!$stripeApiClient->isConfigured()) {
            return $this->json([
                'success' => false,
                'message' => 'Stripe n’est pas configuré sur cet environnement.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $csrfToken = $this->extractRequestValue($request, '_token');
        if (!$this->isCsrfTokenValid('subscription-checkout-' . $code . '-' . $period, $csrfToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $billingPeriod = SubscriptionBillingPeriodEnum::tryFrom($period);
        if (!$billingPeriod instanceof SubscriptionBillingPeriodEnum) {
            return $this->json([
                'success' => false,
                'message' => 'Période de facturation invalide.',
            ], Response::HTTP_NOT_FOUND);
        }

        $plan = $subscriptionPlanRepository->findOneActiveByCode($code);
        if (null === $plan || !$plan->supportsBillingPeriod($billingPeriod)) {
            return $this->json([
                'success' => false,
                'message' => 'Abonnement introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $stripeCheckoutSessionSynchronizer->syncLatestSubscriptionForPrestataire($prestataireProfile);
        } catch (\Throwable) {
        }

        $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);
        try {
            $subscriptionUpgradePolicy->assertCanPurchasePlan($currentSubscription, $plan, $billingPeriod);
        } catch (\DomainException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $setupIntentId = trim($this->extractRequestValue($request, 'setupIntentId'));
        if ('' === $setupIntentId) {
            return $this->json([
                'success' => false,
                'message' => 'Le moyen de paiement Stripe n’a pas été confirmé.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            if ($stripeSubscriptionCheckoutManager->isManagedStripeSubscription($currentSubscription) && null !== $currentSubscription?->getPlan()) {
                $stripeSubscriptionCheckoutManager->applySetupIntentPaymentMethod($prestataireProfile, $setupIntentId);
                $result = $stripeSubscriptionCheckoutManager->requestUpgrade($currentSubscription, $plan, $billingPeriod);
            } else {
                $result = $stripeSubscriptionCheckoutManager->createSubscriptionFromSetupIntent(
                    $prestataireProfile,
                    $plan,
                    $billingPeriod,
                    $setupIntentId,
                );
            }
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de créer ou mettre à jour l’abonnement Stripe : ' . $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json([
            'success' => true,
            'requiresAction' => (bool) ($result['requiresAction'] ?? false),
            'paymentIntentClientSecret' => $result['paymentIntentClientSecret'] ?? null,
            'paymentIntentStatus' => $result['paymentIntentStatus'] ?? null,
            'stripeSubscriptionId' => $result['stripeSubscriptionId'] ?? null,
            'message' => $result['message'] ?? 'L’abonnement a été transmis à Stripe.',
            'redirectUrl' => $this->generateUrl('app_subscription_index'),
        ]);
    }

    #[Route('/finalize', name: 'finalize', methods: ['POST'])]
    public function finalize(
        Request $request,
        StripeApiClient $stripeApiClient,
        StripeCheckoutSessionSynchronizer $stripeCheckoutSessionSynchronizer,
    ): JsonResponse {
        if (!$stripeApiClient->isConfigured()) {
            return $this->json([
                'success' => false,
                'message' => 'Stripe n’est pas configuré sur cet environnement.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $prestataireProfile = $this->getPrestataireProfile();

        $stripeSubscriptionId = $this->extractRequestValue($request, 'stripeSubscriptionId');

        try {
            if ('' !== $stripeSubscriptionId) {
                $stripeCheckoutSessionSynchronizer->syncSubscriptionForPrestataire($stripeSubscriptionId, $prestataireProfile);
            } else {
                $stripeCheckoutSessionSynchronizer->syncLatestSubscriptionForPrestataire($prestataireProfile);
            }
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => 'La synchronisation finale avec Stripe a échoué : ' . $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return $this->json([
            'success' => true,
            'redirectUrl' => $this->generateUrl('app_subscription_index'),
        ]);
    }

    #[Route('/portal', name: 'portal', methods: ['GET', 'POST'])]
    public function portal(
        StripeApiClient $stripeApiClient,
        StripeCustomerManager $stripeCustomerManager,
    ): RedirectResponse {
        $prestataireProfile = $this->getPrestataireProfile();

        if (!$stripeApiClient->isConfigured()) {
            $this->addFlash('danger', 'Stripe n’est pas configuré sur cet environnement.');

            return $this->redirectToRoute('app_subscription_index');
        }

        $customer = $stripeCustomerManager->getManagedCustomerForPrestataire($prestataireProfile);
        if (null === $customer) {
            $this->addFlash('warning', 'Aucun compte de facturation Stripe n’est encore associé à votre profil.');

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $portalSession = $stripeApiClient->createBillingPortalSession(
                $customer,
                $this->generateUrl('app_subscription_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Impossible d’ouvrir le portail de facturation Stripe : ' . $exception->getMessage());

            return $this->redirectToRoute('app_subscription_index');
        }

        $portalUrl = $portalSession['url'] ?? null;
        if (!is_string($portalUrl) || '' === $portalUrl) {
            $this->addFlash('danger', 'Impossible d’ouvrir le portail de facturation Stripe.');

            return $this->redirectToRoute('app_subscription_index');
        }

        return $this->redirect($portalUrl);
    }

    private function getPrestataireProfile(): \App\Entity\PrestataireProfile
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        return $user->getPrestataireProfile();
    }

    private function extractRequestValue(Request $request, string $key): string
    {
        $formValue = $request->request->get($key);
        if (\is_scalar($formValue)) {
            return trim((string) $formValue);
        }

        if ('' === trim((string) $request->getContent())) {
            return '';
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return '';
        }

        $value = $payload[$key] ?? null;

        return \is_scalar($value) ? trim((string) $value) : '';
    }
}
