import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["typeField", "proFieldsWrapper"];

    connect() {
        setTimeout(() => {
            this.toggleProFields();
        }, 1);
    }

    toggleProFields() {
        if (!this.hasTypeFieldTarget || !this.hasProFieldsWrapperTarget) return;

        const radios = this.typeFieldTarget.querySelectorAll('input[type="radio"]');
        const selectedRadio = Array.from(radios).find(radio => radio.checked);

        if (!selectedRadio) {
            this.proFieldsWrapperTarget.style.setProperty('display', 'none', 'important');
            return;
        }

        const radioValue = selectedRadio.value.toUpperCase();
        
        const labelElement = this.typeFieldTarget.querySelector(`label[for="${selectedRadio.id}"]`);
        const radioLabel = labelElement ? labelElement.textContent.toUpperCase() : '';

        const isPro = radioValue.includes('PRO') || 
                      radioValue.includes('PROFESSIONNEL') ||
                      radioValue.includes('COMPANY') || 
                      radioLabel.includes('PRO') || 
                      radioLabel.includes('ENTREPRISE') || 
                      radioLabel.includes('PROFESSIONNEL');

        if (isPro) {
            this.proFieldsWrapperTarget.style.setProperty('display', 'flex', 'important');
        } else {
            this.proFieldsWrapperTarget.style.setProperty('display', 'none', 'important');
        }
    }
}