<?php

namespace App\Controller\Prestataire;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\QuoteRequestStatusEnum;
use App\Repository\ConversationRepository;
use App\Repository\QuoteProposalRepository;
use App\Repository\QuoteRequestRepository;
use App\Service\NotificationManager;
use App\Service\Subscription\SubscriptionAccessManager;
use App\Service\Subscription\SubscriptionCreditManager;
use App\Security\Voter\PrestataireCompanySettingsVoter;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/prestataire/demandes', name: 'app_prestataire_quote_request_')]
#[IsGranted('ROLE_PRESTATAIRE')]
/**
 * Gère les actions liées à prestataire quote request.
 */
final class PrestataireQuoteRequestController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        Request $request,
        QuoteRequestRepository $quoteRequestRepository,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        $queryBuilder = $quoteRequestRepository->createQueryBuilder('qr')
            ->where('qr.prestataire = :prestataire')
            ->andWhere('qr.deletedAt IS NULL')
            ->setParameter('prestataire', $prestataire)
            ->orderBy('qr.createdAt', 'DESC');

        $quoteRequests = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('prestataire/quote_request/show.html.twig', [
            'quoteRequests' => $quoteRequests,
        ]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    /**
     * Affiche le détail de la ressource demandée.
     *
     * @return Response
     */
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
        QuoteProposalRepository $quoteProposalRepository
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter cette demande.');
        }

        if ($quoteRequest->isDeleted()) {
            throw $this->createNotFoundException('Cette demande n’est plus disponible.');
        }

        $linkedProposal = $quoteProposalRepository->findOneVisibleByQuoteRequestAndPrestataire(
            $quoteRequest,
            $prestataire
        );
        $hasConversationPhotos = false;

        if (null !== $quoteRequest->getConversation()) {
            foreach ($quoteRequest->getConversation()->getMessages() as $message) {
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->getFileName()) {
                        $hasConversationPhotos = true;
                        break 2;
                    }
                }
            }
        }

        return $this->render('prestataire/quote_request/show.html.twig', [
            'quoteRequest' => $quoteRequest,
            'isArchivedView' => $quoteRequest->isArchivedByPrestataire(),
            'linkedProposal' => $linkedProposal,
            'hasConversationPhotos' => $hasConversationPhotos,
        ]);
    }

    #[Route('/{slug}/accept-study', name: 'accept_study', methods: ['POST'])]
    /**
     * Traite l’action "acceptStudy" du contrôleur Prestataire Quote Request.
     *
     * @return RedirectResponse
     */
    public function acceptStudy(
        Request $request,
        string $slug,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager,
        SubscriptionAccessManager $subscriptionAccessManager,
        SubscriptionCreditManager $subscriptionCreditManager,
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();
        $quoteRequest = $quoteRequestRepository->findOneBy([
            'slug' => $slug,
        ]);

        if (!$quoteRequest instanceof QuoteRequest || $quoteRequest->isDeleted()) {
            throw $this->createNotFoundException('Cette demande n’est plus disponible.');
        }

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas traiter cette demande.');
        }

        if (!$this->isGranted(PrestataireCompanySettingsVoter::PRESTATAIRE_HAS_COMPLETE_COMPANY_SETTINGS, $prestataire)) {
            $this->addFlash('warning', [
                'title' => 'Complétez les informations entreprise de vos paramètres avant d’accepter une demande de devis.',
                'items' => $this->getMissingPrestataireCompanySettingsLabels($prestataire),
            ]);

            return $this->redirectToRoute('app_prestataire_settings', [
                'tab' => 'company',
            ]);
        }

        if (!$subscriptionAccessManager->canRespondToQuoteRequests($prestataire)) {
            $this->addFlash('warning', 'Un abonnement actif avec au moins un crédit est requis pour accepter et traiter une demande de devis.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        if (!$this->isCsrfTokenValid('accept-study-' . $quoteRequest->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        if ($quoteRequest->getStatus() !== QuoteRequestStatusEnum::SUBMITTED) {
            $this->addFlash('warning', 'Cette demande ne peut plus être acceptée pour étude.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        try {
            $activeSubscription = $subscriptionAccessManager->requireQuoteResponseAccess($prestataire);

            $subscriptionCreditManager->consumeQuoteResponseCredit(
                $activeSubscription,
                $quoteRequest,
                'Consommation automatique d’un crédit lors de l’acceptation d’une demande de devis.'
            );
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
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

        $clientUser = $quoteRequest->getClient()?->getAccount();

        if ($clientUser instanceof User) {
            $notificationManager->notify(
                $clientUser,
                NotificationTypeEnum::QUOTE_REQUEST_ACCEPTED,
                'Demande acceptée',
                'Votre demande de prestation a été acceptée pour étude.',
                $this->generateUrl('app_quote_request_show', [
                    'slug' => $quoteRequest->getSlug(),
                ]),
                [
                    'quoteRequestId' => $quoteRequest->getId(),
                    'conversationId' => $conversation->getId(),
                    'prestataireId' => $quoteRequest->getPrestataire()?->getId(),
                ],
            );
        }

        $this->addFlash('success', 'Vous avez accepté d’étudier cette demande. La conversation avec le client est maintenant ouverte.');

        return $this->redirectToRoute('app_prestataire_quote_request_show', [
            'slug' => $quoteRequest->getSlug(),
        ]);
    }

    #[Route('/{slug}/deny', name: 'deny', methods: ['POST'])]
    /**
     * Traite l’action "deny" du contrôleur Prestataire Quote Request.
     *
     * @return RedirectResponse
     */
    public function deny(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager
    ): RedirectResponse {
        $user = $this->getUser();

        if ($quoteRequest->isDeleted()) {
            throw $this->createNotFoundException('Cette demande n’est plus disponible.');
        }

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
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        if ($quoteRequest->getStatus() !== QuoteRequestStatusEnum::SUBMITTED) {
            $this->addFlash('warning', 'Cette demande ne peut plus être refusée.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $quoteRequest->setStatus(QuoteRequestStatusEnum::DENIED);
        $quoteRequest->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $clientUser = $quoteRequest->getClient()?->getAccount();

        if ($clientUser instanceof User) {
            $notificationManager->notify(
                $clientUser,
                NotificationTypeEnum::QUOTE_REQUEST_DENIED,
                'Demande refusée',
                'Votre demande de prestation a été refusée par le prestataire.',
                $this->generateUrl('app_quote_request_show', [
                    'slug' => $quoteRequest->getSlug(),
                ]),
                [
                    'quoteRequestId' => $quoteRequest->getId(),
                    'prestataireId' => $quoteRequest->getPrestataire()?->getId(),
                ],
            );
        }

        $this->addFlash('success', 'Vous avez refusé cette demande.');

        return $this->redirectToRoute('app_prestataire_quote_request_show', [
            'slug' => $quoteRequest->getSlug(),
        ]);
    }

    #[Route('/{slug}/delete', name: 'delete', methods: ['POST'])]
    /**
     * Supprime la ressource demandée.
     *
     * @return RedirectResponse
     */
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette demande.');
        }

        if ($quoteRequest->isDeleted()) {
            $this->addFlash('warning', 'Cette demande a déjà été supprimée.');

            return $this->redirectToRoute('app_prestataire_quote_request_index');
        }

        if (
            !$this->isCsrfTokenValid(
                'delete-quote-request-' . $quoteRequest->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        if (null !== $quoteRequest->getConversation()) {
            $this->addFlash('warning', 'Cette demande ne peut plus être supprimée car une conversation est déjà liée.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $quoteRequest
            ->setDeletedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        if (!in_array(
            $quoteRequest->getStatus(),
            [QuoteRequestStatusEnum::SUBMITTED, QuoteRequestStatusEnum::DENIED],
            true
        )) {
            $this->addFlash('warning', 'Cette demande ne peut plus être supprimée dans son état actuel.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $entityManager->flush();

        $this->addFlash('success', 'La demande de devis a bien été supprimée.');

        return $this->redirectToRoute('app_prestataire_quote_request_index');
    }

    #[Route('/{slug}/archive', name: 'archive', methods: ['POST'])]
    /**
     * Traite l’action "archive" du contrôleur Prestataire Quote Request.
     *
     * @return RedirectResponse
     */
    public function archive(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas archiver cette demande.');
        }

        if ($quoteRequest->isDeleted()) {
            $this->addFlash('warning', 'Cette demande n’est plus disponible.');
            return $this->redirectToRoute('app_prestataire_dashboard', [
                'tab' => 'demandes',
                '_fragment' => 'demandes-main-panel',
            ]);
        }

        if (
            !$this->isCsrfTokenValid(
                'archive-quote-request-' . $quoteRequest->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_prestataire_dashboard', [
                'tab' => 'demandes',
                '_fragment' => 'demandes-main-panel',
            ]);
        }

        if ($quoteRequest->getArchivedByPrestataireAt() !== null) {
            $this->addFlash('info', 'Cette demande est déjà archivée.');

            return $this->redirectToRoute('app_prestataire_dashboard', [
                'tab' => 'archives',
                '_fragment' => 'archives-main-panel',
            ]);
        }

        $quoteRequest
            ->setArchivedByPrestataireAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'La demande a bien été archivée.');

        return $this->redirectToRoute('app_prestataire_dashboard', [
            'tab' => 'archives',
            '_fragment' => 'archives-main-panel',
        ]);
    }

    /**
     * @return list<string>
     */
    private function getMissingPrestataireCompanySettingsLabels(QuoteRequest|User|\App\Entity\PrestataireProfile $subject): array
    {
        $prestataire = $subject instanceof User ? $subject->getPrestataireProfile() : $subject;

        if ($subject instanceof QuoteRequest) {
            $prestataire = $subject->getPrestataire();
        }

        if (!$prestataire instanceof \App\Entity\PrestataireProfile) {
            return ['profil entreprise'];
        }

        $missingFields = [];

        if ($this->isBlank($prestataire->getCompanyName())) {
            $missingFields[] = 'Nom de l’entreprise';
        }

        if ($this->isBlank($prestataire->getSiret())) {
            $missingFields[] = 'Numéro SIRET';
        }

        if ($this->isBlank($prestataire->getSiren())) {
            $missingFields[] = 'Numéro SIREN';
        }

        if ($this->isBlank($prestataire->getStructureType())) {
            $missingFields[] = 'Forme juridique';
        }

        if ($this->isBlank($prestataire->getVatNumber())) {
            $missingFields[] = 'TVA intracommunautaire';
        }

        if ($this->isBlank($prestataire->getAddress())) {
            $missingFields[] = 'Adresse';
        }

        if ($this->isBlank($prestataire->getPostalCode())) {
            $missingFields[] = 'Code postal';
        }

        if ($this->isBlank($prestataire->getCity())) {
            $missingFields[] = 'Ville';
        }

        if ($this->isBlank($prestataire->getCountry())) {
            $missingFields[] = 'Pays';
        }

        return $missingFields;
    }

    private function isBlank(mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        if (\is_string($value)) {
            return '' === trim($value);
        }

        return false;
    }
}
