import { Controller } from "@hotwired/stimulus";

const INITIALIZATION_FLAG = "__trouvemoiCookieConsentInitialized";
const CONSENT_DURATION_DAYS = 180;
const MAX_LOAD_ATTEMPTS = 100;
const LOAD_RETRY_DELAY = 50;

export default class extends Controller {
    static values = {
        privacyUrl: String,
        googleAnalyticsId: String,
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

        this.loadAttempts = 0;
        this.decorateManager();
        this.initializeWhenReady();
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

        if (this.loadTimer) {
            window.clearTimeout(this.loadTimer);
        }

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

    initializeWhenReady() {
        if (window[INITIALIZATION_FLAG]) {
            return;
        }

        const isLibraryReady =
            typeof window.tarteaucitron?.init === "function" &&
            typeof window.tarteaucitron.lang?.acceptAll === "string" &&
            Object.keys(window.tarteaucitron.services ?? {}).length > 0;

        if (!isLibraryReady) {
            if (this.loadAttempts >= MAX_LOAD_ATTEMPTS) {
                console.error(
                    "Le gestionnaire de cookies n’a pas pu être chargé depuis le CDN.",
                );

                return;
            }

            this.loadAttempts += 1;
            this.loadTimer = window.setTimeout(
                () => this.initializeWhenReady(),
                LOAD_RETRY_DELAY,
            );

            return;
        }

        window[INITIALIZATION_FLAG] = true;
        window.tarteaucitronForceLanguage = "fr";
        window.tarteaucitronForceExpire = CONSENT_DURATION_DAYS;
        window.tarteaucitronExpireInDay = true;
        window.tarteaucitronCustomText = {
            middleBarHead: "Vos préférences de confidentialité",
            alertBigPrivacy:
                "TrouveMoi utilise des cookies indispensables à son fonctionnement. Vous gardez le contrôle sur tout futur service facultatif.",
            info: "Vos préférences de confidentialité",
            disclaimer:
                "Retrouvez ici les services indispensables au fonctionnement de TrouveMoi et, lorsqu’ils seront proposés, vos choix concernant les services facultatifs.",
            all: "Services facultatifs",
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

        const googleAnalyticsId = this.hasGoogleAnalyticsIdValue
            ? this.googleAnalyticsIdValue.trim().toUpperCase()
            : "";

        if (/^G-[A-Z0-9]+$/.test(googleAnalyticsId)) {
            window.tarteaucitron.user.gtagUa = googleAnalyticsId;
            (window.tarteaucitron.job = window.tarteaucitron.job || []).push(
                "gtag",
            );
        }
    }
}
