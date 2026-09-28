import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'mainImage',
        'author',
        'date',
        'thumbs',
        'deleteWrapper',
        'deleteForm',
        'lightbox',
        'lightboxImage',
    ];

    connect() {
        this.currentIndex = 0;
        this.items = this.thumbButtons;
        this.boundKeydownHandler = this.handleKeydown.bind(this);

        if (this.items.length === 0) {
            return;
        }

        this.show(this.currentIndex);
        document.addEventListener('keydown', this.boundKeydownHandler);
    }

    disconnect() {
        document.removeEventListener('keydown', this.boundKeydownHandler);
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

    prevFromLightbox(event) {
        this.prev(event);
    }

    nextFromLightbox(event) {
        this.next(event);
    }

    openLightbox(event) {
        event?.preventDefault();

        if (!this.hasLightboxTarget || !this.hasLightboxImageTarget) {
            return;
        }

        this.syncLightboxImage();
        this.lightboxTarget.hidden = false;
        document.body.classList.add('is-conversation-gallery-lightbox-open');
    }

    closeLightbox(event) {
        event?.preventDefault();

        if (!this.hasLightboxTarget) {
            return;
        }

        this.lightboxTarget.hidden = true;
        document.body.classList.remove('is-conversation-gallery-lightbox-open');
    }

    stopPropagation(event) {
        event.stopPropagation();
    }

    handleKeydown(event) {
        if (!this.hasLightboxTarget || this.lightboxTarget.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            this.closeLightbox();
            return;
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            this.prev();
            return;
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            this.next();
        }
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
        this.syncLightboxImage();
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

    syncLightboxImage() {
        if (!this.hasLightboxImageTarget) {
            return;
        }

        const item = this.items[this.currentIndex];

        if (!item) {
            return;
        }

        this.lightboxImageTarget.src = item.dataset.url || '';
        this.lightboxImageTarget.alt = item.dataset.alt || 'Photo de la conversation';
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