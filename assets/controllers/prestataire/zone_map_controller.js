import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        zones: Array,
    };

    connect() {
        this.handleMapConnect = this.handleMapConnect.bind(this);
        this.handleTabShown = this.handleTabShown.bind(this);
        this.refreshTimeout = null;
        this.tabTriggers = Array.from(
            document.querySelectorAll('#settingsTabs [data-bs-toggle="tab"]')
        );

        this.element.addEventListener('ux:map:connect', this.handleMapConnect);

        this.tabTriggers.forEach((tabTrigger) => {
            tabTrigger.addEventListener('shown.bs.tab', this.handleTabShown);
        });
    }

    disconnect() {
        this.element.removeEventListener('ux:map:connect', this.handleMapConnect);

        this.tabTriggers.forEach((tabTrigger) => {
            tabTrigger.removeEventListener('shown.bs.tab', this.handleTabShown);
        });

        this.tabTriggers = [];

        if (this.refreshTimeout) {
            clearTimeout(this.refreshTimeout);
            this.refreshTimeout = null;
        }
    }

    handleMapConnect(event) {
        const { map, L } = event.detail;

        this.map = map;
        this.leaflet = L;

        this.zonesValue.forEach((zone) => {
            if (!zone.latitude || !zone.longitude || !zone.radiusKm) {
                return;
            }

            L.circle([parseFloat(zone.latitude), parseFloat(zone.longitude)], {
                radius: parseInt(zone.radiusKm, 10) * 1000,
                color: '#4f46e5',
                fillColor: '#4f46e5',
                fillOpacity: 0.12,
                weight: 2,
            }).addTo(map);
        });

        const activePane = document.querySelector('#zones-panel.active.show');
        if (activePane) {
            this.refreshMap();
        }
    }

    handleTabShown(event) {
        const target = event.target.getAttribute('data-bs-target');

        if (target === '#zones-panel') {
            this.refreshMap();
        }
    }

    refreshMap() {
        if (!this.map) {
            return;
        }

        if (this.refreshTimeout) {
            clearTimeout(this.refreshTimeout);
        }

        this.refreshTimeout = window.setTimeout(() => {
            this.map.invalidateSize();
            this.refreshTimeout = null;
        }, 150);
    }
}
