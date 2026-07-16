import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['checkbox']

    static values = {
        morningStart: String,
        morningEnd: String,
        afternoonStart: String,
        afternoonEnd: String,
    }

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

            if (checkbox.checked && field.value === '') {
                this.applyDefaultValue(field, selector)
            }
        })
    }

    applyDefaultValue(field, selector) {
        const fieldName = field.name ?? ''

        if (selector === '.js-morning-panel') {
            if (fieldName.endsWith('[morningStart]') && this.hasMorningStartValue) {
                field.value = this.morningStartValue
            }

            if (fieldName.endsWith('[morningEnd]') && this.hasMorningEndValue) {
                field.value = this.morningEndValue
            }
        }

        if (selector === '.js-afternoon-panel') {
            if (fieldName.endsWith('[afternoonStart]') && this.hasAfternoonStartValue) {
                field.value = this.afternoonStartValue
            }

            if (fieldName.endsWith('[afternoonEnd]') && this.hasAfternoonEndValue) {
                field.value = this.afternoonEndValue
            }
        }
    }
}
