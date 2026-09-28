import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['star', 'input'];

    static values = {
        rating: { type: Number, default: 0 },
    };

    connect() {
        this.previewRating = null;
        this.render();
    }

    select(event) {
        const selectedValue = Number.parseInt(event.currentTarget.dataset.value || event.params.value || '0', 10);

        this.ratingValue = Number.isNaN(selectedValue) ? 0 : selectedValue;
        this.previewRating = null;
        this.render();
    }

    preview(event) {
        const previewValue = Number.parseInt(event.currentTarget.dataset.value || '0', 10);
        this.previewRating = Number.isNaN(previewValue) ? 0 : previewValue;
        this.render();
    }

    resetPreview() {
        this.previewRating = null;
        this.render();
    }

    render() {
        const activeRating = this.previewRating ?? this.ratingValue;

        if (this.hasInputTarget) {
            this.inputTarget.value = `${this.ratingValue}`;
        }

        this.starTargets.forEach((star) => {
            const value = Number.parseInt(star.dataset.value || '0', 10);
            const isActive = value <= activeRating;

            star.setAttribute('aria-checked', value === this.ratingValue ? 'true' : 'false');
            star.classList.toggle('text-warning', isActive);
            star.classList.toggle('text-secondary', !isActive);
            star.classList.toggle('opacity-50', !isActive);
            star.querySelector('span')?.replaceChildren(document.createTextNode(isActive ? '★' : '☆'));
        });
    }
}
