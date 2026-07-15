<?php

namespace App\Controller\Admin;

use App\Entity\Report;
use App\Enum\ReportReasonEnum;
use App\Enum\ReportStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ReportCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Report::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Signalement')
            ->setEntityLabelInPlural('Signalements')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('contextLabel', 'Contexte')->onlyOnIndex();
        yield TextField::new('contextLabel', 'Contexte')->onlyOnDetail();
        yield TextareaField::new('contextSummary', 'Résumé du contexte')->onlyOnDetail();
        yield TextField::new('contextLinks', 'Accès rapide')
            ->onlyOnDetail()
            ->formatValue(function ($value, Report $report): string {
                $links = [];

                if ($report->getQuoteRequest()) {
                    $links[] = sprintf(
                        '<a href="%s">Voir la demande</a>',
                        $this->adminUrlGenerator->unsetAll()
                            ->setController(QuoteRequestCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($report->getQuoteRequest()->getId())
                            ->generateUrl()
                    );
                }

                if ($report->getConversation()) {
                    $links[] = sprintf(
                        '<a href="%s">Voir la conversation</a>',
                        $this->adminUrlGenerator->unsetAll()
                            ->setController(ConversationCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($report->getConversation()->getId())
                            ->generateUrl()
                    );
                }

                if ($report->getReview()) {
                    $links[] = sprintf(
                        '<a href="%s">Voir l’avis</a>',
                        $this->adminUrlGenerator->unsetAll()
                            ->setController(ReviewCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($report->getReview()->getId())
                            ->generateUrl()
                    );
                }

                return [] !== $links ? implode(' | ', $links) : '<span class="text-muted">Aucun lien disponible</span>';
            })
            ->renderAsHtml();
        yield AssociationField::new('reporter', 'Auteur')->autocomplete()->hideOnForm();
        yield AssociationField::new('reportedUser', 'Utilisateur signalé')->autocomplete()->hideOnForm();
        yield TextField::new('reasonLabel', 'Motif')
            ->formatValue(fn ($value, Report $report): string => $this->renderReasonBadge($report->getReason()))
            ->renderAsHtml()
            ->hideOnForm();
        yield TextareaField::new('message', 'Contenu du signalement')
            ->formatValue(static fn ($value): string => null !== $value && '' !== trim((string) $value) ? (string) $value : 'Aucun message complémentaire fourni.')
            ->onlyOnDetail();
        yield TextField::new('statusLabel', 'Statut')
            ->formatValue(fn ($value, Report $report): string => $this->renderStatusBadge($report->getStatus()))
            ->renderAsHtml()
            ->hideOnForm();
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                ReportStatusEnum::NEW->getLabel() => ReportStatusEnum::NEW,
                ReportStatusEnum::IN_REVIEW->getLabel() => ReportStatusEnum::IN_REVIEW,
                ReportStatusEnum::RESOLVED->getLabel() => ReportStatusEnum::RESOLVED,
                ReportStatusEnum::DISMISSED->getLabel() => ReportStatusEnum::DISMISSED,
            ])
            ->onlyOnForms();
        yield TextareaField::new('adminNote', 'Note admin')->hideOnIndex();
        yield DateTimeField::new('resolvedAt', 'Traité le')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    private function renderReasonBadge(mixed $value): string
    {
        $reason = $value instanceof ReportReasonEnum ? $value : ReportReasonEnum::tryFrom((string) $value);

        if (!$reason instanceof ReportReasonEnum) {
            return $this->renderBadge((string) $value, '#6c757d', 'rgba(108, 117, 125, 0.12)', '#495057');
        }

        [$borderColor, $backgroundColor, $textColor] = match ($reason) {
            ReportReasonEnum::INAPPROPRIATE_BEHAVIOR => ['#f0ad00', 'rgba(240, 173, 0, 0.12)', '#7a5a00'],
            ReportReasonEnum::HARASSMENT, ReportReasonEnum::SCAM_OR_FRAUD => ['#dc3545', 'rgba(220, 53, 69, 0.10)', '#a61e2d'],
            ReportReasonEnum::FAKE_OR_MISLEADING_CONTENT => ['#0dcaf0', 'rgba(13, 202, 240, 0.10)', '#0a6f86'],
            ReportReasonEnum::SPAM => ['#6c757d', 'rgba(108, 117, 125, 0.12)', '#495057'],
            ReportReasonEnum::OTHER => ['#212529', 'rgba(33, 37, 41, 0.08)', '#212529'],
        };

        return $this->renderBadge($reason->getLabel(), $borderColor, $backgroundColor, $textColor);
    }

    private function renderStatusBadge(mixed $value): string
    {
        $status = $value instanceof ReportStatusEnum ? $value : ReportStatusEnum::tryFrom((string) $value);

        if (!$status instanceof ReportStatusEnum) {
            return $this->renderBadge((string) $value, '#6c757d', 'rgba(108, 117, 125, 0.12)', '#495057');
        }

        [$borderColor, $backgroundColor, $textColor] = match ($status) {
            ReportStatusEnum::NEW => ['#f0ad00', 'rgba(240, 173, 0, 0.12)', '#7a5a00'],
            ReportStatusEnum::IN_REVIEW => ['#0d6efd', 'rgba(13, 110, 253, 0.10)', '#0a58ca'],
            ReportStatusEnum::RESOLVED => ['#198754', 'rgba(25, 135, 84, 0.12)', '#146c43'],
            ReportStatusEnum::DISMISSED => ['#6c757d', 'rgba(108, 117, 125, 0.12)', '#495057'],
        };

        return $this->renderBadge($status->getLabel(), $borderColor, $backgroundColor, $textColor);
    }

    private function renderBadge(string $label, string $borderColor, string $backgroundColor, string $textColor): string
    {
        return sprintf(
            '<span class="badge rounded-pill" style="border:1px solid %s;background:%s;color:%s;font-weight:600;">%s</span>',
            htmlspecialchars($borderColor, ENT_QUOTES),
            htmlspecialchars($backgroundColor, ENT_QUOTES),
            htmlspecialchars($textColor, ENT_QUOTES),
            htmlspecialchars($label, ENT_QUOTES)
        );
    }
}
