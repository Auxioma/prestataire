<?php

declare(strict_types=1);

namespace App\Controller\Prestataire;

use App\Controller\AbstractInvoiceController;
use App\Entity\Invoice;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\User;
use App\Enum\NotificationTypeEnum;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\QuoteProposalRepository;
use App\Service\InvoiceDocumentResolver;
use App\Service\InvoiceManager;
use App\Service\InvoicePdfGenerator;
use App\Service\NotificationManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class InvoiceController extends AbstractInvoiceController
{
    #[Route('/prestataire/devis/{publicReference}/facture', name: 'app_prestataire_invoice_manage', methods: ['GET', 'POST'])]
    public function manage(
        string $publicReference,
        Request $request,
        QuoteProposalRepository $quoteProposalRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        InvoiceManager $invoiceManager,
        NotificationManager $notificationManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $proposal = $this->getPrestataireProposal(
            $publicReference,
            $quoteProposalRepository,
            $prestataireProfileRepository,
        );

        if (!$proposal->isAccepted()) {
            throw $this->createNotFoundException('La facture n’est disponible qu’après acceptation du devis.');
        }

        $invoice = $invoiceManager->getOrCreateFromAcceptedQuote($proposal);

        if ($invoice->isIssued()) {
            return $this->redirectToRoute('app_prestataire_invoice_show', [
                'publicReference' => $proposal->getPublicReference(),
            ], 303);
        }

        $form = $this->createForm(InvoiceType::class, $invoice, [
            'internal_source_type' => $invoice->getQuoteProposal()?->usesExternalPdfDocument()
                ? \App\Enum\InvoiceSourceTypeEnum::MANUAL_FROM_EXTERNAL_QUOTE
                : \App\Enum\InvoiceSourceTypeEnum::GENERATED_FROM_QUOTE,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $invoiceManager->saveDraft($invoice, true);

                if ($request->request->has('issue_invoice')) {
                    $invoiceManager->issue($invoice);
                    $this->notifyClientInvoiceAvailable($proposal, $invoice, $notificationManager);

                    $this->addFlash('success', 'La facture a bien été émise.');

                    return $this->redirectToRoute('app_prestataire_invoice_show', [
                        'publicReference' => $proposal->getPublicReference(),
                    ], 303);
                }

                $this->addFlash('success', 'La facture a bien été enregistrée.');

                return $this->redirectToRoute('app_prestataire_invoice_manage', [
                    'publicReference' => $proposal->getPublicReference(),
                ]);
            } catch (\DomainException $exception) {
                $this->addFlash('warning', $exception->getMessage());
            }
        }

        $statusCode = $form->isSubmitted() && !$form->isValid()
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('invoice/edit.html.twig', [
            'invoice' => $invoice,
            'proposal' => $proposal,
            'form' => $form->createView(),
        ], new Response(status: $statusCode));
    }

    #[Route('/prestataire/devis/{publicReference}/facture/emettre', name: 'app_prestataire_invoice_issue', methods: ['POST'])]
    public function issue(
        string $publicReference,
        Request $request,
        QuoteProposalRepository $quoteProposalRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        InvoiceManager $invoiceManager,
        NotificationManager $notificationManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $proposal = $this->getPrestataireProposal(
            $publicReference,
            $quoteProposalRepository,
            $prestataireProfileRepository,
        );

        if (!$this->isCsrfTokenValid('issue_invoice_' . $proposal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $invoice = $invoiceManager->getOrCreateFromAcceptedQuote($proposal);
            $invoiceManager->issue($invoice);
            $this->notifyClientInvoiceAvailable($proposal, $invoice, $notificationManager);

            $this->addFlash('success', 'La facture a bien été émise.');
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute('app_prestataire_invoice_manage', [
            'publicReference' => $proposal->getPublicReference(),
        ], 303);
    }

    #[Route('/prestataire/devis/{publicReference}/facture/apercu', name: 'app_prestataire_invoice_show', methods: ['GET'])]
    public function show(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $invoice = $this->getPrestataireInvoice(
            $publicReference,
            $invoiceRepository,
            $prestataireProfileRepository,
        );

        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
            'proposal' => $invoice->getQuoteProposal(),
            'viewerContext' => 'prestataire',
        ]);
    }

    #[Route('/prestataire/devis/{publicReference}/facture/pdf', name: 'app_prestataire_invoice_pdf', methods: ['GET'])]
    public function showPdfForPrestataire(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        InvoiceDocumentResolver $documentResolver,
        InvoicePdfGenerator $pdfGenerator,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $invoice = $this->getPrestataireInvoice(
            $publicReference,
            $invoiceRepository,
            $prestataireProfileRepository,
        );

        return $this->createPdfResponse($invoice, $documentResolver, $pdfGenerator);
    }

    #[Route('/prestataire/devis/{publicReference}/facture/xml', name: 'app_prestataire_invoice_xml', methods: ['GET'])]
    public function downloadXmlForPrestataire(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        InvoiceDocumentResolver $documentResolver,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $invoice = $this->getPrestataireInvoice(
            $publicReference,
            $invoiceRepository,
            $prestataireProfileRepository,
        );

        $xmlPath = $documentResolver->getXmlPath($invoice);
        if ($xmlPath === null) {
            throw $this->createNotFoundException('Le XML de préparation Factur-X n’est pas disponible.');
        }

        $response = new BinaryFileResponse($xmlPath);
        $response->headers->set('Content-Type', 'application/xml');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('%s.xml', $invoice->getInvoiceNumber() ?: 'facture')
        );

        return $response;
    }

    #[Route('/prestataire/devis/{publicReference}/facture/xml/apercu', name: 'app_prestataire_invoice_xml_preview', methods: ['GET'])]
    public function previewXmlForPrestataire(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        InvoiceDocumentResolver $documentResolver,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $invoice = $this->getPrestataireInvoice(
            $publicReference,
            $invoiceRepository,
            $prestataireProfileRepository,
        );

        $xmlPath = $documentResolver->getXmlPath($invoice);
        if ($xmlPath === null) {
            throw $this->createNotFoundException('Le XML de préparation Factur-X n’est pas disponible.');
        }

        $response = new BinaryFileResponse($xmlPath);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            sprintf('%s.xml', $invoice->getInvoiceNumber() ?: 'facture')
        );

        return $response;
    }

    private function notifyClientInvoiceAvailable(
        QuoteProposal $proposal,
        Invoice $invoice,
        NotificationManager $notificationManager,
    ): void {
        $clientUser = $proposal->getClient()?->getAccount();
        if (!$clientUser instanceof User) {
            return;
        }

        $notificationManager->notify(
            $clientUser,
            NotificationTypeEnum::INVOICE_RECEIVED,
            'Nouvelle facture disponible',
            'Une nouvelle facture liée à votre devis est disponible.',
            $this->generateUrl('app_quote_request_invoice_show', [
                'publicReference' => $proposal->getPublicReference(),
            ]),
            [
                'invoiceId' => $invoice->getId(),
                'invoiceNumber' => $invoice->getInvoiceNumber(),
                'quoteProposalId' => $proposal->getId(),
                'quoteProposalReference' => $proposal->getPublicReference(),
                'quoteProposalNumber' => $proposal->getProposalNumber(),
                'quoteRequestId' => $proposal->getQuoteRequest()?->getId(),
                'quoteRequestSlug' => $proposal->getQuoteRequest()?->getSlug(),
            ]
        );
    }

    private function getCurrentPrestataire(PrestataireProfileRepository $prestataireProfileRepository): PrestataireProfile
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        $prestataire = $prestataireProfileRepository->findOneBy(['account' => $user]);

        if (!$prestataire instanceof PrestataireProfile) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        return $prestataire;
    }

    private function getPrestataireProposal(
        string $publicReference,
        QuoteProposalRepository $quoteProposalRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
    ): QuoteProposal {
        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReference(
            $publicReference,
            $this->getCurrentPrestataire($prestataireProfileRepository),
        );

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        return $proposal;
    }

    private function getPrestataireInvoice(
        string $publicReference,
        InvoiceRepository $invoiceRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
    ): Invoice {
        $invoice = $invoiceRepository->findOneForPrestataireByProposalReference(
            $publicReference,
            $this->getCurrentPrestataire($prestataireProfileRepository),
        );

        if (!$invoice instanceof Invoice) {
            throw $this->createNotFoundException('Facture introuvable.');
        }

        return $invoice;
    }
}
