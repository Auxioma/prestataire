import { Controller } from "@hotwired/stimulus";

const SWEETALERT_SCRIPT_URL =
    "https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js";
const SWEETALERT_STYLESHEET_URL =
    "https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css";

export default class extends Controller {
    static values = {
        title: String,
        text: String,
        icon: { type: String, default: "warning" },
        confirmButtonText: { type: String, default: "Confirmer" },
        cancelButtonText: { type: String, default: "Annuler" },
        confirmButtonColor: { type: String, default: "#0b2c78" },
        cancelButtonColor: { type: String, default: "#d64045" },
    };

    connect() {
        this.confirmed = false;
    }

    async confirm(event) {
        if (this.confirmed) {
            return;
        }

        const trigger = event.submitter ?? this.element;
        const options = this.buildOptions(trigger);

        if (!options.title && !options.text) {
            return;
        }

        event.preventDefault();

        const Swal = await this.loadSweetAlert();
        if (!Swal) {
            if (window.confirm(options.text || options.title || "Confirmer cette action ?")) {
                this.confirmed = true;

                if (typeof this.element.requestSubmit === "function") {
                    this.element.requestSubmit(trigger);
                } else {
                    this.element.submit();
                }
            }

            return;
        }

        const result = await Swal.fire({
            title: options.title,
            text: options.text,
            icon: options.icon,
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText,
            cancelButtonText: options.cancelButtonText,
            confirmButtonColor: options.confirmButtonColor,
            cancelButtonColor: options.cancelButtonColor,
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            background: "#f8fbff",
            color: "#081a47",
            backdrop: "rgba(8, 26, 71, 0.55)",
            customClass: {
                popup: "tm-swal-popup",
                title: "tm-swal-title",
                htmlContainer: "tm-swal-text",
                confirmButton: "tm-swal-confirm",
                cancelButton: "tm-swal-cancel",
            },
        });

        if (!result.isConfirmed) {
            return;
        }

        this.confirmed = true;

        if (typeof this.element.requestSubmit === "function") {
            this.element.requestSubmit(trigger);
        } else {
            this.element.submit();
        }
    }

    buildOptions(trigger) {
        return {
            title: trigger.dataset.confirmDialogTitleValue || this.titleValue || "",
            text: trigger.dataset.confirmDialogTextValue || this.textValue || "",
            icon: trigger.dataset.confirmDialogIconValue || this.iconValue,
            confirmButtonText:
                trigger.dataset.confirmDialogConfirmButtonTextValue ||
                this.confirmButtonTextValue,
            cancelButtonText:
                trigger.dataset.confirmDialogCancelButtonTextValue ||
                this.cancelButtonTextValue,
            confirmButtonColor:
                trigger.dataset.confirmDialogConfirmButtonColorValue ||
                this.confirmButtonColorValue,
            cancelButtonColor:
                trigger.dataset.confirmDialogCancelButtonColorValue ||
                this.cancelButtonColorValue,
        };
    }

    async loadSweetAlert() {
        if (window.Swal) {
            return window.Swal;
        }

        this.ensureStylesheet();

        if (this.constructor.loadingPromise) {
            await this.constructor.loadingPromise;

            return window.Swal ?? null;
        }

        this.constructor.loadingPromise = new Promise((resolve) => {
            const existingScript = document.querySelector(
                `script[src="${SWEETALERT_SCRIPT_URL}"]`
            );

            if (existingScript) {
                existingScript.addEventListener("load", () => resolve(window.Swal ?? null), {
                    once: true,
                });
                existingScript.addEventListener("error", () => resolve(null), {
                    once: true,
                });

                return;
            }

            const script = document.createElement("script");
            script.src = SWEETALERT_SCRIPT_URL;
            script.async = true;
            script.addEventListener("load", () => resolve(window.Swal ?? null), {
                once: true,
            });
            script.addEventListener("error", () => resolve(null), {
                once: true,
            });
            document.head.appendChild(script);
        });

        await this.constructor.loadingPromise;

        return window.Swal ?? null;
    }

    ensureStylesheet() {
        if (
            document.querySelector(`link[href="${SWEETALERT_STYLESHEET_URL}"]`) ||
            document.querySelector('style[data-sweetalert-theme="tm"]')
        ) {
            return;
        }

        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = SWEETALERT_STYLESHEET_URL;
        document.head.appendChild(link);

        const style = document.createElement("style");
        style.dataset.sweetalertTheme = "tm";
        style.textContent = `
            .tm-swal-popup {
                border: 1px solid rgba(11, 44, 120, 0.12);
                border-radius: 24px;
                background:
                    radial-gradient(circle at top right, rgba(27, 79, 201, 0.12), transparent 38%),
                    linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
                box-shadow: 0 24px 60px rgba(8, 26, 71, 0.24);
            }
            .tm-swal-title {
                color: #081a47;
                font-weight: 800;
                letter-spacing: -0.02em;
            }
            .tm-swal-text {
                color: #4b5563;
                line-height: 1.6;
            }
            .tm-swal-confirm,
            .tm-swal-cancel {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 999px !important;
                border: 0 !important;
                font-weight: 700 !important;
                padding: 0.8rem 1.25rem !important;
                box-shadow: none !important;
            }
            .tm-swal-confirm {
                background: linear-gradient(135deg, #0b2c78 0%, #1d4ed8 100%) !important;
            }
            .tm-swal-cancel {
                background: #ffffff !important;
                color: #081a47 !important;
                border: 1px solid rgba(8, 26, 71, 0.14) !important;
            }
        `;
        document.head.appendChild(style);
    }
}
