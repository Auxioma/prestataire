import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submit', 'error'];

    async submit(event) {
        event.preventDefault();

        const form = this.element;
        const formData = new FormData(form);

        this.setBusy(true);
        this.clearError();

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const payload = await this.readPayload(response);

            if (!response.ok || payload?.ok === false) {
                this.showError(payload?.message || 'Le message n’a pas pu être envoyé.');
                return;
            }

            form.reset();
        } catch (_error) {
            this.showError('Une erreur est survenue pendant l’envoi du message.');
        } finally {
            this.setBusy(false);
        }
    }

    async readPayload(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            return null;
        }

        return response.json();
    }

    setBusy(isBusy) {
        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = isBusy;
        }
    }

    clearError() {
        if (!this.hasErrorTarget) {
            return;
        }

        this.errorTarget.textContent = '';
        this.errorTarget.classList.add('d-none');
    }

    showError(message) {
        if (!this.hasErrorTarget) {
            window.alert(message);
            return;
        }

        this.errorTarget.textContent = message;
        this.errorTarget.classList.remove('d-none');
    }
}
