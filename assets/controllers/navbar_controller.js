import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results', 'desktopResults', 'mobileResults'];
    static values = {
        autocompleteUrl: String,
    };

    connect() {
        this.dropdownElements = this.element.querySelectorAll('.dropdown');
        this.dropdownListeners = [];

        this.dropdownElements.forEach((dropdown) => {
            const menu = dropdown.querySelector('.dropdown-menu');
            const showHandler = () => {
                if (menu) {
                    menu.classList.add('animate-fade-in');
                }
            };
            const hideHandler = () => {
                if (menu) {
                    menu.classList.remove('animate-fade-in');
                }
            };

            dropdown.addEventListener('show.bs.dropdown', showHandler);
            dropdown.addEventListener('hide.bs.dropdown', hideHandler);
            this.dropdownListeners.push({ dropdown, showHandler, hideHandler });
        });

        this.abortController = null;
        this.debounceTimer = null;

        this.handleDocumentClick = this.handleDocumentClick.bind(this);
        document.addEventListener('pointerdown', this.handleDocumentClick);
    }

    disconnect() {
        document.removeEventListener('pointerdown', this.handleDocumentClick);
        this.dropdownListeners.forEach(({ dropdown, showHandler, hideHandler }) => {
            dropdown.removeEventListener('show.bs.dropdown', showHandler);
            dropdown.removeEventListener('hide.bs.dropdown', hideHandler);
        });
        this.dropdownListeners = [];

        if (this.abortController) {
            this.abortController.abort();
        }

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
    }

    onInput(event) {
        const value = event.currentTarget.value.trim();

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        if (value.length < 2) {
            this.clearResults();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.fetchResults(value);
        }, 180);
    }

    async fetchResults(query) {
        if (!this.hasAutocompleteUrlValue) {
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
                },
                signal: this.abortController.signal,
            });

            if (!response.ok) {
                throw new Error('Erreur autocomplete');
            }

            const data = await response.json();
            this.renderResults(Array.isArray(data.items) ? data.items : []);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            this.clearResults();
        }
    }

    renderResults(items) {
        const html = items.length
            ? `
                <div class="tm-navbarsearch-dropdown-list">
                    ${items.map((item) => `
                        <a class="tm-navbarsearch-suggestion" href="${item.url ?? '#'}">
                            <div class="tm-navbarsearch-suggestion-main">
                                <strong>${this.escapeHtml(item.companyName ?? '')}</strong>
                                ${item.metier ? `<span>${this.escapeHtml(item.metier)}</span>` : ''}
                            </div>
                            <div class="tm-navbarsearch-suggestion-meta">
                                ${item.serviceLabel ? `<span>${this.escapeHtml(item.serviceLabel)}</span>` : ''}
                                ${item.city ? `<span>${this.escapeHtml(item.city)}</span>` : ''}
                            </div>
                        </a>
                    `).join('')}
                </div>
            `
            : `
                <div class="tm-navbarsearch-empty">
                    Aucun résultat trouvé.
                </div>
            `;

        this.resultsTargets.forEach((target) => {
            target.innerHTML = html;
            target.classList.remove('d-none');
        });
    }

    clearResults() {
        if (!this.hasResultsTarget) {
            return;
        }

        this.resultsTargets.forEach((target) => {
            target.innerHTML = '';
            target.classList.add('d-none');
        });
    }

    handleDocumentClick(event) {
        if (!this.element.contains(event.target)) {
            this.clearResults();
        }
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}
