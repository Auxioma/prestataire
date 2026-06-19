import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["modal", "image", "counter", "trigger"];

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
        this.showCurrentImage();
        this.modalTarget.hidden = false;
        document.body.style.overflow = "hidden";
        document.addEventListener("keydown", this.handleKeydown);
    }

    close() {
        this.modalTarget.hidden = true;
        this.imageTarget.src = "";
        this.imageTarget.alt = "";
        document.body.style.overflow = "";
        document.removeEventListener("keydown", this.handleKeydown);
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
        }

        if (event.key === "ArrowRight") {
            this.next();
        }

        if (event.key === "ArrowLeft") {
            this.previous();
        }
    }

    disconnect() {
        document.removeEventListener("keydown", this.handleKeydown);
        document.body.style.overflow = "";
    }
}