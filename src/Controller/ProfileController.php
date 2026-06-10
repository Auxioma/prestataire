<?php

namespace App\Controller;

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Form\AccountSettingsType;
use App\Form\PrestataireCompanyTabType;
use App\Form\PrestataireServiceType;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/prestataire/parametres', name: 'app_prestataire_settings')]
    public function settings(
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $categoryRepository,
        FormFactoryInterface $formFactory
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->getPrestataireProfile() === null) {
            $profile = new PrestataireProfile();
            $user->setPrestataireProfile($profile);
            $profile->setAccount($user);
        }

        $prestataireProfile = $user->getPrestataireProfile();

        $userForm = $formFactory->createNamed(
            'user_profile_form',
            \App\Form\UserProfileTabType::class,
            $user
        );

        $companyForm = $formFactory->createNamed(
            'company_form',
            PrestataireCompanyTabType::class,
            $prestataireProfile
        );

        $publicProfileForm = $formFactory->createNamed(
            'public_profile_form',
            \App\Form\PrestatairePublicProfileTabType::class,
            $prestataireProfile
        );
        $userForm->handleRequest($request);
        $publicProfileForm->handleRequest($request);
        $companyForm->handleRequest($request);

        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations personnelles ont été enregistrées.');
            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'profile-panel']);
        }

        if ($publicProfileForm->isSubmitted() && $publicProfileForm->isValid()) {
            if ($prestataireProfile && $prestataireProfile->getCompanyName()) {
                $prestataireProfile->setSlug(strtolower(str_replace(' ', '-', $prestataireProfile->getCompanyName())));
            }

            $entityManager->persist($prestataireProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil public a été enregistré.');
            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'profile-panel']);
        }

        if ($companyForm->isSubmitted() && $companyForm->isValid()) {
            if ($prestataireProfile && $prestataireProfile->getCompanyName()) {
                $prestataireProfile->setSlug(strtolower(str_replace(' ', '-', $prestataireProfile->getCompanyName())));
            }

            $entityManager->persist($prestataireProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Les informations de l’entreprise ont été enregistrées.');
            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'company-panel']);
        }

        return $this->render('profile/prestataire_profile.html.twig', [
            'userForm' => $userForm->createView(),
            'publicProfileForm' => $publicProfileForm->createView(),
            'companyForm' => $companyForm->createView(),
            'user' => $user,
            'categories' => $categoryRepository->findWithSubCategories(),
        ]);
    }

    // Ajouter un service au profil
    #[Route('/prestataire/service/ajouter', name: 'app_prestataire_add_service', methods: ['POST'])]
    public function addService(Request $request, EntityManagerInterface $em, ServiceRepository $serviceRepo): Response
    {
        $serviceId = $request->request->get('service_id');
        $user = $this->getUser();
        $service = $serviceRepo->find($serviceId);


        // 1. Vérification de base (est-ce qu'on a bien un service et un profil ?)
        if (!$service || !($user instanceof \App\Entity\User) || !$user->getPrestataireProfile()) {
            $this->addFlash('error', 'Une erreur est survenue.');
            return $this->redirectToRoute('app_prestataire_settings');
        }

        // 2. Vérification d'existence (est-ce que le service est déjà lié ?)
        $exists = $em->getRepository(PrestataireService::class)->findOneBy([
            'prestataire' => $user->getPrestataireProfile(),
            'service' => $service
        ]);

        if ($exists) {
            $this->addFlash('warning', 'Vous proposez déjà ce service !');
        } else {
            // 3. Persistance
            $pService = new PrestataireService();
            $pService->setPrestataire($user->getPrestataireProfile());
            $pService->setService($service);

            $em->persist($pService);
            $em->flush();
            $this->addFlash('success', 'Service ajouté !');
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }

    // Suppression d'un service
    #[Route('/prestataire/service/supprimer/{id}', name: 'app_prestataire_service_delete', methods: ['POST'])]
    public function delete(Request $request, PrestataireService $ps, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ps->getId(), $request->request->get('_token'))) {
            $em->remove($ps);
            $em->flush();
            $this->addFlash('success', 'Le service a bien été retiré de votre profil.');
        }

        // On redirige vers la route nommée avec l'ancre
        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }

    // Edition d'un service
    #[Route('/prestataire/service/editer/{id}', name: 'app_prestataire_service_edit')]
    public function edit(Request $request, PrestataireService $ps, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PrestataireServiceType::class, $ps);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Tarifs mis à jour !');
            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
        }

        return $this->render('prestataire/edit_service.html.twig', [
            'form' => $form->createView(),
            'ps' => $ps
        ]);
    }


    /**
     * PARAMETRES PROFILE CLIENT
     */
    #[Route('/client/parametres', name: 'app_client_settings')]
    public function clientSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Initialisation à la volée du ClientProfile s'il n'existe pas encore
        if ($user->getClientProfile() === null) {
            $profile = new ClientProfile();

            $user->setClientProfile($profile);
            $profile->setAccount($user);
        }

        // 2. Utiliser le formulaire global AccountSettingsType lié au $user
        $form = $this->createForm(AccountSettingsType::class, $user);
        $form->handleRequest($request);

        // 3. Traitement de la soumission
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil client a été mis à jour avec succès !');

            return $this->redirectToRoute('app_client_settings');
        }

        // 4. Envoi à la vue client dédiée
        return $this->render('profile/client_profile.html.twig', [
            'settingsForm' => $form->createView(),
            'user' => $user,
        ]);
    }
}
