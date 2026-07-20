<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Repository\Subscription\SubscriptionPlanRepository;
use App\Service\Subscription\StripeCheckoutSessionSynchronizer;
use App\Service\Subscription\StripeApiClient;
use App\Service\Subscription\StripeCustomerManager;
use App\Service\Subscription\StripeSubscriptionCheckoutManager;
use App\Service\Subscription\SubscriptionAccessManager;
use App\Service\Subscription\SubscriptionUpgradePolicy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        SubscriptionAccessManager $subscriptionAccessManager,
        StripeApiClient $stripeApiClient,
        StripeCheckoutSessionSynchronizer $stripeCheckoutSessionSynchronizer,
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

        return $this->render('subscription/index.html.twig', [
            'plans' => $subscriptionPlanRepository->findActiveOrdered(),
            'currentSubscription' => $currentSubscription,
            'stripeConfigured' => $stripeApiClient->isConfigured(),
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
        StripeSubscriptionCheckoutManager $stripeSubscriptionCheckoutManager,
    ): RedirectResponse {
        $prestataireProfile = $this->getPrestataireProfile();

        if (!$stripeApiClient->isConfigured()) {
            $this->addFlash('danger', 'Stripe n’est pas configuré sur cet environnement.');

            return $this->redirectToRoute('app_subscription_index');
        }

        if (!$this->isCsrfTokenValid('subscription-checkout-' . $code . '-' . $period, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $billingPeriod = SubscriptionBillingPeriodEnum::tryFrom($period);
        if (!$billingPeriod instanceof SubscriptionBillingPeriodEnum) {
            throw $this->createNotFoundException('Période de facturation invalide.');
        }

        $plan = $subscriptionPlanRepository->findOneActiveByCode($code);
        if (null === $plan || !$plan->supportsBillingPeriod($billingPeriod)) {
            throw $this->createNotFoundException('Abonnement introuvable.');
        }

        $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);
        try {
            $subscriptionUpgradePolicy->assertCanPurchasePlan($currentSubscription, $plan, $billingPeriod);
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $this->redirectToRoute('app_subscription_index');
        }

        if ($stripeSubscriptionCheckoutManager->isManagedStripeSubscription($currentSubscription) && null !== $currentSubscription?->getPlan()) {
            try {
                $stripeSubscriptionCheckoutManager->requestUpgrade($currentSubscription, $plan, $billingPeriod);
                $this->addFlash('success', 'La montée en gamme a été demandée à Stripe. Le nouveau cycle et sa facturation immédiate ont été transmis.');
            } catch (\Throwable $exception) {
                $this->addFlash('danger', 'Impossible de mettre à jour l’abonnement Stripe : ' . $exception->getMessage());
            }

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $checkoutUrl = $stripeSubscriptionCheckoutManager->startSubscriptionCheckout(
                $prestataireProfile,
                $plan,
                $billingPeriod,
                $this->generateUrl('app_subscription_index', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?checkout=success&session_id={CHECKOUT_SESSION_ID}',
                $this->generateUrl('app_subscription_index', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?checkout=cancel',
            );
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Impossible de créer la session Stripe : ' . $exception->getMessage());

            return $this->redirectToRoute('app_subscription_index');
        }

        return $this->redirect($checkoutUrl);
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
}
