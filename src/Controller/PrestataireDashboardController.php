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

        return $this->render('prestataire_dashboard/prestataire_dashboard.html.twig', [
            'user' => $user,
            'prestataireProfile' => $prestataireProfile,
            'prestations' => $prestations,
            'quoteRequests' => $quoteRequests,
            'quoteSort' => $quoteSort,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
        ]);
    }
}
