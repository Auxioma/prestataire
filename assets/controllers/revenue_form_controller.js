import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['subtotal', 'tax', 'total'];

    connect() {
        this.updateTotal();
    }

    updateTotal() {
        if (!this.hasSubtotalTarget || !this.hasTaxTarget || !this.hasTotalTarget) {
            return;
        }

        const subtotalValue = this.parseAmount(this.subtotalTarget.value);
        const taxValue = this.parseAmount(this.taxTarget.value);

        if (subtotalValue === null && taxValue === null) {
            return;
        }

        const subtotal = subtotalValue ?? 0;
        const tax = taxValue ?? 0;
        const total = subtotal + tax;

        this.totalTarget.value = total.toFixed(2);
    }

    parseAmount(value) {
        const normalized = String(value ?? '')
            .trim()
            .replace(/\s+/g, '')
            .replace(',', '.');

        if (normalized === '') {
            return null;
        }

        const parsed = Number.parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : null;
    }
}
