import { Controller } from "@hotwired/stimulus";

const SWEETALERT_SCRIPT_URL =
    "https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js";
const SWEETALERT_STYLESHEET_URL =
    "https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css";
const DEFAULT_REGISTER_URL = "/register/choice";

export default class extends Controller {
    static targets = ["icon"];

    static values = {
        url: String,
        type: String,
        targetId: String,
        csrfToken: String,
        isFavorite: Boolean,
        loginUrl: String,
        registerUrl: String,
        removeOnUnfavorite: Boolean,
        removeClosestSelector: String,
    };

    connect() {
        this.isLoading = false;
        this.render();
    }

    async toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        this.element.disabled = true;

        try {
            const response = await fetch(this.urlValue, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                body: new URLSearchParams({
                    type: this.typeValue,
                    targetId: this.targetIdValue,
                    _token: this.csrfTokenValue,
                }),
                credentials: "same-origin",
            });

            const data = await this.parseResponseData(response);

            if (response.status === 401 || data?.requiresAuth === true) {
                await this.showAuthenticationAlert();
                return;
            }

            if (response.status === 403) {
                throw new Error(data?.message || "Accès réservé aux clients.");
            }

            if (!response.ok || !data?.success) {
                throw new Error(data?.message || "Impossible de mettre à jour les favoris.");
            }

            this.isFavoriteValue = Boolean(data.isFavorite);
            this.render();

            if (!this.isFavoriteValue && this.removeOnUnfavoriteValue) {
                this.removeElementFromView();
            }
        } catch (error) {
            window.alert(error.message || "Impossible de mettre à jour les favoris.");
        } finally {
            this.element.disabled = false;
            this.isLoading = false;
        }
    }

    async parseResponseData(response) {
        const contentType = response.headers.get("content-type") || "";

        if (contentType.includes("application/json")) {
            return response.json();
        }

        const text = await response.text();

        if (response.status === 401 || response.redirected || this.looksLikeHtml(text)) {
            return { success: false, requiresAuth: true };
        }

        if (text.trim() === "") {
            return null;
        }

        try {
            return JSON.parse(text);
        } catch {
            return {
                success: false,
                message: "Impossible de mettre à jour les favoris.",
            };
        }
    }

    looksLikeHtml(text) {
        const trimmedText = text.trim().toLowerCase();

        return (
            trimmedText.startsWith("<!doctype html") ||
            trimmedText.startsWith("<html") ||
            trimmedText.includes("<body")
        );
    }

    async showAuthenticationAlert() {
        const Swal = await this.loadSweetAlert();

        if (!Swal) {
            if (
                window.confirm(
                    "Connectez-vous ou créez un compte pour ajouter ce contenu à vos favoris."
                )
            ) {
                window.location.href = this.loginUrlValue;
            }

            return;
        }

        const result = await Swal.fire({
            title: "Enregistrez vos favoris",
            text: "Connectez-vous ou créez un compte client pour sauvegarder vos prestataires, prestations et bons plans favoris.",
            icon: "info",
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: "Se connecter",
            denyButtonText: "Créer un compte",
            cancelButtonText: "Plus tard",
            reverseButtons: true,
            focusConfirm: true,
            buttonsStyling: false,
            background: "#f8fbff",
            color: "#081a47",
            backdrop: "rgba(8, 26, 71, 0.55)",
            iconColor: "#0b2c78",
            customClass: {
                popup: "tm-swal-popup tm-swal-popup--primary",
                title: "tm-swal-title",
                htmlContainer: "tm-swal-text",
                icon: "tm-swal-icon",
                actions: "tm-swal-actions",
                confirmButton: "tm-swal-confirm",
                denyButton: "tm-swal-cancel",
                cancelButton: "tm-swal-cancel",
            },
        });

        if (result.isConfirmed) {
            window.location.href = this.loginUrlValue;
            return;
        }

        if (result.isDenied) {
            window.location.href = this.registerUrl;
        }
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
                    transform 0.18s ease,
                    box-shadow 0.18s ease,
                    opacity 0.18s ease !important;
            }
            .tm-swal-confirm {
                background: linear-gradient(135deg, #0b2c78 0%, #2b6cf6 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 14px 28px rgba(11, 44, 120, 0.24);
            }
            .tm-swal-cancel {
                background: rgba(255, 255, 255, 0.9) !important;
                color: #0b2c78 !important;
                box-shadow: inset 0 0 0 1px rgba(11, 44, 120, 0.18);
            }
            .tm-swal-confirm:hover,
            .tm-swal-confirm:focus-visible,
            .tm-swal-cancel:hover,
            .tm-swal-cancel:focus-visible {
                transform: translateY(-1px);
            }
            @media (max-width: 575.98px) {
                .tm-swal-popup {
                    border-radius: 24px;
                    padding: 1.2rem 1rem 1rem;
                }
                .tm-swal-title {
                    font-size: 1.35rem;
                }
                .tm-swal-actions {
                    flex-direction: column;
                }
                .tm-swal-confirm,
                .tm-swal-cancel {
                    width: 100%;
                }
            }
        `;
        document.head.appendChild(style);
    }

    get registerUrl() {
        if (this.hasRegisterUrlValue && this.registerUrlValue) {
            return this.registerUrlValue;
        }

        return DEFAULT_REGISTER_URL;
    }

    render() {
        this.element.setAttribute("aria-pressed", this.isFavoriteValue ? "true" : "false");
        this.element.setAttribute(
            "aria-label",
            this.isFavoriteValue ? "Retirer des favoris" : "Ajouter aux favoris"
        );

        if (!this.hasIconTarget) {
            return;
        }

        this.iconTarget.classList.remove("fa-regular", "fa-solid", "text-muted", "text-danger");
        this.iconTarget.classList.add("fa-heart");

        if (this.isFavoriteValue) {
            this.iconTarget.classList.add("fa-solid", "text-danger");
        } else {
            this.iconTarget.classList.add("fa-regular", "text-muted");
        }
    }

    removeElementFromView() {
        if (!this.hasRemoveClosestSelectorValue || !this.removeClosestSelectorValue) {
            return;
        }

        const elementToRemove = this.element.closest(this.removeClosestSelectorValue);
        if (!elementToRemove) {
            return;
        }

        elementToRemove.remove();
    }
}
