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
        this.portalEl = null;

        this.boundClose = this.handleOutsideClick.bind(this);
        this.boundReposition = this.positionPortal.bind(this);

        document.addEventListener('click', this.boundClose);
        window.addEventListener('resize', this.boundReposition);
        window.addEventListener('scroll', this.boundReposition, true);
    }

    disconnect() {
        if (this.abortController) {
            this.abortController.abort();
        }

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        document.removeEventListener('click', this.boundClose);
        window.removeEventListener('resize', this.boundReposition);
        window.removeEventListener('scroll', this.boundReposition, true);

        this.removePortal();
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
        if (this.items.length === 0 || this.isHidden()) {
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
        if (this.items.length === 0) {
            this.hideSuggestions();
            return;
        }

        const container = this.ensurePortal();

        container.innerHTML = this.items.map((item, index) => {
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
                    <span class="tm-homepage-search-suggestion__icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </span>
                    <span class="tm-homepage-search-suggestion__content">
                        <span class="tm-homepage-search-suggestion__title">${title}</span>
                        ${subtitle ? `<span class="tm-homepage-search-suggestion__meta">${subtitle}</span>` : ''}
                    </span>
                </button>
            `;
        }).join('');

        this.suggestionsTarget.hidden = false;
        container.hidden = false;
        this.positionPortal();

        container.querySelectorAll('[data-index]').forEach((button) => {
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
        const clickedInPortal = this.portalEl && this.portalEl.contains(event.target);
        if (!this.element.contains(event.target) && !clickedInPortal) {
            this.hideSuggestions();
        }
    }

    hideSuggestions() {
        if (this.hasSuggestionsTarget) {
            this.suggestionsTarget.hidden = true;
            this.suggestionsTarget.innerHTML = '';
        }

        if (this.portalEl) {
            this.portalEl.hidden = true;
            this.portalEl.innerHTML = '';
        }
    }

    isHidden() {
        return !this.portalEl || this.portalEl.hidden || this.portalEl.childElementCount === 0;
    }

    ensurePortal() {
        if (this.portalEl) {
            return this.portalEl;
        }

        this.portalEl = document.createElement('div');
        this.portalEl.className = 'tm-homepage-search-suggestions-portal';
        this.portalEl.hidden = true;
        document.body.appendChild(this.portalEl);

        return this.portalEl;
    }

    positionPortal() {
        if (!this.portalEl || this.portalEl.hidden || !this.hasQueryTarget) {
            return;
        }

        const rect = this.queryTarget.getBoundingClientRect();

        this.portalEl.style.position = 'fixed';
        this.portalEl.style.top = `${rect.bottom + 12}px`;
        this.portalEl.style.left = `${rect.left}px`;
        this.portalEl.style.width = `${Math.max(rect.width, 360)}px`;
        this.portalEl.style.zIndex = '999999';
    }

    removePortal() {
        if (this.portalEl && this.portalEl.parentNode) {
            this.portalEl.parentNode.removeChild(this.portalEl);
        }
        this.portalEl = null;
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