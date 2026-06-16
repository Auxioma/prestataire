import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'step',
        'indicator',
        'progressBar',
        'prevButton',
        'nextButton',
        'submitButton',
        'stepCurrent',
        'stepTotal',
        'previewTitle',
        'previewShortDescription',
        'previewDescription',
        'previewPricing',
        'previewAdditionalInfo',
        'previewCategory',
        'previewSubcategory',
        'previewService',
        'previewZones',
        'sourceTitle',
        'sourceShortDescription',
        'sourceDescription',
        'sourcePricingType',
        'sourcePriceFrom',
        'sourcePriceTo',
        'sourcePriceUnit',
        'sourceAdditionalInfo',
        'pricingFixedBlock',
        'pricingFromBlock',
        'pricingRangeBlock',
        'pricingQuoteBlock',
    ];

    connect() {
        this.currentStep = 0;
        this.totalSteps = this.stepTargets.length;
        this.updateUI();
        this.bindLivePreview();
        this.togglePricingFields();
        this.refreshPreview();
    }

    next(event) {
        event.preventDefault();

        if (!this.validateCurrentStep()) {
            return;
        }

        if (this.currentStep < this.totalSteps - 1) {
            this.currentStep++;
            this.updateUI();
            this.refreshPreview();
            this.scrollToTop();
        }
    }

    previous(event) {
        event.preventDefault();

        if (this.currentStep > 0) {
            this.currentStep--;
            this.updateUI();
            this.refreshPreview();
            this.scrollToTop();
        }
    }

    goToStep(event) {
        event.preventDefault();
        const index = parseInt(event.currentTarget.dataset.stepIndexValue, 10);

        if (Number.isNaN(index)) {
            return;
        }

        if (index <= this.currentStep || this.validateStepsUntil(index)) {
            this.currentStep = index;
            this.updateUI();
            this.refreshPreview();
            this.scrollToTop();
        }
    }

    refreshPreview() {
        if (this.hasPreviewTitleTarget && this.hasSourceTitleTarget) {
            this.previewTitleTarget.textContent = this.valueOrFallback(
                this.sourceTitleTarget.value,
                'Titre de la prestation'
            );
        }

        if (this.hasPreviewShortDescriptionTarget && this.hasSourceShortDescriptionTarget) {
            this.previewShortDescriptionTarget.textContent = this.valueOrFallback(
                this.sourceShortDescriptionTarget.value,
                'Ajoutez une description courte pour résumer votre prestation en quelques mots.'
            );
        }

        if (this.hasPreviewDescriptionTarget && this.hasSourceDescriptionTarget) {
            this.previewDescriptionTarget.textContent = this.valueOrFallback(
                this.sourceDescriptionTarget.value,
                'Ajoutez une description détaillée pour expliquer clairement votre offre.'
            );
        }

        this.togglePricingFields();

        if (this.hasPreviewPricingTarget) {
            this.previewPricingTarget.textContent = this.buildPricingLabel();
        }

        if (this.hasPreviewAdditionalInfoTarget && this.hasSourceAdditionalInfoTarget) {
            this.previewAdditionalInfoTarget.textContent = this.valueOrFallback(
                this.sourceAdditionalInfoTarget.value,
                'Aucune information complémentaire renseignée pour le moment.'
            );
        }

        if (this.hasPreviewZonesTarget) {
            const zoneItems = Array.from(this.element.querySelectorAll('[data-zone-preview-item]'));

            if (zoneItems.length > 0) {
                this.previewZonesTarget.innerHTML = zoneItems.map((item) => {
                    const city = item.dataset.zoneCity || 'Zone d’intervention';
                    const radius = item.dataset.zoneRadius || '';
                    const postalCode = item.dataset.zonePostalCode || '';

                    const meta = [];
                    if (postalCode) meta.push(postalCode);
                    if (radius) meta.push(`Rayon ${radius} km`);

                    return `
                        <div class="tm-prestation-wizard-preview-zone-item">
                            <strong>${city}</strong>
                            <span>${meta.length ? meta.join(' · ') : 'Zone configurée sur votre profil'}</span>
                        </div>
                    `;
                }).join('');
            } else {
                this.previewZonesTarget.innerHTML = `
                    <div class="tm-prestation-wizard-preview-zone-item is-empty">
                        <strong>Aucune zone transmise</strong>
                        <span>Les zones d’intervention n’ont pas encore été injectées dans cette vue.</span>
                    </div>
                `;
            }
        }
    }

    bindLivePreview() {
        [
            'sourceTitleTarget',
            'sourceShortDescriptionTarget',
            'sourceDescriptionTarget',
            'sourcePricingTypeTarget',
            'sourcePriceFromTarget',
            'sourcePriceToTarget',
            'sourcePriceUnitTarget',
            'sourceAdditionalInfoTarget',
        ].forEach((targetName) => {
            if (this[`has${this.capitalize(targetName)}`]) {
                this[targetName].addEventListener('input', () => this.refreshPreview());
                this[targetName].addEventListener('change', () => this.refreshPreview());
            }
        });
    }

    updateUI() {
        this.stepTargets.forEach((step, index) => {
            const isActive = index === this.currentStep;
            step.classList.toggle('is-active', isActive);
            step.classList.toggle('d-none', !isActive);
        });

        this.indicatorTargets.forEach((indicator, index) => {
            indicator.classList.toggle('is-active', index === this.currentStep);
            indicator.classList.toggle('is-done', index < this.currentStep);
        });

        if (this.hasProgressBarTarget) {
            const progress = ((this.currentStep + 1) / this.totalSteps) * 100;
            this.progressBarTarget.style.width = `${progress}%`;
        }

        if (this.hasStepCurrentTarget) {
            this.stepCurrentTarget.textContent = `${this.currentStep + 1}`;
        }

        if (this.hasStepTotalTarget) {
            this.stepTotalTarget.textContent = `${this.totalSteps}`;
        }

        if (this.hasPrevButtonTarget) {
            this.prevButtonTarget.classList.toggle('d-none', this.currentStep === 0);
        }

        if (this.hasNextButtonTarget) {
            this.nextButtonTarget.classList.toggle('d-none', this.currentStep === this.totalSteps - 1);
        }

        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.classList.toggle('d-none', this.currentStep !== this.totalSteps - 1);
        }
    }

    validateStepsUntil(targetIndex) {
        const originalStep = this.currentStep;

        for (let i = originalStep; i < targetIndex; i++) {
            this.currentStep = i;
            if (!this.validateCurrentStep()) {
                this.updateUI();
                return false;
            }
        }

        this.currentStep = originalStep;
        return true;
    }

    validateCurrentStep() {
        const currentPanel = this.stepTargets[this.currentStep];
        if (!currentPanel) {
            return true;
        }

        const fields = currentPanel.querySelectorAll('input, textarea, select');
        let firstInvalidField = null;

        fields.forEach((field) => {
            if (field.offsetParent === null) {
                return;
            }

            if (typeof field.reportValidity === 'function' && !field.reportValidity() && !firstInvalidField) {
                firstInvalidField = field;
            }
        });

        if (firstInvalidField) {
            firstInvalidField.focus();
            return false;
        }

        return true;
    }

    togglePricingFields() {
    if (!this.hasSourcePricingTypeTarget) {
        return;
    }

    const pricingType = (this.sourcePricingTypeTarget.value || '').trim();

    const priceFromField = this.element.querySelector('[data-pricing-field="priceFrom"]');
    const priceToField = this.element.querySelector('[data-pricing-field="priceTo"]');
    const priceUnitField = this.element.querySelector('[data-pricing-field="priceUnit"]');
    const quoteNote = this.hasPricingQuoteBlockTarget
        ? this.pricingQuoteBlockTarget.querySelector('.tm-prestation-wizard-note')
        : null;

    if (priceFromField) priceFromField.classList.add('d-none');
    if (priceToField) priceToField.classList.add('d-none');
    if (priceUnitField) priceUnitField.classList.add('d-none');
    if (quoteNote) quoteNote.classList.add('d-none');

    if (pricingType === 'quote') {
        if (quoteNote) quoteNote.classList.remove('d-none');
        return;
    }

    if (pricingType === 'from') {
        if (priceFromField) priceFromField.classList.remove('d-none');
        if (priceUnitField) priceUnitField.classList.remove('d-none');
        return;
    }

    if (pricingType === 'range') {
        if (priceFromField) priceFromField.classList.remove('d-none');
        if (priceToField) priceToField.classList.remove('d-none');
        if (priceUnitField) priceUnitField.classList.remove('d-none');
        return;
    }

    if (['fixed', 'hourly', 'daily'].includes(pricingType)) {
        if (priceToField) priceToField.classList.remove('d-none');
        if (priceUnitField) priceUnitField.classList.remove('d-none');
        return;
    }

    if (priceToField) priceToField.classList.remove('d-none');
    if (priceUnitField) priceUnitField.classList.remove('d-none');
}

    buildPricingLabel() {
        const pricingType = this.hasSourcePricingTypeTarget ? this.sourcePricingTypeTarget.value.trim() : '';
        const priceFrom = this.hasSourcePriceFromTarget ? this.sourcePriceFromTarget.value.trim() : '';
        const priceTo = this.hasSourcePriceToTarget ? this.sourcePriceToTarget.value.trim() : '';
        const priceUnit = this.hasSourcePriceUnitTarget ? this.sourcePriceUnitTarget.value.trim() : '';
        const unitLabel = priceUnit ? ` / ${priceUnit}` : '';

        if (pricingType === 'quote') {
            return 'Tarif sur devis';
        }

        if (pricingType === 'range') {
            if (priceFrom && priceTo) {
                return `De ${priceFrom} € à ${priceTo} €${unitLabel}`;
            }
            if (priceFrom) {
                return `À partir de ${priceFrom} €${unitLabel}`;
            }
            if (priceTo) {
                return `Jusqu’à ${priceTo} €${unitLabel}`;
            }
            return 'Fourchette non renseignée';
        }

        if (pricingType === 'from') {
            if (priceFrom) {
                return `À partir de ${priceFrom} €${unitLabel}`;
            }
            return 'Tarif à partir de non renseigné';
        }

        if (['fixed', 'hourly', 'daily'].includes(pricingType)) {
            if (priceTo) {
                return `${priceTo} €${unitLabel}`;
            }
            return 'Tarif non renseigné';
        }

        if (priceFrom && priceTo) {
            return `De ${priceFrom} € à ${priceTo} €${unitLabel}`;
        }

        if (priceFrom) {
            return `À partir de ${priceFrom} €${unitLabel}`;
        }

        if (priceTo) {
            return `${priceTo} €${unitLabel}`;
        }

        return 'Tarification non renseignée';
    }

    valueOrFallback(value, fallback) {
        return value && value.trim() !== '' ? value.trim() : fallback;
    }

    scrollToTop() {
        this.element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    capitalize(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }
}