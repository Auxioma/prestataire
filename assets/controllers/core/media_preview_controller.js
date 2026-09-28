import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'placeholder', 'clearButton', 'deleteCheckbox'];
    static values = {
        hasExisting: Boolean,
        existingSrc: String,
    };

    connect() {
        this.syncState();
    }

    update() {
        const file = this.inputTarget.files?.[0];

        if (!file) {
            this.syncState();
            return;
        }

        if (!file.type.startsWith('image/')) {
            this.inputTarget.value = '';
            this.syncState();
            return;
        }

        if (this.hasDeleteCheckboxTarget) {
            this.deleteCheckboxTarget.checked = false;
        }

        const reader = new FileReader();

        reader.onload = (event) => {
            this.previewTarget.src = event.target.result;
            this.previewTarget.classList.remove('d-none');

            if (this.hasPlaceholderTarget) {
                this.placeholderTarget.classList.add('d-none');
            }

            this.showClearButton();
        };

        reader.readAsDataURL(file);
    }

    clear(event) {
        event.preventDefault();

        const hasSelectedFile = Boolean(this.inputTarget.files?.length);

        if (hasSelectedFile) {
            this.inputTarget.value = '';
        } else if (this.hasDeleteCheckboxTarget) {
            this.deleteCheckboxTarget.checked = !this.deleteCheckboxTarget.checked;
        }

        this.syncState();
    }

    syncState() {
        if (this.hasDeleteCheckboxTarget && this.deleteCheckboxTarget.checked) {
            this.reset();
            this.showClearButton();
            return;
        }

        if (this.inputTarget.files?.length) {
            this.update();
            return;
        }

        if (this.hasExistingValue && this.existingSrcValue) {
            this.restoreExisting();
            return;
        }

        this.reset();
    }

    restoreExisting() {
        this.previewTarget.src = this.existingSrcValue;
        this.previewTarget.classList.remove('d-none');

        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.add('d-none');
        }

        this.showClearButton();
    }

    reset() {
        this.previewTarget.src = '';
        this.previewTarget.classList.add('d-none');

        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.remove('d-none');
        }

        this.hideClearButton();
    }

    showClearButton() {
        if (this.hasClearButtonTarget) {
            this.clearButtonTarget.classList.remove('d-none');
        }
    }

    hideClearButton() {
        if (this.hasClearButtonTarget) {
            this.clearButtonTarget.classList.add('d-none');
        }
    }
}
