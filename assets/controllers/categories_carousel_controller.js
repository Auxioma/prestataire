import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["viewport", "track", "item"];
    static values = {
        step: { type: Number, default: 1 },
        interval: { type: Number, default: 2000 }, // Défilement toutes les 2s
    };

    connect() {
        this.currentIndex = 0;
        this.visibleItems = 1;
        this.autoplayTimer = null;
        this.metricsTimer = null;

        this.updateMetrics = this.updateMetrics.bind(this);
        this.onResize = this.onResize.bind(this);
        this.onMouseEnter = this.stopAutoplay.bind(this);
        this.onMouseLeave = this.startAutoplay.bind(this);

        window.addEventListener("resize", this.onResize);

        // Laisse un tick au navigateur pour poser le Flexbox avant de calculer les tailles
        this.metricsTimer = window.setTimeout(() => {
            this.updateMetrics();
            this.startAutoplay();
            this.metricsTimer = null;
        }, 200);

        // Gestion du survol
        this.element.addEventListener("mouseenter", this.onMouseEnter);
        this.element.addEventListener("mouseleave", this.onMouseLeave);
    }

    disconnect() {
        window.removeEventListener("resize", this.onResize);
        this.element.removeEventListener("mouseenter", this.onMouseEnter);
        this.element.removeEventListener("mouseleave", this.onMouseLeave);

        if (this.metricsTimer) {
            clearTimeout(this.metricsTimer);
            this.metricsTimer = null;
        }

        this.stopAutoplay();
    }

    onResize() {
        this.updateMetrics();
        this.goTo(this.currentIndex, false);
    }

    next() {
        const maxIndex = this.maxIndex();
        if (this.currentIndex >= maxIndex) {
            this.goTo(0); // Retour fluide au début
        } else {
            this.goTo(this.currentIndex + this.stepValue);
        }
    }

    startAutoplay() {
        this.stopAutoplay();
        this.autoplayTimer = setInterval(() => {
            this.next();
        }, this.intervalValue);
    }

    stopAutoplay() {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    goTo(index, animated = true) {
        const maxIndex = this.maxIndex();
        this.currentIndex = Math.max(0, Math.min(index, maxIndex));

        const item = this.itemTargets[0];
        if (!item) return;

        const trackStyle = window.getComputedStyle(this.trackTarget);
        const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || 0);
        const offset = this.currentIndex * (item.offsetWidth + gap);

        this.trackTarget.style.transition = animated
            ? "transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)"
            : "none";
        this.trackTarget.style.transform = `translateX(-${offset}px)`;

        if (!animated) {
            requestAnimationFrame(() => {
                this.trackTarget.style.transition =
                    "transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)";
            });
        }
    }

    updateMetrics() {
        const item = this.itemTargets[0];
        const viewport = this.viewportTarget;

        if (!item || !viewport) return;

        const trackStyle = window.getComputedStyle(this.trackTarget);
        const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || 0);
        const viewportWidth = viewport.offsetWidth;
        const itemWidth = item.offsetWidth;

        this.visibleItems = Math.max(
            1,
            Math.round((viewportWidth + gap) / (itemWidth + gap)),
        );
    }

    maxIndex() {
        return Math.max(0, this.itemTargets.length - this.visibleItems);
    }
}
