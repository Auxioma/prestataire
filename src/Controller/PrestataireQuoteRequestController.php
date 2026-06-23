<?php

namespace App\Controller;

use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Repository\QuoteRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\QuoteRequestStatusEnum;
use App\Enum\MessageTypeEnum;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;


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

    #[Route('/{id}/accept-study', name: 'accept_study', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function acceptStudy(
        Request $request,
        QuoteRequest $quoteRequest,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas traiter cette demande.');
        }

        if (!$this->isCsrfTokenValid('accept-study-' . $quoteRequest->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'id' => $quoteRequest->getId(),
            ]);
        }

        if ($quoteRequest->getStatus() !== QuoteRequestStatusEnum::SUBMITTED) {
            $this->addFlash('warning', 'Cette demande ne peut plus être acceptée pour étude.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'id' => $quoteRequest->getId(),
            ]);
        }

        $quoteRequest->setStatus(QuoteRequestStatusEnum::ACCEPTED);
        $quoteRequest->setUpdatedAt(new \DateTimeImmutable());

        $conversation = $conversationRepository->findOneByQuoteRequest($quoteRequest);

        if (!$conversation instanceof Conversation) {
            $conversation = new Conversation();
            $conversation
                ->setQuoteRequest($quoteRequest)
                ->setClient($quoteRequest->getClient())
                ->setPrestataire($quoteRequest->getPrestataire())
                ->touch();

            $entityManager->persist($conversation);

            $message = new Message();
            $message
                ->setConversation($conversation)
                ->setType(MessageTypeEnum::SYSTEM)
                ->setAuthor(null)
                ->setContent('Le prestataire a accepté d’étudier votre demande de devis.');

            $conversation->addMessage($message);
            $conversation->markLastMessageAt($message->getCreatedAt());

            $entityManager->persist($message);
        } else {
            $conversation->touch();
        }

        $entityManager->flush();

        $this->addFlash('success', 'Vous avez accepté d’étudier cette demande. La conversation avec le client est maintenant ouverte.');

        return $this->redirectToRoute('app_prestataire_quote_request_show', [
            'id' => $quoteRequest->getId(),
        ]);
    }

    #[Route('/{id}/deny', name: 'deny', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deny(
        Request $request,
        QuoteRequest $quoteRequest,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas traiter cette demande.');
        }

        if (!$this->isCsrfTokenValid('deny-quote-request-' . $quoteRequest->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'id' => $quoteRequest->getId(),
            ]);
        }

        if ($quoteRequest->getStatus() !== QuoteRequestStatusEnum::SUBMITTED) {
            $this->addFlash('warning', 'Cette demande ne peut plus être refusée.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'id' => $quoteRequest->getId(),
            ]);
        }

        $quoteRequest->setStatus(QuoteRequestStatusEnum::DENIED);
        $quoteRequest->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'Vous avez refusé cette demande.');

        return $this->redirectToRoute('app_prestataire_quote_request_show', [
            'id' => $quoteRequest->getId(),
        ]);
    }
}
