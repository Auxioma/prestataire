import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submit', 'error', 'files', 'preview', 'summary'];

    connect() {
        this.previewUrls = [];
        this.renderSelectionState();
    }

    disconnect() {
        this.clearPreviewUrls();
    }

    async submit(event) {
        event.preventDefault();

        const form = this.element;
        const formData = new FormData(form);

        this.setBusy(true);
        this.clearError();

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const payload = await this.readPayload(response);

            if (!response.ok || payload?.ok === false) {
                this.showError(payload?.message || 'Le message n’a pas pu être envoyé.');
                return;
            }

            form.reset();
            this.renderSelectionState();

            if (payload?.message) {
                form.dispatchEvent(new CustomEvent('conversation:message-created', {
                    bubbles: true,
                    detail: {
                        message: payload.message,
                    },
                }));
            }
        } catch (_error) {
            this.showError('Une erreur est survenue pendant l’envoi du message.');
        } finally {
            this.setBusy(false);
        }
    }

    filesChanged() {
        this.renderSelectionState();
    }

    async readPayload(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            return null;
        }

        return response.json();
    }

    renderSelectionState() {
        const files = this.hasFilesTarget && this.filesTarget.files
            ? Array.from(this.filesTarget.files)
            : [];

        this.renderSummary(files);
        this.renderPreview(files);
    }

    renderSummary(files) {
        if (!this.hasSummaryTarget) {
            return;
        }

        if (files.length === 0) {
            this.summaryTarget.textContent = 'Jusqu’à 5 photos.';
            return;
        }

        const label = files.length === 1 ? 'photo sélectionnée' : 'photos sélectionnées';
        this.summaryTarget.textContent = `${files.length} ${label}`;
    }

    renderPreview(files) {
        if (!this.hasPreviewTarget) {
            return;
        }

        this.clearPreviewUrls();
        this.previewTarget.innerHTML = '';

        if (files.length === 0) {
            this.previewTarget.hidden = true;
            return;
        }

        const fragment = document.createDocumentFragment();

        files.forEach((file) => {
            const item = document.createElement('figure');
            item.className = 'tm-conversation-preview-item';

            const image = document.createElement('img');
            image.className = 'tm-conversation-preview-image';
            image.alt = file.name || 'Photo sélectionnée';

            const objectUrl = URL.createObjectURL(file);
            this.previewUrls.push(objectUrl);
            image.src = objectUrl;

            const caption = document.createElement('figcaption');
            caption.className = 'tm-conversation-preview-caption';
            caption.textContent = file.name || 'Photo sélectionnée';

            item.append(image, caption);
            fragment.appendChild(item);
        });

        this.previewTarget.appendChild(fragment);
        this.previewTarget.hidden = false;
    }

    clearPreviewUrls() {
        this.previewUrls.forEach((url) => URL.revokeObjectURL(url));
        this.previewUrls = [];
    }

    setBusy(isBusy) {
        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = isBusy;
        }
    }

    clearError() {
        if (!this.hasErrorTarget) {
            return;
        }

        this.errorTarget.textContent = '';
        this.errorTarget.classList.add('d-none');
    }

    showError(message) {
        if (!this.hasErrorTarget) {
            window.alert(message);
            return;
        }

        this.errorTarget.textContent = message;
        this.errorTarget.classList.remove('d-none');
    }
}
