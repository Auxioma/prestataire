import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['range', 'value', 'query', 'suggestions'];
    static values = {
        defaultRadius: Number,
        autocompleteUrl: String,
    };

    connect() {
        this.update();
        this.abortController = null;
        this.debounceTimer = null;
        this.activeIndex = -1;
        this.items = [];
        this.boundClose = this.handleOutsideClick.bind(this);
        document.addEventListener('click', this.boundClose);
    }

    disconnect() {
        if (this.abortController) {
            this.abortController.abort();
        }

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        document.removeEventListener('click', this.boundClose);
    }

    update() {
        if (!this.hasValueTarget) {
            return;
        }

        const value = this.hasRangeTarget && this.rangeTarget.value
            ? this.rangeTarget.value
            : this.defaultRadiusValue || 25;

        this.valueTarget.textContent = `${value} km`;
    }

    onQueryInput() {
        if (!this.hasQueryTarget || !this.hasSuggestionsTarget) {
            return;
        }

        const query = this.queryTarget.value.trim();

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        if (query.length < 2) {
            this.items = [];
            this.activeIndex = -1;
            this.hideSuggestions();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(query);
        }, 180);
    }

    onQueryKeydown(event) {
        if (!this.hasSuggestionsTarget || this.items.length === 0 || this.isHidden()) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.activeIndex = Math.min(this.activeIndex + 1, this.items.length - 1);
            this.renderSuggestions();
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.activeIndex = Math.max(this.activeIndex - 1, 0);
            this.renderSuggestions();
            return;
        }

        if (event.key === 'Enter' && this.activeIndex >= 0) {
            event.preventDefault();
            this.applySuggestion(this.items[this.activeIndex]);
            return;
        }

        if (event.key === 'Escape') {
            this.hideSuggestions();
        }
    }

    async fetchSuggestions(query) {
        if (!this.autocompleteUrlValue) {
            return;
        }

        if (this.abortController) {
            this.abortController.abort();
        }

        this.abortController = new AbortController();

        try {
            const url = new URL(this.autocompleteUrlValue, window.location.origin);
            url.searchParams.set('q', query);

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: this.abortController.signal,
            });

            if (!response.ok) {
                this.items = [];
                this.activeIndex = -1;
                this.hideSuggestions();
                return;
            }

            const payload = await response.json();
            this.items = Array.isArray(payload.items) ? payload.items : [];
            this.activeIndex = -1;

            if (this.items.length === 0) {
                this.hideSuggestions();
                return;
            }

            this.renderSuggestions();
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            this.items = [];
            this.activeIndex = -1;
            this.hideSuggestions();
        }
    }

    renderSuggestions() {
        if (!this.hasSuggestionsTarget) {
            return;
        }

        if (this.items.length === 0) {
            this.hideSuggestions();
            return;
        }

        this.suggestionsTarget.innerHTML = this.items.map((item, index) => {
            const title = this.escapeHtml(item.companyName || item.metier || 'Suggestion');
            const subtitleParts = [
                item.metier || '',
                item.city || '',
                item.categoryLabel || '',
                item.serviceLabel || '',
            ].filter(Boolean);

            const subtitle = this.escapeHtml(subtitleParts.join(' · '));

            return `
                <button
                    type="button"
                    class="tm-homepage-search-suggestion${index === this.activeIndex ? ' is-active' : ''}"
                    data-index="${index}"
                >
                    <span class="tm-homepage-search-suggestion__title">${title}</span>
                    ${subtitle ? `<span class="tm-homepage-search-suggestion__meta">${subtitle}</span>` : ''}
                </button>
            `;
        }).join('');

        this.suggestionsTarget.hidden = false;

        this.suggestionsTarget.querySelectorAll('[data-index]').forEach((button) => {
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });

            button.addEventListener('click', () => {
                const index = Number(button.dataset.index);
                if (!Number.isNaN(index) && this.items[index]) {
                    this.applySuggestion(this.items[index]);
                }
            });
        });
    }

    applySuggestion(item) {
        if (!this.hasQueryTarget) {
            return;
        }

        this.queryTarget.value = item.companyName || item.metier || this.queryTarget.value;
        this.hideSuggestions();
    }

    handleOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.hideSuggestions();
        }
    }

    hideSuggestions() {
        if (!this.hasSuggestionsTarget) {
            return;
        }

        this.suggestionsTarget.hidden = true;
        this.suggestionsTarget.innerHTML = '';
    }

    isHidden() {
        return this.suggestionsTarget.hidden || this.suggestionsTarget.childElementCount === 0;
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}