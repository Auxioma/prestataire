import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // On définit les éléments HTML (les select) que le JS va manipuler
    static targets = ["category", "subcategory", "service"];

    // Appelé quand le menu "Catégorie" change
    async loadSubCategories() {
        const catId = this.categoryTarget.value;
        
        // On réinitialise les menus suivants
        this.resetSelect(this.subcategoryTarget);
        this.resetSelect(this.serviceTarget);

        if (!catId) return;

        // Appel API vers ton CategoryController
        const response = await fetch(`/api/subcategories/${catId}`);
        const data = await response.json();
        
        // Remplissage du menu sous-catégorie
        this.populateSelect(this.subcategoryTarget, data);
        this.subcategoryTarget.disabled = false;
    }

    // Appelé quand le menu "Sous-catégorie" change
    async loadServices() {
        const subId = this.subcategoryTarget.value;
        
        // On réinitialise le menu service
        this.resetSelect(this.serviceTarget);

        if (!subId) return;

        // Appel API vers ton CategoryController
        const response = await fetch(`/api/services/${subId}`);
        const data = await response.json();

        this.populateSelect(this.serviceTarget, data);
        this.serviceTarget.disabled = false;
    }

    // Fonction utilitaire pour ajouter les options dans un select
    populateSelect(element, data) {
        data.forEach(item => {
            const option = new Option(item.name, item.id);
            element.add(option);
        });
    }

    // Fonction utilitaire pour vider un select
    resetSelect(element) {
        element.innerHTML = '<option value="">Sélectionnez...</option>';
        element.disabled = true;
    }
}