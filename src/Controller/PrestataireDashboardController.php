<?php

namespace App\Controller;

use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\QuoteRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use App\Entity\Message;
use App\Form\MessageType;
use App\Enum\MessageTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RealtimeNotifier;

final class PrestataireDashboardController extends AbstractController
{
    #[Route('/prestataire/espace-pro', name: 'app_prestataire_dashboard', methods: ['GET'])]
    public function index(
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_PRESTATAIRE')) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        $prestataireProfile = $prestataireProfileRepository->findOneBy([
            'account' => $user,
        ]);

        if (!$prestataireProfile) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        $prestations = $prestataireServiceRepository->findBy(
            ['prestataire' => $prestataireProfile],
            ['updatedAt' => 'DESC', 'createdAt' => 'DESC']
        );

        $quoteSort = $request->query->get('quote_sort', 'recent');

        $quoteOrderBy = match ($quoteSort) {
            'oldest' => ['createdAt' => 'ASC'],
            'budget_asc' => ['budgetAmount' => 'ASC'],
            'budget_desc' => ['budgetAmount' => 'DESC'],
            default => ['createdAt' => 'DESC'],
        };

        $quoteRequests = $quoteRequestRepository->findBy(
            ['prestataire' => $prestataireProfile],
            $quoteOrderBy
        );

        $conversationId = $request->query->get('conversation');
        $conversations = $conversationRepository->findBy(
            ['prestataire' => $prestataireProfile],
            ['lastMessageAt' => 'DESC', 'createdAt' => 'DESC']
        );

        $activeConversation = null;
        $activeTab = $request->query->get('tab', 'dashboard');

        if (!empty($conversations)) {
            if (is_string($conversationId) || is_numeric($conversationId)) {
                foreach ($conversations as $conversation) {
                    if ((string) $conversation->getId() === (string) $conversationId) {
                        $activeConversation = $conversation;
                        break;
                    }
                }
            }

            if (!$activeConversation instanceof Conversation) {
                $activeConversation = $conversations[0];
            }
        }

        $messageForm = null;

        if ($activeConversation) {
            $message = new Message();

            $messageForm = $this->createForm(MessageType::class, $message, [
                'action' => $this->generateUrl('app_prestataire_conversation_message_send', [
                    'id' => $activeConversation->getId(),
                ]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('prestataire_dashboard/prestataire_dashboard.html.twig', [
            'user' => $user,
            'prestataireProfile' => $prestataireProfile,
            'prestations' => $prestations,
            'quoteRequests' => $quoteRequests,
            'quoteSort' => $quoteSort,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messageForm' => $messageForm,
            'activeTab' => $activeTab,
        ]);
    }

    #[Route('/prestataire/espace-pro/conversation/{id}/message', name: 'app_prestataire_conversation_message_send', methods: ['POST'])]
    public function sendMessage(
        #[MapEntity(id: 'id')] Conversation $conversation,
        Request $request,
        EntityManagerInterface $entityManager,
        RealtimeNotifier $realtimeNotifier,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataireProfile = $user->getPrestataireProfile();

        if ($conversation->getPrestataire()?->getId() !== $prestataireProfile->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas répondre à cette conversation.');
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setConversation($conversation);
            $message->setAuthor($user);
            $message->setType(MessageTypeEnum::USER);

            if (method_exists($conversation, 'setLastMessageAt')) {
                $conversation->setLastMessageAt(new \DateTimeImmutable());
            }

            if (method_exists($conversation, 'setUpdatedAt')) {
                $conversation->setUpdatedAt(new \DateTimeImmutable());
            }

            $entityManager->persist($message);
            $entityManager->flush();

            $realtimeNotifier->notifyMessageCreated($conversation->getId(), $message);
        }

        return $this->redirectToRoute('app_prestataire_dashboard', [
            'conversation' => $conversation->getId(),
            'tab' => 'messages',
            '_fragment' => 'messages-main-panel',
        ], 303);
    }
}
