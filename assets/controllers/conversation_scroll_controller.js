import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        key: String,
    };

    connect() {
        const saved = window.sessionStorage.getItem(this.storageKey);

        if (saved === null) {
            return;
        }

        window.sessionStorage.removeItem(this.storageKey);

        const scrollTop = Number.parseInt(saved, 10);

        if (Number.isNaN(scrollTop)) {
            return;
        }

        requestAnimationFrame(() => {
            window.scrollTo({
                top: scrollTop,
                behavior: 'auto',
            });
        });
    }

    remember() {
        window.sessionStorage.setItem(this.storageKey, String(window.scrollY));
    }

    get storageKey() {
        if (this.hasKeyValue && this.keyValue) {
            return `conversation-scroll:${this.keyValue}`;
        }

        return `conversation-scroll:${window.location.pathname}`;
    }
}
