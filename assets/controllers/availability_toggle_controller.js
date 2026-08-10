import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['checkbox', 'vacationPanel']

    static values = {
        morningStart: String,
        morningEnd: String,
        afternoonStart: String,
        afternoonEnd: String,
    }

    connect() {
        this.checkboxTargets.forEach((checkbox) => this.applyState(checkbox))
        this.syncVacationPanel()
    }

    toggle(event) {
        this.applyState(event.currentTarget)
    }

    toggleVacation() {
        this.syncVacationPanel()
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

    syncVacationPanel() {
        if (!this.hasVacationPanelTarget) {
            return
        }

        const vacationCheckbox = this.element.querySelector('input[name$="[isOnVacation]"]')
        if (!vacationCheckbox) {
            return
        }

        this.vacationPanelTarget.classList.toggle('is-hidden', !vacationCheckbox.checked)

        const dateField = this.vacationPanelTarget.querySelector('input[type="date"]')
        if (dateField) {
            dateField.disabled = !vacationCheckbox.checked

            if (!vacationCheckbox.checked) {
                dateField.value = ''
            }
        }
    }
}
