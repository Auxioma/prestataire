import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        eventsUrl: String,
        updateUrl: String,
        csrfToken: String,
        scriptUrl: String,
        compact: { type: Boolean, default: false },
        height: { type: Number, default: 650 },
    };

    connect() {
        this.initialized = false;
        this.initTimeout = null;
        this.resizeTimeout = null;
        this.retryCount = 0;
        this.loadPromise = null;
        this.handleTabShown = this.handleTabShown.bind(this);
        this.handleWindowLoad = this.handleWindowLoad.bind(this);

        document.addEventListener("shown.bs.tab", this.handleTabShown);
        window.addEventListener("load", this.handleWindowLoad, {
            once: true,
        });

        this.initCalendarWhenReady();
    }

    disconnect() {
        document.removeEventListener("shown.bs.tab", this.handleTabShown);
        window.removeEventListener("load", this.handleWindowLoad);

        if (this.initTimeout) {
            clearTimeout(this.initTimeout);
            this.initTimeout = null;
        }

        if (this.resizeTimeout) {
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = null;
        }

        if (this.calendar) {
            this.calendar.destroy();
            this.calendar = null;
        }
    }

    handleWindowLoad() {
        this.initCalendarWhenReady(true);
    }

    handleTabShown(event) {
        if (!this.isVisible()) {
            return;
        }

        if (!this.initialized) {
            this.initTimeout = window.setTimeout(() => {
                this.initCalendarWhenReady();
                this.initTimeout = null;
            }, 30);
            return;
        }

        this.resizeTimeout = window.setTimeout(() => {
            this.calendar?.updateSize();
            this.resizeTimeout = null;
        }, 60);
    }

    initCalendar() {
        if (
            this.initialized ||
            !this.element ||
            typeof FullCalendar === "undefined"
        ) {
            return;
        }

        this.calendar = new FullCalendar.Calendar(this.element, {
            locale: "fr",
            timeZone: "local",
            initialView: "dayGridMonth",
            firstDay: 1,
            height: this.heightValue,
            nowIndicator: true,
            selectable: true,
            editable: true,
            dayMaxEvents: true,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: this.compactValue ? "" : "dayGridMonth,timeGridWeek,timeGridDay,listWeek",
            },
            buttonText: {
                today: "Aujourd’hui",
                month: "Mois",
                week: "Semaine",
                day: "Jour",
                list: "Liste",
            },
            events: this.eventsUrlValue,
            eventTimeFormat: {
                hour: "2-digit",
                minute: "2-digit",
                hour12: false,
            },
            eventClick: (info) => {
                if (info.event.url) {
                    info.jsEvent.preventDefault();
                    window.location.href = info.event.url;
                }
            },
            eventDrop: (info) => this.persistEventDates(info),
            eventResize: (info) => this.persistEventDates(info),
        });

        this.calendar.render();
        this.initialized = true;
    }

    async initCalendarWhenReady(force = false) {
        if (!this.isVisible()) {
            return;
        }

        if (typeof FullCalendar === "undefined") {
            const didLoad = await this.ensureFullCalendarLoaded();

            if (!didLoad) {
                if (this.retryCount >= 20 && !force) {
                    return;
                }

                this.retryCount += 1;
                this.initTimeout = window.setTimeout(() => {
                    this.initCalendarWhenReady(force);
                }, 150);

                return;
            }
        }

        this.initCalendar();
    }

    async ensureFullCalendarLoaded() {
        if (typeof FullCalendar !== "undefined") {
            return true;
        }

        if (!this.hasScriptUrlValue) {
            return false;
        }

        const existingScript = document.querySelector(
            `script[src="${this.scriptUrlValue}"]`
        );

        if (existingScript && typeof FullCalendar !== "undefined") {
            return true;
        }

        if (this.loadPromise) {
            await this.loadPromise;

            return typeof FullCalendar !== "undefined";
        }

        this.loadPromise = new Promise((resolve) => {
            const script = existingScript || document.createElement("script");

            if (!existingScript) {
                script.src = this.scriptUrlValue;
                script.async = true;
                document.body.appendChild(script);
            }

            const finalize = () => resolve(typeof FullCalendar !== "undefined");

            script.addEventListener("load", finalize, { once: true });
            script.addEventListener("error", () => resolve(false), {
                once: true,
            });

            if (existingScript && existingScript.dataset.loaded === "true") {
                finalize();
            }
        });

        const loaded = await this.loadPromise;

        const script = document.querySelector(`script[src="${this.scriptUrlValue}"]`);
        if (loaded && script) {
            script.dataset.loaded = "true";
        }

        return loaded;
    }

    formatLocalDateTime(date) {
        if (!(date instanceof Date)) {
            return null;
        }

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");
        const seconds = String(date.getSeconds()).padStart(2, "0");

        return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    }

    async persistEventDates(info) {
        const payload = {
            id: info.event.id,
            startsAt: this.formatLocalDateTime(info.event.start),
            endsAt: this.formatLocalDateTime(info.event.end),
            isAllDay: info.event.allDay,
        };

        try {
            const response = await fetch(this.updateUrlValue, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.csrfTokenValue,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Erreur");
            }

            if (data.event) {
                if (data.event.start) {
                    info.event.setStart(data.event.start);
                }

                if (data.event.end) {
                    info.event.setEnd(data.event.end);
                }
            }
        } catch (error) {
            info.revert();
            window.alert("Impossible de mettre à jour le rendez-vous.");
        }
    }

    isVisible() {
        return this.element.offsetParent !== null;
    }
}
