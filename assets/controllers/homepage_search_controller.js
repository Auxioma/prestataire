import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['range', 'value'];
    static values = {
        defaultRadius: Number,
    };

    connect() {
        this.update();
    }

    update() {
        if (!this.hasValueTarget) {
            return;
        }

        const value = this.hasRangeTarget && this.rangeTarget.value
            ? this.rangeTarget.value
            : this.defaultRadiusValue || 25;

        this.valueTarget.textContent = `${value} km`;
    }
}