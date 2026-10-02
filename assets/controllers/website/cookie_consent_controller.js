import { Controller } from "@hotwired/stimulus";

const INITIALIZATION_FLAG = "__trouvemoiCookieConsentInitialized";
const CONSENT_DURATION_DAYS = 180;
const TARTEAUCITRON_SCRIPT_URL =
    "https://cdn.jsdelivr.net/gh/AmauriC/tarteaucitron.js@master/tarteaucitron.js";

export default class extends Controller {
    static values = {
        gaId: String,
        gtmId: String,
        privacyUrl: String,
    };

    connect() {
        this.handleCookieSettingsClick =
            this.handleCookieSettingsClick.bind(this);
        this.handleManagerReady = this.handleManagerReady.bind(this);
        this.preserveManagerBeforeRender =
            this.preserveManagerBeforeRender.bind(this);
        this.element.addEventListener("click", this.handleCookieSettingsClick);
        document.addEventListener(
            "turbo:before-render",
            this.preserveManagerBeforeRender,
        );
        window.addEventListener("tac.root_available", this.handleManagerReady);

        this.initializeWhenReady = this.initializeWhenReady.bind(this);
        // Deferred CDN scripts may finish after Stimulus connects.
        document.addEventListener("DOMContentLoaded", this.initializeWhenReady);
        window.addEventListener("load", this.initializeWhenReady);
        this.decorateManager();
        this.loadTarteaucitron();
    }

    disconnect() {
        this.element.removeEventListener(
            "click",
            this.handleCookieSettingsClick,
        );
        document.removeEventListener(
            "turbo:before-render",
            this.preserveManagerBeforeRender,
        );
        window.removeEventListener(
            "tac.root_available",
            this.handleManagerReady,
        );
        this.managerObserver?.disconnect();

        document.removeEventListener(
            "DOMContentLoaded",
            this.initializeWhenReady,
        );
        window.removeEventListener("load", this.initializeWhenReady);

        if (this.decorateTimer) {
            window.clearTimeout(this.decorateTimer);
        }
    }

    handleManagerReady() {
        this.decorateTimer = window.setTimeout(() => this.decorateManager(), 0);
    }

    decorateManager() {
        const managerRoot = document.getElementById("tarteaucitronRoot");

        if (!managerRoot) {
            return;
        }

        this.updateManagerState(managerRoot);
        this.managerObserver?.disconnect();
        this.managerObserver = new MutationObserver(() =>
            this.updateManagerState(managerRoot),
        );
        this.managerObserver.observe(managerRoot, {
            childList: true,
            subtree: true,
        });
    }

    updateManagerState(managerRoot) {
        const optionalServiceLists = Array.from(
            managerRoot.querySelectorAll('ul[id^="tarteaucitronServices_"]'),
        ).filter(
            (list) =>
                ![
                    "tarteaucitronServices_mandatory",
                    "tarteaucitronServices_cookies",
                ].includes(list.id),
        );
        const hasOptionalServices = optionalServiceLists.some((list) =>
            list.querySelector(".tarteaucitronLine"),
        );

        managerRoot.classList.toggle(
            "tm-cookie-consent--essential-only",
            !hasOptionalServices,
        );

        const mandatoryLine = managerRoot.querySelector(
            "#tarteaucitronServices_mandatory .tarteaucitronLine",
        );

        if (
            mandatoryLine &&
            !mandatoryLine.querySelector(".tm-cookie-consent__status")
        ) {
            const status = document.createElement("span");
            status.className = "tm-cookie-consent__status";
            status.textContent = "Toujours actifs";
            mandatoryLine.append(status);
        }

        const saveButton = managerRoot.querySelector(
            "#tarteaucitronSaveButton",
        );
        const expectedLabel = hasOptionalServices
            ? window.tarteaucitron.lang.save
            : window.tarteaucitron.lang.close;

        if (saveButton && saveButton.textContent !== expectedLabel) {
            saveButton.textContent = expectedLabel;
        }
    }

    handleCookieSettingsClick(event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest("#tm-cookie-settings");

        if (!trigger || !this.element.contains(trigger)) {
            return;
        }

        const openPanel = window.tarteaucitron?.userInterface?.openPanel;

        if (
            typeof openPanel !== "function" ||
            !document.getElementById("tarteaucitron")
        ) {
            return;
        }

        event.preventDefault();
        openPanel.call(window.tarteaucitron.userInterface);
    }

    preserveManagerBeforeRender(event) {
        const managerRoot = document.getElementById("tarteaucitronRoot");
        const newBody = event.detail?.newBody;

        if (!managerRoot || !(newBody instanceof HTMLBodyElement)) {
            return;
        }

        newBody.append(managerRoot);
    }

    async loadTarteaucitron() {
        try {
            // Avoid loading the library more than once.
            if (!window.tarteaucitron) {
                await this.loadScript(TARTEAUCITRON_SCRIPT_URL);
            }

            this.initializeWhenReady();
        } catch (error) {
            console.error(
                "Le gestionnaire de cookies n’a pas pu être chargé depuis le CDN.",
                error,
            );
        }
    }

