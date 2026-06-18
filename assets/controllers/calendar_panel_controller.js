import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        eventsUrl: String,
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
            editable: false,
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
        });

        this.calendar.render();
        this.initialized = true;
    }

    isVisible() {
        return this.element.offsetParent !== null;
    }
}
