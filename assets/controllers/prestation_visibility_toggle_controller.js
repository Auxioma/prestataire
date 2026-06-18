import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'status', 'label'];
    static values = {
        url: String,
        token: String,
        isComplete: Boolean,
    };

    connect() {
        this.isLoading = false;
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

            if (this.hasStatusTarget) {
                this.statusTarget.classList.remove(
                    'tm-prestations-badge--success',
                    'tm-prestations-badge--draft',
                    'tm-prestations-badge--muted'
                );

                if (!data.isActive) {
                    this.statusTarget.textContent = 'Masquée';
                    this.statusTarget.classList.add('tm-prestations-badge--muted');
                } else if (this.isCompleteValue) {
                    this.statusTarget.textContent = 'Visible';
                    this.statusTarget.classList.add('tm-prestations-badge--success');
                } else {
                    this.statusTarget.textContent = 'Active mais incomplète';
                    this.statusTarget.classList.add('tm-prestations-badge--draft');
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