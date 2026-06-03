import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = [
        "input",
        "toggle",
        "length",
        "uppercase",
        "number",
        "special",
    ];

    connect() {
        this.renderClosedEye();
        this.checkPassword();
    }

    toggleVisibility() {
        const visible = this.inputTarget.type === "text";

        this.inputTarget.type = visible ? "password" : "text";

        if (visible) {
            this.renderClosedEye();
        } else {
            this.renderOpenEye();
        }
    }

    checkPassword() {
        const value = this.inputTarget.value;

        this.updateRequirement(this.lengthTarget, value.length >= 8);

        this.updateRequirement(this.uppercaseTarget, /[A-Z]/.test(value));

        this.updateRequirement(this.numberTarget, /\d/.test(value));

        this.updateRequirement(this.specialTarget, /[\W_]/.test(value));
    }

    updateRequirement(element, valid) {
        element.classList.remove("valid", "invalid");

        element.classList.add(valid ? "valid" : "invalid");

        const text = element.textContent
            .replace("✅", "")
            .replace("❌", "")
            .trim();

        element.textContent = `${valid ? "✅" : "❌"} ${text}`;
    }

    renderOpenEye() {
        this.toggleTarget.innerHTML = '<i class="bi bi-eye-fill"></i>';
    }

    renderClosedEye() {
        this.toggleTarget.innerHTML = '<i class="bi bi-eye-slash-fill"></i>';
    }
}
