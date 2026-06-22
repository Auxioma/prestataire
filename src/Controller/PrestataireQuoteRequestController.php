<?php

namespace App\Controller;

use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Repository\QuoteRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prestataire/demandes', name: 'app_prestataire_quote_request_')]
final class PrestataireQuoteRequestController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(QuoteRequestRepository $quoteRequestRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        $quoteRequests = $quoteRequestRepository->findBy(
            ['prestataire' => $prestataire],
            ['createdAt' => 'DESC']
        );

        return $this->render('prestataire_quote_request/index.html.twig', [
            'quoteRequests' => $quoteRequests,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(QuoteRequest $quoteRequest): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter cette demande.');
        }

        return $this->render('prestataire_quote_request/show.html.twig', [
            'quoteRequest' => $quoteRequest,
        ]);
    }
}