<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Form\MessageType;
use App\Form\QuoteRequestType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\QuoteRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/demandes-de-devis', name: 'app_quote_request')]
final class QuoteRequestController extends AbstractController
{
    #[Route('', name: '_index', methods: ['GET'])]
    public function index(QuoteRequestRepository $quoteRequestRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $quoteRequests = $quoteRequestRepository->findBy(
            ['client' => $user->getClientProfile()],
            ['createdAt' => 'DESC']
        );

        return $this->render('quote_request/index.html.twig', [
            'quoteRequests' => $quoteRequests,
        ]);
    }

    #[Route('/nouvelle', name: '_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PrestataireServiceRepository $prestataireServiceRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $quoteRequest = new QuoteRequest();
        $quoteRequest->setClient($user->getClientProfile());

        $prestation = null;
        $prestataire = null;

        $prestationId = $request->query->get('prestation');
        $prestataireId = $request->query->get('prestataire');

        if (!$prestationId && !$prestataireId) {
            throw $this->createNotFoundException('Contexte manquant pour créer une demande de devis.');
        }

        if ($prestationId) {
            $prestation = $prestataireServiceRepository->find($prestationId);

            if (!$prestation || !$prestation->isActive()) {
                throw $this->createNotFoundException('Prestation introuvable.');
            }

            $prestataire = $prestation->getPrestataire();

            if (!$prestataire) {
                throw $this->createNotFoundException('Prestataire introuvable.');
            }

            $quoteRequest->setPrestation($prestation);
            $quoteRequest->setPrestataire($prestataire);
        } else {
            $prestataire = $prestataireProfileRepository->find($prestataireId);

            if (!$prestataire) {
                throw $this->createNotFoundException('Prestataire introuvable.');
            }

            $activePrestations = $prestataire
                ->getPrestataireServices()
                ->filter(static fn($ps) => $ps->isActive());

            if ($activePrestations->isEmpty()) {
                $this->addFlash('warning', 'Ce prestataire ne propose actuellement aucune prestation disponible pour une demande de devis.');

                return $this->redirectToRoute('app_prestataire_show', [
                    'slug' => $prestataire->getSlug(),
                ]);
            }

            $quoteRequest->setPrestataire($prestataire);
        }

        $form = $this->createForm(QuoteRequestType::class, $quoteRequest, [
            'prestataire' => $prestataire,
            'locked_prestation' => null !== $prestation,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $selectedPrestation = $quoteRequest->getPrestation();

            if (!$selectedPrestation) {
                $form->addError(new FormError('Veuillez sélectionner un service.'));
            } elseif ($selectedPrestation->getPrestataire()?->getId() !== $prestataire?->getId()) {
                $form->addError(new FormError('Le service sélectionné ne correspond pas au prestataire choisi.'));
            } elseif (!$selectedPrestation->isActive()) {
                $form->addError(new FormError('Le service sélectionné n’est pas disponible.'));
            }

            if ($form->isValid()) {
                $quoteRequest->setPrestataire($selectedPrestation->getPrestataire());
                $quoteRequest->setUpdatedAt(new \DateTimeImmutable());

                $baseSlug = $slugger
                    ->slug($quoteRequest->getTitle() ?: 'demande-de-devis')
                    ->lower()
                    ->toString();

                $quoteRequest->setSlug($baseSlug . '-' . substr(uniqid(), -6));

                $entityManager->persist($quoteRequest);
                $entityManager->flush();

                $this->addFlash('success', 'Votre demande de devis a bien été envoyée.');

                return $this->redirectToRoute('app_quote_request_show', [
                    'slug' => $quoteRequest->getSlug(),
                ]);
            }
        }

        return $this->render('quote_request/new.html.twig', [
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'prestataire' => $prestataire,
            'prestation' => $prestation,
        ]);
    }

    #[Route('/{slug}', name: '_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        if ($quoteRequest->getClient()?->getId() !== $user->getClientProfile()?->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter cette demande.');
        }

        $conversation = $conversationRepository->findOneByQuoteRequest($quoteRequest);
        $messages = $conversation ? $messageRepository->findByConversationOrderedByCreatedAt($conversation) : [];

        $messageForm = null;
        $canSendMessage = $conversation && \in_array($quoteRequest->getStatus()->value, ['accepted', 'answered'], true);

        if ($canSendMessage) {
            $message = new Message();
            $message->setConversation($conversation);
            $message->setAuthor($user);

            $messageForm = $this->createForm(MessageType::class, $message);
            $messageForm->handleRequest($request);

            if ($messageForm->isSubmitted() && $messageForm->isValid()) {
                $entityManager->persist($message);

                $quoteRequest->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();

                return $this->redirect(
                    $this->generateUrl('app_quote_request_show', [
                        'slug' => $quoteRequest->getSlug(),
                    ]) . '#quote-message-form'
                );
            }
        }

        return $this->render('quote_request/show.html.twig', [
            'quoteRequest' => $quoteRequest,
            'conversation' => $conversation,
            'messages' => $messages,
            'messageForm' => $messageForm?->createView(),
        ]);
    }
}
