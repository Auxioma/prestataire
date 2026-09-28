import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'backdrop', 'toggle'];

    connect() {
        this.boundTabShownHandlers = new Map();
        this.bindTabs();
        this.activateTabFromHash();
        this.onHashChange = this.activateTabFromHash.bind(this);
        window.addEventListener('hashchange', this.onHashChange);
        this.onKeydown = (event) => {
            if (event.key === 'Escape' && this.hasSidebarTarget && this.sidebarTarget.classList.contains('is-open')) {
                this.setSidebarOpen(false);
                if (this.hasToggleTarget) {
                    this.toggleTarget.focus();
                }
            }
        };
        this.element.addEventListener('keydown', this.onKeydown);
        this.mobileSidebarMedia = window.matchMedia('(max-width: 991.98px)');
        this.onViewportChange = () => this.updateSidebarInert();
        this.mobileSidebarMedia.addEventListener('change', this.onViewportChange);
        this.updateSidebarInert();
    }

    disconnect() {
        window.removeEventListener('hashchange', this.onHashChange);
        this.element.removeEventListener('keydown', this.onKeydown);
        this.mobileSidebarMedia.removeEventListener('change', this.onViewportChange);
        if (this.hasSidebarTarget) {
            this.sidebarTarget.inert = false;
        }

        this.boundTabShownHandlers?.forEach((handler, trigger) => {
            trigger.removeEventListener('shown.bs.tab', handler);
            delete trigger.dataset.tabBound;
        });

        this.boundTabShownHandlers?.clear();
    }

    bindTabs() {
        const triggers = this.element.querySelectorAll('#settingsTabs [data-bs-toggle="tab"]');

        triggers.forEach((trigger) => {
            if (trigger.dataset.tabBound === 'true') return;

            trigger.dataset.tabBound = 'true';

            const handler = (event) => {
                const target = event.target.getAttribute('data-bs-target');
                if (target) {
                    history.replaceState(null, '', target);
                }
            };

            this.boundTabShownHandlers.set(trigger, handler);
            trigger.addEventListener('shown.bs.tab', handler);
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
        const isOpen = this.hasSidebarTarget && !this.sidebarTarget.classList.contains('is-open');
        this.setSidebarOpen(isOpen);
    }

    closeSidebar() {
        this.setSidebarOpen(false);
    }

    setSidebarOpen(isOpen) {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.classList.toggle('is-open', isOpen);
        }

        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.toggle('is-visible', isOpen);
        }

        if (this.hasToggleTarget) {
            this.toggleTarget.setAttribute('aria-expanded', String(isOpen));
            this.toggleTarget.setAttribute('aria-label', `${isOpen ? 'Fermer' : 'Ouvrir'} la navigation des paramètres`);
        }

        this.updateSidebarInert();
    }

    updateSidebarInert() {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.inert = this.mobileSidebarMedia.matches && !this.sidebarTarget.classList.contains('is-open');
        }
    }
}
