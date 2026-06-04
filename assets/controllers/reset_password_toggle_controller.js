import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['input', 'icon']

    toggle() {
        const isHidden = this.inputTarget.type === 'password'

        this.inputTarget.type = isHidden ? 'text' : 'password'

        this.element.setAttribute('data-password-toggle-visible', isHidden ? 'true' : 'false')

        const button = this.element.querySelector('[data-action*="password-toggle#toggle"]')
        if (button) {
            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false')
            button.setAttribute(
                'aria-label',
                isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
            )
        }

        if (this.hasIconTarget) {
            this.iconTarget.classList.toggle('bi-eye', !isHidden)
            this.iconTarget.classList.toggle('bi-eye-slash', isHidden)
        }
    }
}