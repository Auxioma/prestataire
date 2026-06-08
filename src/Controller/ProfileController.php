<?php

namespace App\Controller;

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Form\AccountSettingsType;
use App\Form\ClientProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/prestataire/parametres', name: 'app_prestataire_settings')]
    public function settings(Request $request, EntityManagerInterface $entityManager): Response
    {
        // 1. Récupérer l'utilisateur connecté
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Si le prestataire n'a pas encore de profil créé en BDD, on l'initialise à la volée
        if ($user->getPrestataireProfile() === null) {
            $profile = new PrestataireProfile();
            
            $user->setPrestataireProfile($profile);
            $profile->setAccount($user);
        }

        // 3. Création du formulaire global (User + sous-formulaire Prestataire)
        $form = $this->createForm(AccountSettingsType::class, $user);
        $form->handleRequest($request);


        // 4. Traitement de la soumission
        if ($form->isSubmitted() && $form->isValid()) {
            
            // Générer le slug à partir du nom de l'entreprise si nécessaire
            $profile = $user->getPrestataireProfile();
            if ($profile && $profile->getCompanyName()) {
                $profile->setSlug(strtolower(str_replace(' ', '-', $profile->getCompanyName())));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // 💡 AJOUT ICI : On vide les fichiers de l'entité pour éviter l'erreur de sérialisation en session
            if ($profile) {
                $profile->setLogoFile(null);
                $profile->setCoverImageFile(null);
            }

            $this->addFlash('success', 'Vos modifications ont été enregistrées avec succès !');

            return $this->redirectToRoute('app_prestataire_settings');
        }

        // 5. Envoi à la vue
        return $this->render('profile/prestataire_profile.html.twig', [
            'settingsForm' => $form->createView(),
            'user' => $user,
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