import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'input',
        'length',
        'uppercase',
        'number',
        'special'
    ];

    connect() {
        this.checkPassword();
    }

    checkPassword() {
        const val = this.inputTarget.value;

        this.updateRequirement(
            this.lengthTarget,
            val.length >= 8,
            'Au moins 8 caractères'
        );

        this.updateRequirement(
            this.uppercaseTarget,
            /[A-Z]/.test(val),
            'Au moins une majuscule'
        );

        this.updateRequirement(
            this.numberTarget,
            /\d/.test(val),
            'Au moins un chiffre'
        );

        this.updateRequirement(
            this.specialTarget,
            /[\W_]/.test(val),
            'Au moins un caractère spécial (@, $, !, %, *, ?, &, #, _, etc.)'
        );
    }

    updateRequirement(element, isValid, text) {
        if (isValid) {
            element.classList.remove('text-danger');
            element.classList.add('text-success', 'fw-semibold');

            element.innerHTML = `
                ✅ <span class="ms-1">${text}</span>
            `;
        } else {
            element.classList.remove('text-success', 'fw-semibold');
            element.classList.add('text-danger');

            element.innerHTML = `
                ❌ <span class="ms-1">${text}</span>
            `;
        }
    }
}