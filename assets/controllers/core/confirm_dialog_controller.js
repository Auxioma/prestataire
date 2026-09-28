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
            iconColor:
                options.theme === "danger" ? "#c62828" : options.confirmButtonColor,
            customClass: {
                popup: `tm-swal-popup tm-swal-popup--${options.theme}`,
                title: "tm-swal-title",
                htmlContainer: "tm-swal-text",
                icon: "tm-swal-icon",
                actions: "tm-swal-actions",
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
        const title = trigger.dataset.confirmDialogTitleValue || this.titleValue || "";
        const text = trigger.dataset.confirmDialogTextValue || this.textValue || "";
        const icon = trigger.dataset.confirmDialogIconValue || this.iconValue;
        const confirmButtonText =
            trigger.dataset.confirmDialogConfirmButtonTextValue ||
            this.confirmButtonTextValue;
        const cancelButtonText =
            trigger.dataset.confirmDialogCancelButtonTextValue ||
            this.cancelButtonTextValue;
        const confirmButtonColor =
            trigger.dataset.confirmDialogConfirmButtonColorValue ||
            this.confirmButtonColorValue;
        const cancelButtonColor =
            trigger.dataset.confirmDialogCancelButtonColorValue ||
            this.cancelButtonColorValue;

        return {
            title,
            text,
            icon,
            confirmButtonText,
            cancelButtonText,
            confirmButtonColor,
            cancelButtonColor,
            theme: this.resolveTheme({
                title,
                text,
                icon,
                confirmButtonText,
            }),
        };
    }

    resolveTheme({ title, text, icon, confirmButtonText }) {
        const fingerprint = `${title} ${text} ${confirmButtonText}`.toLowerCase();

        if (
            icon === "error" ||
            /supprim|refus|définitive|definitive|irr[eé]versible/.test(fingerprint)
        ) {
            return "danger";
        }

        return "primary";
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
                position: relative;
                border: 1px solid rgba(11, 44, 120, 0.12);
                border-radius: 28px;
                padding: 1.35rem 1.35rem 1.15rem;
                background:
                    radial-gradient(circle at top right, rgba(27, 79, 201, 0.12), transparent 38%),
                    linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
                box-shadow: 0 24px 60px rgba(8, 26, 71, 0.24);
                overflow: hidden;
            }
            .tm-swal-popup::before {
                content: "";
                position: absolute;
                inset: 0 0 auto 0;
                height: 4px;
                background: linear-gradient(90deg, #0b2c78 0%, #2b6cf6 100%);
            }
            .tm-swal-popup.tm-swal-popup--danger::before {
                background: linear-gradient(90deg, #9f1239 0%, #ef4444 100%);
            }
            .tm-swal-title {
                color: #081a47;
                font-weight: 800;
                letter-spacing: -0.03em;
                font-size: 1.55rem;
                line-height: 1.15;
                margin-top: 0.35rem;
            }
            .tm-swal-text {
                color: #4b5563;
                line-height: 1.6;
                font-size: 0.97rem;
                max-width: 32rem;
                margin: 0 auto;
            }
            .tm-swal-icon {
                transform: scale(0.92);
                margin-top: 0.55rem;
                margin-bottom: 0.45rem;
            }
            .tm-swal-actions {
                gap: 0.75rem;
                margin-top: 1.4rem;
                width: 100%;
                justify-content: center;
            }
            .tm-swal-confirm,
            .tm-swal-cancel {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-width: 10.5rem;
                border-radius: 999px !important;
                border: 0 !important;
                font-weight: 700 !important;
                letter-spacing: -0.01em;
                padding: 0.88rem 1.25rem !important;
                transition:
                    transform 0.16s ease,
                    box-shadow 0.16s ease,
                    background 0.16s ease,
                    color 0.16s ease !important;
                box-shadow: none !important;
            }
            .tm-swal-confirm {
                background: linear-gradient(135deg, #0b2c78 0%, #1d4ed8 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 14px 28px rgba(12, 47, 134, 0.24) !important;
            }
            .tm-swal-popup--danger .tm-swal-confirm {
                background: linear-gradient(135deg, #9f1239 0%, #dc2626 100%) !important;
                box-shadow: 0 14px 28px rgba(185, 28, 28, 0.24) !important;
            }
            .tm-swal-cancel {
                background: #ffffff !important;
                color: #081a47 !important;
                border: 1px solid rgba(8, 26, 71, 0.14) !important;
            }
            .tm-swal-confirm:hover,
            .tm-swal-confirm:focus-visible,
            .tm-swal-cancel:hover,
            .tm-swal-cancel:focus-visible {
                transform: translateY(-1px);
            }
            .tm-swal-cancel:hover,
            .tm-swal-cancel:focus-visible {
                box-shadow: 0 10px 24px rgba(8, 26, 71, 0.1) !important;
            }
            @media (max-width: 575.98px) {
                .tm-swal-popup {
                    width: min(100%, 92vw);
                    padding: 1.15rem 1rem 1rem;
                    border-radius: 24px;
                }
                .tm-swal-title {
                    font-size: 1.35rem;
                }
                .tm-swal-actions {
                    flex-direction: column-reverse;
                }
                .tm-swal-confirm,
                .tm-swal-cancel {
                    width: 100%;
                    min-width: 0;
                    margin: 0 !important;
                }
            }
        `;
        document.head.appendChild(style);
    }
}
