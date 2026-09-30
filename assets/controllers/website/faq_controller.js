import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['empty', 'filter', 'item', 'loadMore', 'moreLabel', 'search', 'status'];

    static values = {
        initialCount: { type: Number, default: 5 },
        increment: { type: Number, default: 5 },
    };

    connect() {
        this.selectedCategory = 'all';
        this.visibleLimit = this.initialCountValue;
        this.render();
    }

    selectCategory(event) {
        this.selectedCategory = event.params.category;
        this.visibleLimit = this.initialCountValue;
        this.render();
    }

    search() {
        this.visibleLimit = this.initialCountValue;
        this.render();
    }

    showMore() {
        this.visibleLimit += this.incrementValue;
        this.render();
    }

    render() {
        const query = this.normalize(this.hasSearchTarget ? this.searchTarget.value : '');
        const matchingItems = this.itemTargets.filter((item) => {
            const matchesCategory = 'all' === this.selectedCategory
                || item.dataset.faqCategory === this.selectedCategory;
            const matchesQuery = '' === query
                || this.normalize(item.dataset.faqSearch || '').includes(query);

            return matchesCategory && matchesQuery;
        });
        const displayedItems = '' === query
            ? matchingItems.slice(0, this.visibleLimit)
            : matchingItems;
        const displayedSet = new Set(displayedItems);

        this.itemTargets.forEach((item) => {
            item.hidden = !displayedSet.has(item);
        });

        this.filterTargets.forEach((filter) => {
            const isActive = filter.dataset.websiteFaqCategoryParam === this.selectedCategory;
            filter.classList.toggle('is-active', isActive);
            filter.setAttribute('aria-pressed', String(isActive));
        });

        const remaining = Math.max(0, matchingItems.length - displayedItems.length);
        if (this.hasLoadMoreTarget) {
            this.loadMoreTarget.hidden = '' !== query || 0 === remaining;
        }
        if (this.hasMoreLabelTarget) {
            this.moreLabelTarget.textContent = 0 < remaining
                ? `Voir plus de questions (${Math.min(remaining, this.incrementValue)})`
                : 'Toutes les questions sont affichées';
        }
        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = 0 !== matchingItems.length;
        }
        if (this.hasStatusTarget) {
            const plural = displayedItems.length > 1 ? 's' : '';
            this.statusTarget.textContent = `${displayedItems.length} question${plural} affichée${plural} sur ${matchingItems.length}.`;
        }
    }

    normalize(value) {
        return value
            .toLocaleLowerCase('fr')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }
}
