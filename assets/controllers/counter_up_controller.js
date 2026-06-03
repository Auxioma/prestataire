import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["number"]
    // On déclare proprement la valeur attendue (un nombre)
    static values = { end: Number }

    connect() {
        // Stimulus convertit automatiquement l'attribut HTML en variable JS disponible via "this.endValue"
        const targetNumber = this.endValue || 120000; 
        const duration = 1500; 
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsedTime = currentTime - startTime;
            const progress = Math.min(elapsedTime / duration, 1);
            
            const easeOutProgress = progress * (2 - progress);
            const currentNumber = Math.floor(easeOutProgress * targetNumber);

            // Formatage avec espace pour les milliers
            this.numberTarget.textContent = currentNumber.toLocaleString('fr-FR');

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }
}