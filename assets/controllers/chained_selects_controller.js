import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['category', 'subcategory', 'service'];

    async loadSubCategories() {
        const catId = this.categoryTarget.value;

        this.resetSelect(this.subcategoryTarget, 'Choisir une sous-catégorie...');
        this.resetSelect(this.serviceTarget, 'Choisir un service...');

        if (!catId) return;

        const response = await fetch(`/api/subcategories/${catId}`);
        const data = await response.json();

        this.populateSelect(this.subcategoryTarget, data, 'Choisir une sous-catégorie...');
        this.enableSelect(this.subcategoryTarget);
    }

    async loadServices() {
        const subId = this.subcategoryTarget.value;

        this.resetSelect(this.serviceTarget, 'Choisir un service...');

        if (!subId) return;

        const response = await fetch(`/api/services/${subId}`);
        const data = await response.json();

        this.populateSelect(this.serviceTarget, data, 'Choisir un service...');
        this.enableSelect(this.serviceTarget);
    }

    populateSelect(element, data, placeholder = 'Sélectionnez...') {
        this.clearSelect(element, placeholder);

        data.forEach(item => {
            const option = new Option(item.name, item.id);
            element.add(option);
        });

        this.refreshTomSelect(element);
        this.dispatchNativeChange(element);
    }

    resetSelect(element, placeholder = 'Sélectionnez...') {
        this.clearSelect(element, placeholder);
        element.disabled = true;

        this.refreshTomSelect(element);
        this.dispatchNativeChange(element);
    }

    clearSelect(element, placeholder = 'Sélectionnez...') {
        element.innerHTML = '';
        element.add(new Option(placeholder, ''));
        element.value = '';
    }

    enableSelect(element) {
        element.disabled = false;

        this.refreshTomSelect(element);
        this.dispatchNativeChange(element);
    }

    refreshTomSelect(element) {
        if (element.tomselect) {
            element.tomselect.clear();
            element.tomselect.clearOptions();

            Array.from(element.options).forEach(option => {
                element.tomselect.addOption({
                    value: option.value,
                    text: option.text,
                });
            });

            element.tomselect.refreshOptions(false);
        }
    }

    dispatchNativeChange(element) {
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }
}