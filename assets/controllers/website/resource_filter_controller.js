import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['filter', 'item', 'status'];

    connect() {
        this.showCategory('all');
    }

    select(event) {
        this.showCategory(event.params.category);
    }

    showCategory(category) {
        let visibleCount = 0;

        this.itemTargets.forEach((item) => {
            const isVisible = category === 'all' || item.dataset.resourceCategory === category;
            item.hidden = !isVisible;
            visibleCount += isVisible ? 1 : 0;
        });

        this.filterTargets.forEach((filter) => {
            const isActive = filter.dataset.websiteResourceFilterCategoryParam === category;
            filter.classList.toggle('is-active', isActive);
            filter.setAttribute('aria-pressed', String(isActive));
        });

        if (this.hasStatusTarget) {
            const plural = visibleCount > 1 ? 's' : '';
            this.statusTarget.textContent = `${visibleCount} ressource${plural} affichée${plural}.`;
        }
    }
}
