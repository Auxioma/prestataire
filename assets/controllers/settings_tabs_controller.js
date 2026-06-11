import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'backdrop', 'toggle'];

    connect() {
        this.bindTabs();
        this.activateTabFromHash();
        this.onHashChange = this.activateTabFromHash.bind(this);
        window.addEventListener('hashchange', this.onHashChange);
    }

    disconnect() {
        window.removeEventListener('hashchange', this.onHashChange);
    }

    bindTabs() {
        const triggers = this.element.querySelectorAll('#settingsTabs [data-bs-toggle="tab"]');

        triggers.forEach((trigger) => {
            if (trigger.dataset.tabBound === 'true') return;

            trigger.dataset.tabBound = 'true';

            trigger.addEventListener('shown.bs.tab', (event) => {
                const target = event.target.getAttribute('data-bs-target');
                if (target) {
                    history.replaceState(null, '', target);
                }
            });
        });
    }

    activateTabFromHash() {
        const hash = window.location.hash;
        if (!hash) return;

        const trigger = this.element.querySelector(`#settingsTabs [data-bs-target="${hash}"]`);
        if (trigger && window.bootstrap?.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    }

    toggleSidebar() {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.classList.toggle('is-open');
        }

        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.toggle('is-visible');
        }
    }

    closeSidebar() {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.classList.remove('is-open');
        }

        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.remove('is-visible');
        }
    }
}