<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Repository\Subscription\SubscriptionCustomerRepository;
use App\Repository\Subscription\SubscriptionPlanRepository;
use App\Service\Subscription\StripeApiClient;
use App\Service\Subscription\SubscriptionAccessManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

#[Route('/prestataire/abonnements', name: 'app_subscription_')]
final class SubscriptionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionAccessManager $subscriptionAccessManager,
        StripeApiClient $stripeApiClient,
    ): Response {
        $prestataireProfile = $this->getPrestataireProfile();

        if ('success' === $request->query->get('checkout')) {
            $this->addFlash('success', 'Le paiement a bien ete initialise. Votre abonnement sera synchronise apres confirmation Stripe.');
        }

        if ('cancel' === $request->query->get('checkout')) {
            $this->addFlash('warning', 'Le paiement a ete annule.');
        }

        return $this->render('subscription/index.html.twig', [
            'plans' => $subscriptionPlanRepository->findActiveOrdered(),
            'currentSubscription' => $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile),
            'stripeConfigured' => $stripeApiClient->isConfigured(),
        ]);
    }

    #[Route('/checkout/{code}/{period}', name: 'checkout', methods: ['POST'])]
    public function checkout(
        string $code,
        string $period,
        Request $request,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        SubscriptionAccessManager $subscriptionAccessManager,
        StripeApiClient $stripeApiClient,
        EntityManagerInterface $entityManager,
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
        if (null !== $currentSubscription && null !== $currentSubscription->getPlan()) {
            $currentAmount = (float) ($currentSubscription->getPlan()->getAmountForPeriod($currentSubscription->getBillingPeriod()) ?? 0);
            $selectedAmount = (float) ($plan->getAmountForPeriod($billingPeriod) ?? 0);

            if (
                $selectedAmount > $currentAmount
                && $currentSubscription->getStripeSubscriptionId()
                && $currentSubscription->getStripeSubscriptionItemId()
            ) {
                try {
                    $stripeApiClient->updateSubscriptionPlan($currentSubscription, $plan, $billingPeriod);
                    $this->addFlash('success', 'La montée en gamme a été demandée à Stripe. Le prorata sera géré automatiquement.');
                } catch (Throwable $exception) {
                    $this->addFlash('danger', 'Impossible de mettre à jour l’abonnement Stripe : ' . $exception->getMessage());
                }

                return $this->redirectToRoute('app_subscription_index');
            }

            $this->addFlash('info', 'Pour gérer votre abonnement actuel, utilisez le portail client Stripe.');

            return $this->redirectToRoute('app_subscription_portal');
        }

        try {
            $customer = $this->resolveStripeCustomer(
                $prestataireProfile,
                $subscriptionCustomerRepository,
                $stripeApiClient,
                $entityManager,
            );

            $checkoutSession = $stripeApiClient->createCheckoutSession(
                $prestataireProfile,
                $plan,
                $billingPeriod,
                $this->generateUrl('app_subscription_index', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?checkout=success',
                $this->generateUrl('app_subscription_index', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?checkout=cancel',
                $customer,
            );
        } catch (Throwable $exception) {
            $this->addFlash('danger', 'Impossible de créer la session Stripe : ' . $exception->getMessage());

            return $this->redirectToRoute('app_subscription_index');
        }

        $checkoutUrl = $checkoutSession['url'] ?? null;
        if (!is_string($checkoutUrl) || '' === $checkoutUrl) {
            $this->addFlash('danger', 'Impossible de créer la session Stripe.');

            return $this->redirectToRoute('app_subscription_index');
        }

        return $this->redirect($checkoutUrl);
    }

    #[Route('/portal', name: 'portal', methods: ['GET', 'POST'])]
    public function portal(
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        StripeApiClient $stripeApiClient,
    ): RedirectResponse {
        $prestataireProfile = $this->getPrestataireProfile();

        if (!$stripeApiClient->isConfigured()) {
            $this->addFlash('danger', 'Stripe n’est pas configuré sur cet environnement.');

            return $this->redirectToRoute('app_subscription_index');
        }

        $customer = $subscriptionCustomerRepository->findOneByPrestataire($prestataireProfile);
        if (
            !$customer instanceof \App\Entity\Subscription\SubscriptionCustomer
            || '' === trim((string) $customer->getStripeCustomerId())
        ) {
            $this->addFlash('warning', 'Aucun compte de facturation Stripe n’est encore associé à votre profil.');

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $portalSession = $stripeApiClient->createBillingPortalSession(
                $customer,
                $this->generateUrl('app_subscription_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        } catch (Throwable $exception) {
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

    private function resolveStripeCustomer(
        \App\Entity\PrestataireProfile $prestataireProfile,
        SubscriptionCustomerRepository $subscriptionCustomerRepository,
        StripeApiClient $stripeApiClient,
        EntityManagerInterface $entityManager,
    ): \App\Entity\Subscription\SubscriptionCustomer {
        $customer = $subscriptionCustomerRepository->findOneByPrestataire($prestataireProfile);

        if (
            $customer instanceof \App\Entity\Subscription\SubscriptionCustomer
            && '' !== trim((string) $customer->getStripeCustomerId())
        ) {
            return $customer;
        }

        $stripeCustomer = $stripeApiClient->createCustomer($prestataireProfile);
        $stripeCustomerId = trim((string) ($stripeCustomer['id'] ?? ''));

        if ('' === $stripeCustomerId) {
            throw new \RuntimeException('Stripe n’a pas retourné d’identifiant client.');
        }

        $customer ??= (new \App\Entity\Subscription\SubscriptionCustomer())
            ->setPrestataireProfile($prestataireProfile);

        $customer
            ->setStripeCustomerId($stripeCustomerId)
            ->setBillingEmail($prestataireProfile->getAccount()?->getEmail())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($customer);
        $entityManager->flush();

        return $customer;
    }
}
