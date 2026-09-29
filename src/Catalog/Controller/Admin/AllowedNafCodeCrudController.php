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

namespace App\Catalog\Controller\Admin;

use App\Catalog\Entity\AllowedNafCode;
use App\Prestataire\Entity\PrestataireProfile;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Gère les codes NAF autorisés à inscrire un compte prestataire.
 */
final class AllowedNafCodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AllowedNafCode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Code NAF')
            ->setEntityLabelInPlural('Codes NAF autorisés')
            ->setDefaultSort(['code' => 'ASC'])
            ->setSearchFields(['code', 'label'])
            ->setHelp(Crud::PAGE_INDEX, 'Seuls les codes actifs permettent une nouvelle inscription prestataire. Désactivez de préférence un code déjà utilisé plutôt que de le supprimer.');
    }

    public function configureFields(string $pageName): iterable
    {
        $code = TextField::new('code', 'Code NAF')
            ->setHelp('Format attendu : 43.22A. Le code ne peut plus être modifié après sa création.')
            ->setFormTypeOption('attr', [
                'maxlength' => 6,
                'placeholder' => '43.22A',
            ]);

        if (Crud::PAGE_EDIT === $pageName) {
            $code->setFormTypeOption('disabled', true);
        }

        yield $code;

        yield TextField::new('label', 'Libellé')
            ->setHelp('Intitulé lisible correspondant au code NAF.')
            ->setFormTypeOption('attr', [
                'maxlength' => 255,
                'placeholder' => 'Installation d’eau et de gaz',
            ]);

        yield BooleanField::new('isActive', 'Autorisé')
            ->setHelp('Un code désactivé reste enregistré mais bloque les nouvelles inscriptions correspondantes.');
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof AllowedNafCode) {
            parent::deleteEntity($entityManager, $entityInstance);

            return;
        }

        $code = $entityInstance->getCode();
        if (null !== $code && 0 < $entityManager->getRepository(PrestataireProfile::class)->count(['nafCode' => $code])) {
            $this->addFlash(
                'warning',
                \sprintf('Le code NAF %s est utilisé par au moins un prestataire. Désactivez-le plutôt que de le supprimer.', $code)
            );

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
