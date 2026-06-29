import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'mainImage',
        'author',
        'date',
        'thumbs',
        'deleteWrapper',
        'deleteForm',
    ];

    connect() {
        this.currentIndex = 0;
        this.items = this.thumbButtons;

        if (this.items.length === 0) {
            return;
        }

        this.show(this.currentIndex);
    }

    select(event) {
        event.preventDefault();

        const button = event.currentTarget;
        const index = Number(button.dataset.index ?? 0);

        this.show(index);
    }

    prev(event) {
        event?.preventDefault();

        if (this.items.length === 0) {
            return;
        }

        const nextIndex = this.currentIndex <= 0
            ? this.items.length - 1
            : this.currentIndex - 1;

        this.show(nextIndex);
    }

    next(event) {
        event?.preventDefault();

        if (this.items.length === 0) {
            return;
        }

        const nextIndex = this.currentIndex >= this.items.length - 1
            ? 0
            : this.currentIndex + 1;

        this.show(nextIndex);
    }

    show(index) {
        const item = this.items[index];

        if (!item) {
            return;
        }

        this.currentIndex = index;

        this.mainImageTarget.src = item.dataset.url || '';
        this.mainImageTarget.alt = item.dataset.alt || 'Photo de la conversation';

        if (this.hasAuthorTarget) {
            this.authorTarget.textContent = item.dataset.author || 'Utilisateur';
        }

        if (this.hasDateTarget) {
            this.dateTarget.textContent = item.dataset.date || '—';
        }

        this.updateActiveThumb();
        this.updateDeleteState(item);
    }

    updateActiveThumb() {
        this.items.forEach((item, index) => {
            item.classList.toggle('is-active', index === this.currentIndex);
        });
    }

    updateDeleteState(item) {
        if (!this.hasDeleteWrapperTarget) {
            return;
        }

        const canDelete = item.dataset.canDelete === '1';

        if (!canDelete) {
            this.deleteWrapperTarget.hidden = true;
            return;
        }

        this.deleteWrapperTarget.hidden = false;

        if (!this.hasDeleteFormTarget) {
            return;
        }

        const deleteUrl = item.dataset.deleteUrl || '';
        const deleteToken = item.dataset.deleteToken || '';

        this.deleteFormTarget.action = deleteUrl;

        const tokenField = this.deleteFormTarget.querySelector('input[name="_token"]');
        if (tokenField) {
            tokenField.value = deleteToken;
        }
    }

    get thumbButtons() {
        if (!this.hasThumbsTarget) {
            return [];
        }

        return Array.from(
            this.thumbsTarget.querySelectorAll('.c-conversation-gallery__thumb')
        );
    }
}