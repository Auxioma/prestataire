<?php

declare(strict_types=1);

namespace App\Controller\Prestataire;

use App\Entity\Invoice;
use App\Entity\PrestataireRevenueEntry;
use App\Entity\User;
use App\Service\PrestataireRevenueManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PRESTATAIRE')]
final class PrestataireRevenueController extends AbstractController
{
    private function resolveRevenueSubtab(Request $request): string
    {
        $subtab = (string) $request->request->get('revenues_subtab', 'summary');

        return \in_array($subtab, ['summary', 'history', 'payouts'], true) ? $subtab : 'summary';
    }

    #[Route('/prestataire/espace-pro/revenus/facture/{id}/payer', name: 'app_prestataire_revenue_invoice_mark_paid', methods: ['POST'])]
    public function markInvoicePaid(
        Request $request,
        Invoice $invoice,
        PrestataireRevenueManager $prestataireRevenueManager,
    ): Response {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || $invoice->getPrestataire()?->getAccount()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$this->isCsrfTokenValid('mark_invoice_paid_' . $invoice->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $prestataireRevenueManager->markInvoiceAsPaid($invoice);
        $this->addFlash('success', 'La facture est maintenant marquée comme payée.');

        return $this->redirectToRoute('app_prestataire_dashboard', [
            'tab' => 'revenus',
            'revenues_subtab' => $this->resolveRevenueSubtab($request),
            '_fragment' => 'revenus-main-panel',
        ], 303);
    }

    #[Route('/prestataire/espace-pro/revenus/externe/{id}/payer', name: 'app_prestataire_revenue_manual_mark_paid', methods: ['POST'])]
    public function markManualRevenuePaid(
        Request $request,
        PrestataireRevenueEntry $entry,
        PrestataireRevenueManager $prestataireRevenueManager,
    ): Response {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || $entry->getPrestataire()?->getAccount()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$this->isCsrfTokenValid('mark_manual_revenue_paid_' . $entry->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $prestataireRevenueManager->markManualRevenueAsPaid($entry);
        $this->addFlash('success', 'Le revenu externe est maintenant marqué comme payé.');

        return $this->redirectToRoute('app_prestataire_dashboard', [
            'tab' => 'revenus',
            'revenues_subtab' => $this->resolveRevenueSubtab($request),
            '_fragment' => 'revenus-main-panel',
        ], 303);
    }

    #[Route('/prestataire/espace-pro/revenus/externe/{id}/supprimer', name: 'app_prestataire_revenue_manual_delete', methods: ['POST'])]
    public function deleteManualRevenue(
        Request $request,
        PrestataireRevenueEntry $entry,
        PrestataireRevenueManager $prestataireRevenueManager,
    ): Response {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || $entry->getPrestataire()?->getAccount()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$this->isCsrfTokenValid('delete_manual_revenue_' . $entry->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $prestataireRevenueManager->deleteManualRevenue($entry);
        $this->addFlash('success', 'Le revenu externe a bien été supprimé.');

        return $this->redirectToRoute('app_prestataire_dashboard', [
            'tab' => 'revenus',
            'revenues_subtab' => $this->resolveRevenueSubtab($request),
            '_fragment' => 'revenus-main-panel',
        ], 303);
    }

    #[Route('/prestataire/espace-pro/revenus/externe/{id}/annuler-paiement', name: 'app_prestataire_revenue_manual_mark_unpaid', methods: ['POST'])]
    public function markManualRevenueUnpaid(
        Request $request,
        PrestataireRevenueEntry $entry,
        PrestataireRevenueManager $prestataireRevenueManager,
    ): Response {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || $entry->getPrestataire()?->getAccount()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$this->isCsrfTokenValid('mark_manual_revenue_unpaid_' . $entry->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $prestataireRevenueManager->markManualRevenueAsUnpaid($entry);
        $this->addFlash('success', 'Le revenu externe est repassé en attente de paiement.');

        return $this->redirectToRoute('app_prestataire_dashboard', [
            'tab' => 'revenus',
            'revenues_subtab' => $this->resolveRevenueSubtab($request),
            '_fragment' => 'revenus-main-panel',
        ], 303);
    }
}
