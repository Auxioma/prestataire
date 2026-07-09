import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['category', 'subcategory', 'service'];

    async loadSubCategories() {
        const catId = this.categoryTarget.value;

        this.resetSelect(this.subcategoryTarget, 'Choisir une sous-catégorie...');
        this.resetSelect(this.serviceTarget, 'Choisir un service...');

        if (!catId) return;

        try {
            const response = await fetch(`/api/subcategories/${catId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des sous-categories');
            }

            const data = await response.json();

            this.populateSelect(this.subcategoryTarget, Array.isArray(data) ? data : [], 'Choisir une sous-catégorie...');
            this.enableSelect(this.subcategoryTarget);
        } catch (error) {
            this.resetSelect(this.subcategoryTarget, 'Choisir une sous-catégorie...');
            this.resetSelect(this.serviceTarget, 'Choisir un service...');
        }
    }

    async loadServices() {
        const subId = this.subcategoryTarget.value;

        this.resetSelect(this.serviceTarget, 'Choisir un service...');

        if (!subId) return;

        try {
            const response = await fetch(`/api/services/${subId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des services');
            }

            const data = await response.json();

            this.populateSelect(this.serviceTarget, Array.isArray(data) ? data : [], 'Choisir un service...');
            this.enableSelect(this.serviceTarget);
        } catch (error) {
            this.resetSelect(this.serviceTarget, 'Choisir un service...');
        }
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
