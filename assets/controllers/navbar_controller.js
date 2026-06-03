import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // On récupère tous les dropdowns de la navbar
        this.dropdownElements = this.element.querySelectorAll('.dropdown');

        this.dropdownElements.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');

            // Événement Bootstrap quand le menu commence à s'ouvrir
            dropdown.addEventListener('show.bs.dropdown', () => {
                menu.classList.add('show');
            });

            // Événement Bootstrap quand le menu commence à se fermer
            dropdown.addEventListener('hide.bs.dropdown', (e) => {
                // On laisse le temps à l'animation CSS de se jouer (250ms) avant de retirer la classe
                e.preventDefault();
                menu.classList.remove('show');
                
                setTimeout(() => {
                    // On recrée manuellement la fermeture complète après l'animation
                    const instance = bootstrap.Dropdown.getInstance(toggle);
                    if (instance) {
                        dropdown.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                }, 250); // Doit correspondre à la durée de la transition CSS (0.25s)
            });
        });
    }
}