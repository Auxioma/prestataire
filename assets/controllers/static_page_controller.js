import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        document.body.style.backgroundColor = '#f8f9fa';

        // 1. Encapsulation globale dans une carte blanche
        this.element.classList.add('bg-white', 'p-4', 'p-md-5', 'rounded-3', 'shadow-sm', 'border', 'my-5');

        // 2. Style des paragraphes
        const paragraphs = this.element.querySelectorAll('p:not(.small)');
        paragraphs.forEach(p => {
            p.classList.add('lh-lg', 'text-secondary', 'mb-4');
            p.style.fontSize = '0.95rem';
        });

        // 3. Style des titres h2 UNIQUEMENT s'ils ne sont pas dans la FAQ
        const headings = this.element.querySelectorAll('h2:not(.accordion-header)');
        headings.forEach(h2 => {
            h2.classList.add('mt-5', 'mb-3', 'fw-extrabold', 'text-dark', 'h5', 'd-flex', 'align-items-center');
            
            const indicator = document.createElement('span');
            indicator.style.width = '4px';
            indicator.style.height = '18px';
            indicator.style.backgroundColor = 'var(--tm-cp-service, #2046D8)';
            indicator.style.borderRadius = '2px';
            indicator.style.marginRight = '10px';
            indicator.style.display = 'inline-block';
            
            h2.insertBefore(indicator, h2.firstChild);
        });

        // 4. Gestion de la FAQ et de ses bordures de cartes au survol
        const accordionItems = this.element.querySelectorAll('#faqAccordion .accordion-item');
        if (accordionItems.length > 0) {
            this.setupFaqEffects(accordionItems);
        }
    }

    disconnect() {
        document.body.style.backgroundColor = '';
    }

    setupFaqEffects(items) {
        items.forEach(item => {
            const button = item.querySelector('.accordion-button');
            
            // Transition des cartes : liseré bleu
            item.style.transition = 'border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out';

            // Au survol : toute la carte prend un liseré bleu
            item.addEventListener('mouseenter', () => {
                item.style.setProperty('border-color', 'var(--tm-cp-service, #2046D8)', 'important');
                item.style.boxShadow = '0 0 10px rgba(32, 70, 216, 0.15)';
            });

            // Quand on quitte : retour à la bordure normale
            item.addEventListener('mouseleave', () => {
                item.style.setProperty('border-color', '#dee2e6', 'important');
                item.style.boxShadow = 'none';
            });

            // Gestion de l'arrière-plan du bouton ouvert/fermé
            button.addEventListener('click', () => {
                setTimeout(() => {
                    if (!button.classList.contains('collapsed')) {
                        button.style.backgroundColor = 'rgba(32, 70, 216, 0.03)';
                        button.style.color = 'var(--tm-cp-service, #2046D8)';
                    } else {
                        button.style.backgroundColor = '#ffffff';
                        button.style.color = 'inherit';
                    }
                }, 50);
            });
        });
    }
}