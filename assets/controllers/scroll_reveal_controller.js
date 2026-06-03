import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["element"]

    connect() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1";
                    entry.target.style.transform = "translateY(0)";
                }
            });
        }, { threshold: 0.1 });

        this.elementTargets.forEach(el => {
            el.style.opacity = "0";
            el.style.transform = "translateY(20px)";
            el.style.transition = "all 0.6s ease-out";
            observer.observe(el);
        });
    }
}