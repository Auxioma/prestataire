import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['items', 'prototype'];
    static values = {
        index: Number,
    };

    connect() {
        if (!this.hasIndexValue) {
            this.indexValue = this.itemsTarget.children.length;
        }
    }

    add(event) {
        event.preventDefault();

        const template = this.prototypeTarget.innerHTML.trim();
        const html = template.replace(/__name__/g, this.indexValue);

        this.itemsTarget.insertAdjacentHTML('beforeend', html);
        this.indexValue++;
    }
}