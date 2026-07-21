import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
    };

    open(event) {
        if (this.shouldIgnore(event.target) || !this.hasUrlValue || !this.urlValue) {
            return;
        }

        window.location.href = this.urlValue;
    }

    keydown(event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        if (this.shouldIgnore(event.target) || !this.hasUrlValue || !this.urlValue) {
            return;
        }

        event.preventDefault();
        window.location.href = this.urlValue;
    }

    shouldIgnore(target) {
        return !!target.closest('a, button, input, select, textarea, label, [role="button"], .favorite-btn');
    }
}
