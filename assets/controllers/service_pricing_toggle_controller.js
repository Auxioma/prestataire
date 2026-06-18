import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['pricingType', 'priceBlock', 'reductionBlock', 'quoteInfo'];

    connect() {
        this.toggle();
    }

    toggle() {
        if (!this.hasPricingTypeTarget) {
            return;
        }

        const isQuote = this.pricingTypeTarget.value === 'quote';

        if (this.hasPriceBlockTarget) {
            this.priceBlockTarget.classList.toggle('d-none', isQuote);
        }

        if (this.hasReductionBlockTarget) {
            this.reductionBlockTarget.classList.toggle('d-none', isQuote);
        }

        if (this.hasQuoteInfoTarget) {
            this.quoteInfoTarget.classList.toggle('d-none', !isQuote);
        }

        const priceInput = this.hasPriceBlockTarget
            ? this.priceBlockTarget.querySelector('input')
            : null;

        const reductionInput = this.hasReductionBlockTarget
            ? this.reductionBlockTarget.querySelector('input')
            : null;

        if (priceInput) {
            priceInput.required = !isQuote;
        }

        if (isQuote && reductionInput) {
            reductionInput.value = '';
        }
    }
}