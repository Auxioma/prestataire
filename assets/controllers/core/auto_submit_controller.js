import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    submit(event) {
        const form = event.currentTarget.form ?? this.element.closest('form');

        if (form) {
            form.requestSubmit();
        }
    }
}
