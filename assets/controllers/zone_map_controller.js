import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        zones: Array,
    };

    connect() {
        this.handleMapConnect = this.handleMapConnect.bind(this);
        this.element.addEventListener('ux:map:connect', this.handleMapConnect);
    }

    disconnect() {
        this.element.removeEventListener('ux:map:connect', this.handleMapConnect);
    }

    handleMapConnect(event) {
        const { map, L } = event.detail;

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

        console.log('cercles ajoutés', this.zonesValue.length);
    }
}