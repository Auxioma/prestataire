import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['checkbox']

    connect() {
        this.checkboxTargets.forEach((checkbox) => this.applyState(checkbox))
    }

    toggle(event) {
        this.applyState(event.currentTarget)
    }

    applyState(checkbox) {
        const selector = checkbox.dataset.availabilityTogglePanelSelectorValue
        if (!selector) {
            return
        }

        const slot = checkbox.closest('[data-availability-toggle-target="slot"]')
        if (!slot) {
            return
        }

        const panel = slot.querySelector(selector)
        if (!panel) {
            return
        }

        panel.classList.toggle('is-hidden', !checkbox.checked)

        const timeFields = panel.querySelectorAll('input[type="time"]')
        timeFields.forEach((field) => {
            field.disabled = !checkbox.checked

            if (!checkbox.checked) {
                field.value = ''
            }
        })
    }
}