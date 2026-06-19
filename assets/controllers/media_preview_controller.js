import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'placeholder'];

    update() {
        const file = this.inputTarget.files?.[0];

        if (!file) {
            this.reset();
            return;
        }

        if (!file.type.startsWith('image/')) {
            this.reset();
            return;
        }

        const reader = new FileReader();

        reader.onload = (event) => {
            this.previewTarget.src = event.target.result;
            this.previewTarget.classList.remove('d-none');

            if (this.hasPlaceholderTarget) {
                this.placeholderTarget.classList.add('d-none');
            }
        };

        reader.readAsDataURL(file);
    }

    reset() {
        this.previewTarget.src = '';
        this.previewTarget.classList.add('d-none');

        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.remove('d-none');
        }
    }
}