    initializeWhenReady() {
        if (window[INITIALIZATION_FLAG]) {
            return;
        }

        const isLibraryReady =
            typeof window.tarteaucitron?.init === "function" &&
            typeof window.tarteaucitron.lang?.acceptAll === "string" &&
            Object.keys(window.tarteaucitron.services ?? {}).length > 0;

        if (!isLibraryReady) {
            // Wait for the scripts, even on connections taking more than five seconds.
            if (document.readyState === "complete") {
                console.error(
                    "Le gestionnaire de cookies n’a pas pu être chargé depuis le CDN.",
                );
            }

            return;
        }

        window[INITIALIZATION_FLAG] = true;
        window.tarteaucitronForceLanguage = "fr";
        window.tarteaucitronForceExpire = CONSENT_DURATION_DAYS;
        window.tarteaucitronExpireInDay = true;
        window.tarteaucitronCustomText = {
            middleBarHead: "Vos préférences de confidentialité",
            alertBigPrivacy:
                "TrouveMoi utilise des cookies nécessaires et, avec votre accord, une mesure d’audience. Vous pouvez accepter, refuser ou personnaliser vos choix.",
            info: "Vos préférences de confidentialité",
            disclaimer:
                "Choisissez les services que vous autorisez. Les cookies indispensables restent actifs pour assurer le fonctionnement et la sécurité du site.",
            all: "Choix globaux",
            noServices:
                "Aucun cookie facultatif nécessitant votre consentement n’est actuellement utilisé.",
            mandatoryTitle: "Cookies indispensables",
            mandatoryText:
                "Ces cookies assurent la connexion, la sécurité et les services que vous demandez. Ils ne peuvent pas être désactivés.",
            privacyUrl: "Consulter la politique de confidentialité",
        };

        const privacyUrl = this.hasPrivacyUrlValue ? this.privacyUrlValue : "";

        window.tarteaucitron.init({
            privacyUrl,
            readmoreLink: privacyUrl,
            bodyPosition: "bottom",
            hashtag: "#gestion-cookies",
            cookieName: "trouvemoi_cookie_consent",
            orientation: "bottom",
            groupServices: false,
            showDetailsOnClick: false,
            serviceDefaultState: "wait",
            showAlertSmall: false,
            showTitleBanner: true,
            cookieslist: false,
            cookieslistEmbed: false,
            showIcon: false,
            adblocker: false,
            DenyAllCta: true,
            AcceptAllCta: true,
            highPrivacy: true,
            alwaysNeedConsent: false,
            handleBrowserDNTRequest: false,
            removeCredit: true,
            moreInfoLink: true,
            useExternalCss: true,
            useExternalJs: true,
            mandatory: true,
            mandatoryCta: false,
            customCloserId: "tm-cookie-settings",
            googleConsentMode: false,
            bingConsentMode: false,
            pianoConsentMode: false,
            piwikConsentMode: false,
            softConsentMode: false,
            dataLayer: false,
            serverSide: false,
            partnersList: false,
        });

        this.configureGoogleAnalytics();
        this.configureGoogleTagManager();
    }

    configureGoogleAnalytics() {
        const googleAnalyticsId = this.hasGaIdValue
            ? this.gaIdValue.trim().toUpperCase()
            : "";

        if (/^G-[A-Z0-9]+$/.test(googleAnalyticsId)) {
            window.tarteaucitron.user.gtagUa = googleAnalyticsId;
            window.tarteaucitron.user.gtagMore = () => {};
            (window.tarteaucitron.job = window.tarteaucitron.job || []).push(
                "gtag",
            );
        }
    }

    configureGoogleTagManager() {
        const googleTagManagerId = this.hasGtmIdValue
            ? this.gtmIdValue.trim().toUpperCase()
            : "";

        if (!/^GTM-[A-Z0-9]+$/.test(googleTagManagerId)) {
            return;
        }

        window.tarteaucitron.user.googletagmanagerId = googleTagManagerId;
        (window.tarteaucitron.job = window.tarteaucitron.job || []).push(
            "googletagmanager",
        );
    }

    loadScript(src) {
        return new Promise((resolve, reject) => {
            const existingScript = document.querySelector(
                `script[src="${src}"]`,
            );

            if (existingScript) {
                existingScript.addEventListener("load", resolve, {
                    once: true,
                });
                existingScript.addEventListener("error", reject, {
                    once: true,
                });
                return;
            }

            const script = document.createElement("script");

            script.src = src;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;

            document.head.appendChild(script);
        });
    }

    openPreferences() {
        const openPanel = window.tarteaucitron?.userInterface?.openPanel;

        if (typeof openPanel === "function") {
            openPanel.call(window.tarteaucitron.userInterface);
        }
    }
}
