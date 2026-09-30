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

namespace App\Website\Content;

final class FaqContent
{
    /**
     * @return array<string, array{
     *     label: string,
     *     icon: string,
     *     questions: list<array{question: string, answer: string}>
     * }>
     */
    public static function categories(): array
    {
        return [
            'account' => [
                'label' => 'Compte et inscription',
                'icon' => 'fa-regular fa-user',
                'questions' => [
                    [
                        'question' => 'Comment créer un compte sur TrouveMoi.com ?',
                        'answer' => 'Depuis la page d’inscription, choisissez un compte client ou prestataire, complétez le formulaire puis confirmez votre adresse grâce au lien reçu par e-mail.',
                    ],
                    [
                        'question' => 'Je n’ai pas reçu mon e-mail de confirmation, que faire ?',
                        'answer' => 'Vérifiez d’abord vos courriers indésirables. Vous pouvez ensuite ouvrir la page de connexion, déplier « E-mail de confirmation non reçu ? » et demander un nouveau message avec l’adresse utilisée lors de l’inscription.',
                    ],
                    [
                        'question' => 'Comment réinitialiser mon mot de passe ?',
                        'answer' => 'Sur la page de connexion, cliquez sur « Mot de passe oublié ? », saisissez votre adresse e-mail puis suivez le lien sécurisé reçu dans votre messagerie.',
                    ],
                    [
                        'question' => 'Où modifier mes informations personnelles ?',
                        'answer' => 'Une fois connecté, ouvrez les paramètres de votre compte. Vous pourrez y mettre à jour les informations modifiables de votre profil.',
                    ],
                    [
                        'question' => 'Pourquoi mon adresse e-mail n’est-elle pas modifiable dans les paramètres ?',
                        'answer' => 'Cette adresse identifie votre compte et sert aux communications importantes. Elle reste visible dans vos informations personnelles, mais elle est volontairement protégée contre la modification directe.',
                    ],
                    [
                        'question' => 'Quelle différence existe-t-il entre un compte client et un compte prestataire ?',
                        'answer' => 'Le compte client sert à rechercher des professionnels, demander des devis et suivre ses échanges. Le compte prestataire permet de présenter son activité, proposer des prestations et répondre aux demandes.',
                    ],
                    [
                        'question' => 'Pourquoi le SIRET et le code NAF sont-ils vérifiés pour un prestataire ?',
                        'answer' => 'Le SIRET permet de retrouver le code NAF de l’entreprise. L’inscription professionnelle peut continuer lorsque ce code correspond à une activité acceptée par la plateforme.',
                    ],
                ],
            ],
            'search' => [
                'label' => 'Recherche et devis',
                'icon' => 'fa-solid fa-magnifying-glass',
                'questions' => [
                    [
                        'question' => 'Comment trouver un prestataire près de chez moi ?',
                        'answer' => 'Utilisez la recherche en choisissant une catégorie, une prestation et une localisation. Les profils proposés tiennent compte des services et zones d’intervention déclarés par les prestataires.',
                    ],
                    [
                        'question' => 'Comment affiner les résultats de recherche ?',
                        'answer' => 'Sélectionnez d’abord la catégorie adaptée, puis la sous-catégorie ou la prestation recherchée. Ajoutez votre localisation afin d’obtenir une liste plus pertinente.',
                    ],
                    [
                        'question' => 'Comment envoyer une demande de devis ?',
                        'answer' => 'Depuis le profil ou la prestation d’un professionnel, ouvrez la demande de devis et décrivez précisément votre besoin, le lieu, vos contraintes et les délais souhaités.',
                    ],
                    [
                        'question' => 'Sous quel délai vais-je recevoir une proposition ?',
                        'answer' => 'Le délai dépend de la disponibilité des professionnels contactés et de la précision de votre demande. Vous pouvez suivre les réponses directement depuis votre espace client.',
                    ],
                    [
                        'question' => 'Comment comparer plusieurs propositions ?',
                        'answer' => 'Consultez le détail de chaque devis : prestations prévues, montants, délais, conditions et informations du professionnel. Posez vos questions avant de prendre une décision.',
                    ],
                    [
                        'question' => 'Où retrouver mes demandes et mes conversations ?',
                        'answer' => 'Votre espace client regroupe les demandes en cours, les propositions reçues, les documents et les échanges associés. Les éléments terminés restent accessibles dans les archives.',
                    ],
                    [
                        'question' => 'Comment enregistrer un prestataire pour le retrouver plus tard ?',
                        'answer' => 'Utilisez l’action d’ajout aux favoris depuis son profil public. Vous retrouverez ensuite vos professionnels enregistrés dans la rubrique « Favoris » de votre compte.',
                    ],
                ],
            ],
            'payment' => [
                'label' => 'Paiement et sécurité',
                'icon' => 'fa-solid fa-shield-halved',
                'questions' => [
                    [
                        'question' => 'Comment sont définies les conditions de paiement d’une prestation ?',
                        'answer' => 'Les montants, échéances et moyens de paiement applicables doivent apparaître sur le devis ou la facture du prestataire. Vérifiez ces informations avant d’accepter une proposition.',
                    ],
                    [
                        'question' => 'Comment régler un abonnement prestataire ?',
                        'answer' => 'Dans l’espace prestataire, ouvrez « Abonnement et facturation », choisissez une offre et sa périodicité, puis suivez le formulaire de paiement sécurisé.',
                    ],
                    [
                        'question' => 'Où télécharger mes factures d’abonnement ?',
                        'answer' => 'Les factures payées sont disponibles dans la section « Factures d’abonnement » de la page de gestion du forfait. Un e-mail de confirmation est également envoyé pour chaque opération concernée.',
                    ],
                    [
                        'question' => 'Puis-je modifier mon forfait prestataire ?',
                        'answer' => 'Oui. La page de gestion de l’abonnement présente les offres disponibles et permet de sélectionner une nouvelle formule. Les informations affichées avant validation précisent la nouvelle périodicité et le montant.',
                    ],
                    [
                        'question' => 'Comment résilier mon abonnement prestataire ?',
                        'answer' => 'Depuis la gestion du forfait, utilisez l’action de résiliation. La date effective est indiquée et un e-mail de confirmation vous est envoyé. Le forfait reste utilisable jusqu’à cette date.',
                    ],
                    [
                        'question' => 'TrouveMoi conserve-t-il le numéro complet de ma carte bancaire ?',
                        'answer' => 'Non. Le paiement de l’abonnement est traité par Stripe. TrouveMoi affiche uniquement les informations utiles à l’identification de la carte, comme sa marque, ses quatre derniers chiffres et sa date d’expiration.',
                    ],
                    [
                        'question' => 'Que faire si un contenu ou un échange me paraît inapproprié ?',
                        'answer' => 'N’envoyez aucune information sensible supplémentaire et conservez les éléments utiles. Utilisez les dispositifs de signalement disponibles ou contactez l’équipe TrouveMoi pour demander une vérification.',
                    ],
                ],
            ],
        ];
    }
}
