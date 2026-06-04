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
        if (this.hasToggleTarget) {
            this.renderClosedEye();
        }

        if (this.hasInputTarget && this.hasLengthTarget && this.hasUppercaseTarget && this.hasNumberTarget && this.hasSpecialTarget) {
            this.checkPassword();
        }
    }

    toggleVisibility() {
        if (!this.hasInputTarget || !this.hasToggleTarget) {
            return;
        }

        const visible = this.inputTarget.type === "text";
        this.inputTarget.type = visible ? "password" : "text";

        if (visible) {
            this.renderClosedEye();
        } else {
            this.renderOpenEye();
        }
    }

    checkPassword() {
        if (!this.hasInputTarget) {
            return;
        }

        const value = this.inputTarget.value;

        if (this.hasLengthTarget) {
            this.updateRequirement(this.lengthTarget, value.length >= 8);
        }

        if (this.hasUppercaseTarget) {
            this.updateRequirement(this.uppercaseTarget, /[A-Z]/.test(value));
        }

        if (this.hasNumberTarget) {
            this.updateRequirement(this.numberTarget, /\d/.test(value));
        }

        if (this.hasSpecialTarget) {
            this.updateRequirement(this.specialTarget, /[\W_]/.test(value));
        }
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
        if (this.hasToggleTarget) {
            this.toggleTarget.innerHTML = '<i class="bi bi-eye-fill"></i>';
        }
    }

    renderClosedEye() {
        if (this.hasToggleTarget) {
            this.toggleTarget.innerHTML = '<i class="bi bi-eye-slash-fill"></i>';
        }
    }
}