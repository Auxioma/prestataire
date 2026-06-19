import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['allDay', 'startsAt', 'endsAt'];

    connect() {
        if (this.hasAllDayTarget) {
            this.toggleAllDay();
        }
    }

    toggleAllDay() {
        if (!this.hasAllDayTarget || !this.hasStartsAtTarget || !this.hasEndsAtTarget) {
            return;
        }

        const allDay = this.allDayTarget.checked;

        if (!allDay) {
            return;
        }

        const startDate = this.startsAtTarget.value?.slice(0, 10);
        const endDate = this.endsAtTarget.value?.slice(0, 10) || startDate;

        if (startDate) this.startsAtTarget.value = `${startDate}T00:00`;
        if (endDate) this.endsAtTarget.value = `${endDate}T23:59`;
    }
}