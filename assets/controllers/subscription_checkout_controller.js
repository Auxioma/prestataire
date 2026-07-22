import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['paymentElement', 'selectionName', 'selectionMeta', 'submitButton', 'submitHelp', 'errorBox', 'successBox'];

    static values = {
        stripePublicKey: String,
        setupIntentUrl: String,
        finalizeUrl: String,
        setupIntentCsrfToken: String,
        returnUrl: String,
        enabled: Boolean,
    };

    connect() {
        this.stripe = null;
        this.elements = null;
        this.cardElement = null;
        this.selectedPlan = null;
        this.setupIntentClientSecret = null;
        this.confirmedSetupIntentId = null;
        this.isSubmitting = false;

        if (!this.enabledValue) {
            return;
        }

        this.initializeStripeForm();
    }

    async initializeStripeForm() {
        if (!this.hasPaymentElementTarget || !this.hasSubmitButtonTarget || !this.hasSubmitHelpTarget || !this.hasErrorBoxTarget || !this.hasSuccessBoxTarget) {
            return;
        }

        this.setSubmitState(true, 'Initialisation du formulaire Stripe...');
        this.hideAlerts();

        try {
            if (typeof window.Stripe !== 'function') {
                throw new Error('Stripe.js n’est pas disponible.');
            }

            const response = await fetch(this.setupIntentUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    _token: this.setupIntentCsrfTokenValue,
                }),
            });

            const payload = await response.json();
            if (!response.ok || !payload.success || !payload.clientSecret) {
                throw new Error(payload.message || 'Impossible d’initialiser Stripe.');
            }

            this.setupIntentClientSecret = payload.clientSecret;
            this.confirmedSetupIntentId = null;
            this.stripe = window.Stripe(this.stripePublicKeyValue);
            this.elements = this.stripe.elements();

            this.cardElement?.destroy?.();
            this.cardElement = this.elements.create('card', {
                hidePostalCode: true,
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#212529',
                        fontFamily: 'Plus Jakarta Sans, Arial, sans-serif',
                        '::placeholder': {
                            color: '#6c757d',
                        },
                    },
                    invalid: {
                        color: '#dc3545',
                    },
                },
            });

            this.cardElement.mount(this.paymentElementTarget);
            this.cardElement.on('change', ({ error }) => {
                if (error?.message) {
                    this.showError(error.message);
                    return;
                }

                this.hideError();
            });
        } catch (error) {
            this.showError(error.message || 'Impossible d’afficher le formulaire de paiement.');
        } finally {
            this.refreshSubmitState();
        }
    }

    selectPlan(event) {
        if (!this.hasSelectionNameTarget || !this.hasSelectionMetaTarget) {
            return;
        }

        const params = event.params || {};

        this.selectedPlan = {
            code: params.planCode,
            period: params.planPeriod,
            name: params.planName,
            price: params.planPrice,
            label: params.planLabel,
            submitUrl: params.planSubmitUrl,
            csrfToken: params.planCsrfToken,
        };

        this.selectionNameTarget.textContent = `${this.selectedPlan.name} - ${this.selectedPlan.label}`;
        this.selectionMetaTarget.textContent = `${this.selectedPlan.price} EUR - paiement recurrent ${this.selectedPlan.period === 'annual' ? 'annuel' : 'mensuel'}.`;
        this.hideAlerts();
        this.refreshSubmitState();
    }

    async submit(event) {
        event.preventDefault();

        if (!this.selectedPlan || !this.stripe || !this.cardElement || this.isSubmitting) {
            return;
        }

        this.isSubmitting = true;
        this.hideAlerts();
        this.setSubmitState(true, 'Validation de la carte et creation de l’abonnement...');

        try {
            const setupIntentId = await this.confirmSetupIntent();
            const checkoutResponse = await fetch(this.selectedPlan.submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    _token: this.selectedPlan.csrfToken,
                    setupIntentId,
                }),
            });

            const checkoutPayload = await checkoutResponse.json();
            if (!checkoutResponse.ok || !checkoutPayload.success) {
                throw new Error(checkoutPayload.message || 'Stripe a refuse la creation de l’abonnement.');
            }

            if (checkoutPayload.requiresAction && checkoutPayload.paymentIntentClientSecret) {
                const paymentResult = await this.stripe.confirmCardPayment(checkoutPayload.paymentIntentClientSecret);

                if (paymentResult.error) {
                    throw new Error(paymentResult.error.message || 'L’authentification bancaire a echoue.');
                }
            }

            await this.finalizeSubscriptionSync(checkoutPayload.stripeSubscriptionId || null);

            this.showSuccess(checkoutPayload.message || 'L’abonnement a ete cree.');
            window.location.assign(checkoutPayload.redirectUrl || this.returnUrlValue);
        } catch (error) {
            this.showError(error.message || 'Impossible de finaliser l’abonnement.');
        } finally {
            this.isSubmitting = false;
            this.refreshSubmitState();
        }
    }

    async confirmSetupIntent() {
        if (this.confirmedSetupIntentId) {
            return this.confirmedSetupIntentId;
        }

        const result = await this.stripe.confirmCardSetup(this.setupIntentClientSecret, {
            payment_method: {
                card: this.cardElement,
            },
        });

        if (result.error) {
            throw new Error(result.error.message || 'La carte n’a pas pu etre enregistree.');
        }

        if (!result.setupIntent || result.setupIntent.status !== 'succeeded') {
            throw new Error('Le moyen de paiement n’a pas ete confirme par Stripe.');
        }

        this.confirmedSetupIntentId = result.setupIntent.id;

        return this.confirmedSetupIntentId;
    }

    async finalizeSubscriptionSync(stripeSubscriptionId = null) {
        const response = await fetch(this.finalizeUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                stripeSubscriptionId,
            }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'La synchronisation finale avec Stripe a echoue.');
        }
    }

    refreshSubmitState() {
        if (!this.enabledValue) {
            return;
        }

        if (this.isSubmitting) {
            return;
        }

        if (!this.selectedPlan) {
            this.setSubmitState(true, 'Choisissez une formule pour continuer.');
            return;
        }

        if (!this.stripe || !this.cardElement || !this.setupIntentClientSecret) {
            this.setSubmitState(true, 'Le formulaire Stripe est en cours d’initialisation.');
            return;
        }

        this.setSubmitState(false, `Confirmer ${this.selectedPlan.name} - ${this.selectedPlan.label}.`);
    }

    setSubmitState(disabled, helpText) {
        if (!this.hasSubmitButtonTarget || !this.hasSubmitHelpTarget) {
            return;
        }

        this.submitButtonTarget.disabled = disabled;
        this.submitHelpTarget.textContent = helpText;
    }

    hideAlerts() {
        if (!this.hasErrorBoxTarget || !this.hasSuccessBoxTarget) {
            return;
        }

        this.hideError();
        this.successBoxTarget.classList.add('d-none');
        this.successBoxTarget.textContent = '';
    }

    showError(message) {
        if (!this.hasErrorBoxTarget) {
            return;
        }

        this.errorBoxTarget.textContent = message;
        this.errorBoxTarget.classList.remove('d-none');
    }

    hideError() {
        if (!this.hasErrorBoxTarget) {
            return;
        }

        this.errorBoxTarget.classList.add('d-none');
        this.errorBoxTarget.textContent = '';
    }

    showSuccess(message) {
        if (!this.hasSuccessBoxTarget) {
            return;
        }

        this.successBoxTarget.textContent = message;
        this.successBoxTarget.classList.remove('d-none');
    }
}
