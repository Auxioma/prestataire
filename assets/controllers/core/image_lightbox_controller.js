import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["modal", "image", "counter", "trigger", "close"];

    connect() {
        this.currentIndex = 0;
        this.handleKeydown = this.handleKeydown.bind(this);
    }

    open(event) {
        const clickedIndex = parseInt(event.currentTarget.dataset.index, 10);

        if (Number.isNaN(clickedIndex)) {
            return;
        }

        this.currentIndex = clickedIndex;
        this.previousBodyOverflow = document.body.style.overflow;
        this.returnFocusTarget = event.currentTarget;
        this.showCurrentImage();
        this.modalTarget.hidden = false;
        document.body.style.overflow = "hidden";
        document.addEventListener("keydown", this.handleKeydown);
        this.closeTarget.focus();
    }

    close() {
        this.modalTarget.hidden = true;
        this.imageTarget.src = "";
        this.imageTarget.alt = "";
        document.body.style.overflow = this.previousBodyOverflow ?? "";
        document.removeEventListener("keydown", this.handleKeydown);
        this.returnFocusTarget?.focus();
        this.returnFocusTarget = null;
    }

    next() {
        if (this.triggerTargets.length === 0) {
            return;
        }

        this.currentIndex = (this.currentIndex + 1) % this.triggerTargets.length;
        this.showCurrentImage();
    }

    previous() {
        if (this.triggerTargets.length === 0) {
            return;
        }

        this.currentIndex =
            (this.currentIndex - 1 + this.triggerTargets.length) % this.triggerTargets.length;
        this.showCurrentImage();
    }

    showCurrentImage() {
        const current = this.triggerTargets[this.currentIndex];

        if (!current) {
            return;
        }

        this.imageTarget.src = current.dataset.src || "";
        this.imageTarget.alt = current.dataset.alt || "";

        if (this.hasCounterTarget) {
            this.counterTarget.textContent = `${this.currentIndex + 1} / ${this.triggerTargets.length}`;
        }
    }

    handleKeydown(event) {
        if (this.modalTarget.hidden) {
            return;
        }

        if (event.key === "Escape") {
            this.close();
            return;
        }

        if (event.key === "ArrowRight") {
            event.preventDefault();
            this.next();
        }

        if (event.key === "ArrowLeft") {
            event.preventDefault();
            this.previous();
        }

        if (event.key === "Tab") {
            const focusable = [...this.modalTarget.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])')]
                .filter((element) => element.getClientRects().length > 0);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (!first || !last) {
                event.preventDefault();
                this.closeTarget.focus();
            } else if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }

    disconnect() {
        document.removeEventListener("keydown", this.handleKeydown);
        document.body.style.overflow = this.previousBodyOverflow ?? "";
    }
}