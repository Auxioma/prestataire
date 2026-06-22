import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icon'];

    static values = {
        url: String,
        type: String,
        targetId: String,
        csrfToken: String,
        isFavorite: Boolean,
        loginUrl: String,
    };

    connect() {
        this.isLoading = false;
        this.render();
    }

    async toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        this.element.disabled = true;

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({
                    type: this.typeValue,
                    targetId: this.targetIdValue,
                    _token: this.csrfTokenValue,
                }),
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (response.status === 401) {
                if (this.hasLoginUrlValue && this.loginUrlValue) {
                    window.location.href = this.loginUrlValue;
                    return;
                }

                throw new Error('Vous devez être connecté pour gérer vos favoris.');
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Impossible de mettre à jour les favoris.');
            }

            this.isFavoriteValue = !!data.isFavorite;
            this.render();
        } catch (error) {
            window.alert(error.message || 'Impossible de mettre à jour les favoris.');
        } finally {
            this.element.disabled = false;
            this.isLoading = false;
        }
    }

    render() {
        this.element.setAttribute('aria-pressed', this.isFavoriteValue ? 'true' : 'false');
        this.element.setAttribute(
            'aria-label',
            this.isFavoriteValue ? 'Retirer des favoris' : 'Ajouter aux favoris',
        );

        if (!this.hasIconTarget) {
            return;
        }

        this.iconTarget.classList.remove('fa-regular', 'fa-solid', 'text-muted', 'text-danger');
        this.iconTarget.classList.add('fa-heart');

        if (this.isFavoriteValue) {
            this.iconTarget.classList.add('fa-solid', 'text-danger');
        } else {
            this.iconTarget.classList.add('fa-regular', 'text-muted');
        }
    }
}