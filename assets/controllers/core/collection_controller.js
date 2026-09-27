import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['items'];
    static values = {
        index: Number,
        prototype: String,
    };

    connect() {
        if (!this.hasIndexValue) {
            this.indexValue = this.itemsTarget.children.length;
        }
    }

    add(event) {
        event.preventDefault();

        if (!this.hasPrototypeValue) {
            return;
        }

        const html = this.prototypeValue.replace(/__name__/g, this.indexValue);

        this.itemsTarget.insertAdjacentHTML('beforeend', html);
        this.indexValue++;

        this.refreshTitles();
    }

    remove(event) {
        event.preventDefault();

        const item = event.currentTarget.closest('[data-collection-item]');
        if (item) {
            item.remove();
            this.refreshTitles();
        }
    }

    refreshTitles() {
        const items = this.itemsTarget.querySelectorAll('[data-collection-item]');

        items.forEach((item, index) => {
            const title = item.querySelector('.tm-quote-item-card__title');
            if (title) {
                title.textContent = `Ligne ${index + 1}`;
            }
        });
    }
}