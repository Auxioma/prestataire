<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Website\Controller;

use App\Website\Dto\ContactRequest;
use App\Website\Form\ContactRequestType;
use App\Website\Service\ContactRequestMailer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        ContactRequestMailer $contactRequestMailer,
        LoggerInterface $logger,
        #[Autowire(service: 'limiter.contact_form')]
        RateLimiterFactory $contactFormLimiter,
    ): Response {
        $contactRequest = new ContactRequest();
        $form = $this->createForm(ContactRequestType::class, $contactRequest);
        $form->handleRequest($request);
        $status = Response::HTTP_OK;

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $contactFormLimiter->create($request->getClientIp() ?? 'unknown');
            if (!$limiter->consume()->isAccepted()) {
                $form->addError(new FormError('Trop de demandes ont été envoyées. Veuillez réessayer dans quelques minutes.'));
                $status = Response::HTTP_TOO_MANY_REQUESTS;
            } else {
                try {
                    $contactRequestMailer->send($contactRequest);
                } catch (TransportExceptionInterface|\LogicException $exception) {
                    $logger->error('Échec de l’envoi du formulaire de contact.', [
                        'error_type' => $exception::class,
                    ]);
                    $form->addError(new FormError('Votre message n’a pas pu être envoyé. Veuillez réessayer ultérieurement.'));
                    $status = Response::HTTP_SERVICE_UNAVAILABLE;
                }

                if (Response::HTTP_OK === $status) {
                    $this->addFlash('success', 'Votre message a bien été transmis à notre équipe.');

                    return $this->redirectToRoute('app_contact');
                }
            }
        }

        return $this->render('Website/contact/index.html.twig', [
            'contactForm' => $form,
        ], new Response(status: $status));
    }
}
