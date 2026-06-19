import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        eventsUrl: String,
        updateUrl: String,
        csrfToken: String,
    };

    connect() {
        this.initialized = false;
        this.handleTabShown = this.handleTabShown.bind(this);

        document.addEventListener("shown.bs.tab", this.handleTabShown);

        if (this.isVisible()) {
            this.initCalendar();
        }
    }

    disconnect() {
        document.removeEventListener("shown.bs.tab", this.handleTabShown);

        if (this.calendar) {
            this.calendar.destroy();
            this.calendar = null;
        }
    }

    handleTabShown(event) {
        const targetSelector = event.target.getAttribute("data-bs-target");

        if (targetSelector !== "#calendrier-main-panel") {
            return;
        }

        if (!this.initialized) {
            window.setTimeout(() => this.initCalendar(), 30);
            return;
        }

        window.setTimeout(() => this.calendar?.updateSize(), 60);
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
            initialView: "dayGridMonth",
            firstDay: 1,
            height: 650,
            nowIndicator: true,
            selectable: true,
            editable: true,
            dayMaxEvents: true,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek",
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

    async persistEventDates(info) {
        const payload = {
            id: info.event.id,
            startsAt: info.event.start ? info.event.start.toISOString() : null,
            endsAt: info.event.end ? info.event.end.toISOString() : null,
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
        } catch (error) {
            info.revert();
            window.alert("Impossible de mettre à jour le rendez-vous.");
        }
    }

    isVisible() {
        return this.element.offsetParent !== null;
    }
}