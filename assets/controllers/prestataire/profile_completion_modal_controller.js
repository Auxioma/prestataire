import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal', 'backdrop'];

    connect() {
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    disconnect() {
        this.restoreBody();
    }

    close() {
        if (this.hasModalTarget) {
            this.modalTarget.classList.remove('show');
            this.modalTarget.style.display = 'none';
            this.modalTarget.setAttribute('aria-hidden', 'true');
        }

        if (this.hasBackdropTarget) {
            this.backdropTarget.remove();
        }

        this.restoreBody();
    }

    restoreBody() {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }
}
