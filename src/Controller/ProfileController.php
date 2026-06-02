<?php

namespace App\Controller;

use App\Entity\PrestataireProfile;
use App\Form\AccountSettingsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/compte/parametres', name: 'app_prestataire_settings')]
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
            
            // On établit la relation bidirectionnelle
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

            // Grâce au cascade: ['persist'] dans l'entité User, les deux tables se mettent à jour
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées avec succès !');

            return $this->redirectToRoute('app_prestataire_settings');
        }

        // 5. Envoi à la vue
        return $this->render('profile/prestataire_profile.html.twig', [
            'settingsForm' => $form->createView(),
            'user' => $user,
        ]);
    }
}