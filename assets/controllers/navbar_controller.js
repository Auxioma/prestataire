import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.dropdownElements = this.element.querySelectorAll('.dropdown');

        this.dropdownElements.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');

            dropdown.addEventListener('show.bs.dropdown', () => {
                menu.classList.add('show');
            });

            dropdown.addEventListener('hide.bs.dropdown', (e) => {
                e.preventDefault();
                menu.classList.remove('show');
                
                setTimeout(() => {
                    const instance = bootstrap.Dropdown.getInstance(toggle);
                    if (instance) {
                        dropdown.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                }, 250);
            });
        });
    }
}