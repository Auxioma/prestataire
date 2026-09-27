import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'label'];
    static values = {
        url: String,
        token: String,
        isComplete: Boolean,
        statusSelector: String,
    };

    connect() {
        this.isLoading = false;
    }

    get statusElement() {
        if (!this.hasStatusSelectorValue) {
            return null;
        }

        return document.querySelector(this.statusSelectorValue);
    }

    async toggle() {
        if (this.isLoading) {
            return;
        }

        const previousChecked = !this.checkboxTarget.checked;

        this.isLoading = true;
        this.checkboxTarget.disabled = true;
        this.element.classList.add('is-loading');

        try {
            const formData = new FormData();
            formData.append('_token', this.tokenValue);

            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Impossible de modifier le statut.');
            }

            this.checkboxTarget.checked = !!data.isActive;

            if (this.hasLabelTarget) {
                this.labelTarget.textContent = data.isActive ? 'Active' : 'Inactive';
            }

            const statusElement = this.statusElement;

            if (statusElement) {
                statusElement.classList.remove(
                    'tm-prestations-badge--success',
                    'tm-prestations-badge--draft',
                    'tm-prestations-badge--muted'
                );

                if (!data.isActive) {
                    statusElement.textContent = 'Masquée';
                    statusElement.classList.add('tm-prestations-badge--muted');
                } else if (this.isCompleteValue) {
                    statusElement.textContent = 'Visible';
                    statusElement.classList.add('tm-prestations-badge--success');
                } else {
                    statusElement.textContent = 'Active mais incomplète';
                    statusElement.classList.add('tm-prestations-badge--draft');
                }
            }
        } catch (error) {
            this.checkboxTarget.checked = previousChecked;
            window.alert(error.message || 'Impossible de modifier le statut.');
        } finally {
            this.checkboxTarget.disabled = false;
            this.element.classList.remove('is-loading');
            this.isLoading = false;
        }
    }
}