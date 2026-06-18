import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['allDay', 'startsAt', 'endsAt'];

    connect() {
        console.log('appointment-form connected');
        this.toggleAllDay();
    }

    toggleAllDay() {
        const allDay = this.allDayTarget.checked;

        if (allDay) {
            if (!this.startsAtTarget.value) {
                const today = new Date().toISOString().slice(0, 10);
                this.startsAtTarget.value = `${today}T00:00`;
            } else {
                const startDate = this.startsAtTarget.value.slice(0, 10);
                this.startsAtTarget.value = `${startDate}T00:00`;
            }

            if (!this.endsAtTarget.value) {
                const endDate = this.startsAtTarget.value.slice(0, 10);
                this.endsAtTarget.value = `${endDate}T23:59`;
            } else {
                const endDate = this.endsAtTarget.value.slice(0, 10);
                this.endsAtTarget.value = `${endDate}T23:59`;
            }
        }
    }
}