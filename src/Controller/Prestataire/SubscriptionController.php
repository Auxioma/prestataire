<?php

namespace App\Controller\Prestataire;

use App\Entity\User;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Entity\Subscription\SubscriptionInvoice;
use App\Repository\Subscription\PrestataireSubscriptionRepository;
use App\Repository\Subscription\SubscriptionInvoiceRepository;
use App\Repository\Subscription\SubscriptionPlanRepository;
use App\Service\AuthenticatedUserProvider;
use App\Service\Subscription\StripeCheckoutSessionSynchronizer;
use App\Service\Subscription\StripeApiClient;
use App\Service\Subscription\StripeCustomerManager;
use App\Service\Subscription\SubscriptionInvoicePdfGenerator;
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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/prestataire/abonnements', name: 'app_subscription_')]
#[IsGranted('ROLE_PRESTATAIRE')]
final class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

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

        return $this->render('prestataire/subscription/index.html.twig', [
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
        $csrfToken = $this->extractRequestValue($request, '_token');

        if (!$this->isCsrfTokenValid('subscription-finalize', $csrfToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

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

    #[Route('/resiliation', name: 'cancel', methods: ['POST'])]
    public function cancel(
        Request $request,
        StripeApiClient $stripeApiClient,
        StripeCheckoutSessionSynchronizer $stripeCheckoutSessionSynchronizer,
        StripeSubscriptionCheckoutManager $stripeSubscriptionCheckoutManager,
        SubscriptionAccessManager $subscriptionAccessManager,
    ): RedirectResponse {
        $prestataireProfile = $this->getPrestataireProfile();

        if (!$this->isCsrfTokenValid('subscription-cancel', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$stripeApiClient->isConfigured()) {
            $this->addFlash('danger', 'Stripe n’est pas configuré sur cet environnement.');

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $stripeCheckoutSessionSynchronizer->syncLatestSubscriptionForPrestataire($prestataireProfile);
        } catch (\Throwable) {
        }

        $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);
        if (
            null === $currentSubscription
            || null === $currentSubscription->getPlan()
            || 'free' === $currentSubscription->getPlan()->getCode()
            || !$stripeSubscriptionCheckoutManager->isManagedStripeSubscription($currentSubscription)
        ) {
            $this->addFlash('warning', 'Aucun abonnement payant résiliable n’est actuellement associé à votre compte.');

            return $this->redirectToRoute('app_subscription_index');
        }

        if ($currentSubscription->isCancelAtPeriodEnd()) {
            $this->addFlash('info', 'La résiliation de votre abonnement est déjà programmée.');

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $updatedSubscription = $stripeSubscriptionCheckoutManager->scheduleCancellation($currentSubscription);
            $effectiveDate = $updatedSubscription->getCurrentPeriodEnd();

            $this->addFlash(
                'success',
                $effectiveDate instanceof \DateTimeImmutable
                    ? sprintf('Votre abonnement sera résilié le %s. Vous conservez l’accès jusqu’à cette date.', $effectiveDate->format('d/m/Y'))
                    : 'Votre abonnement sera résilié à la fin de la période en cours.'
            );
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Impossible de programmer la résiliation : ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_subscription_index');
    }

    #[Route('/resiliation/annuler', name: 'resume', methods: ['POST'])]
    public function resume(
        Request $request,
        StripeApiClient $stripeApiClient,
        StripeCheckoutSessionSynchronizer $stripeCheckoutSessionSynchronizer,
        StripeSubscriptionCheckoutManager $stripeSubscriptionCheckoutManager,
        SubscriptionAccessManager $subscriptionAccessManager,
    ): RedirectResponse {
        $prestataireProfile = $this->getPrestataireProfile();

        if (!$this->isCsrfTokenValid('subscription-resume', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$stripeApiClient->isConfigured()) {
            $this->addFlash('danger', 'Stripe n’est pas configuré sur cet environnement.');

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $stripeCheckoutSessionSynchronizer->syncLatestSubscriptionForPrestataire($prestataireProfile);
        } catch (\Throwable) {
        }

        $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);
        if (
            null === $currentSubscription
            || null === $currentSubscription->getPlan()
            || 'free' === $currentSubscription->getPlan()->getCode()
            || !$stripeSubscriptionCheckoutManager->isManagedStripeSubscription($currentSubscription)
        ) {
            $this->addFlash('warning', 'Aucun abonnement payant réactivable n’est actuellement associé à votre compte.');

            return $this->redirectToRoute('app_subscription_index');
        }

        if (!$currentSubscription->isCancelAtPeriodEnd()) {
            $this->addFlash('info', 'Aucune résiliation n’est actuellement programmée sur votre abonnement.');

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $stripeSubscriptionCheckoutManager->resumeScheduledCancellation($currentSubscription);
            $this->addFlash('success', 'La résiliation programmée a été annulée. Votre abonnement restera actif.');
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Impossible d’annuler la résiliation programmée : ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_subscription_index');
    }

    #[Route('/factures/{invoiceId}/telecharger', name: 'invoice_download', methods: ['GET'])]
    public function downloadInvoice(
        string $invoiceId,
        SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        SubscriptionInvoicePdfGenerator $subscriptionInvoicePdfGenerator,
    ): Response {
        $prestataireProfile = $this->getPrestataireProfile();
        $invoice = $subscriptionInvoiceRepository->findOneForPrestataireById($prestataireProfile, $invoiceId);

        if (!$invoice instanceof SubscriptionInvoice) {
            throw $this->createNotFoundException('Facture introuvable.');
        }

        $filename = $this->buildInvoiceDownloadFilename($invoice);
        $response = new Response($subscriptionInvoicePdfGenerator->generatePdfOutput($invoice));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    private function getPrestataireProfile(): \App\Entity\PrestataireProfile
    {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
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

    private function buildInvoiceDownloadFilename(SubscriptionInvoice $invoice): string
    {
        $baseName = trim((string) ($invoice->getInvoiceNumber() ?: $invoice->getStripeInvoiceId() ?: 'facture-abonnement'));
        $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $baseName) ?: 'facture-abonnement';

        return sprintf('%s.pdf', trim($baseName, '-'));
    }
}
