import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static classes = [ "hovered" ]

    connect() {
    }

    mouseEnter() {
        this.element.classList.add(this.hoveredClass);
    }

    mouseLeave() {
        this.element.classList.remove(this.hoveredClass);
    }
}