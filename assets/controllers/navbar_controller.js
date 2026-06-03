import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        
        this.dropdownElements = this.element.querySelectorAll('.dropdown');

        this.dropdownElements.forEach(dropdown => {
            const menu = dropdown.querySelector('.dropdown-menu');

            dropdown.addEventListener('show.bs.dropdown', () => {
                if (menu) menu.classList.add('animate-fade-in');
            });

            dropdown.addEventListener('hide.bs.dropdown', () => {
                if (menu) menu.classList.remove('animate-fade-in');
            });
        });
    }
}