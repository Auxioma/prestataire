import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'backdrop', 'toggle'];

    connect() {
        this.onHashChange = this.activateTabsFromHash.bind(this);
        this.bindTabs();
        this.activateTabsFromHash();
        window.addEventListener('hashchange', this.onHashChange);
    }

    disconnect() {
        window.removeEventListener('hashchange', this.onHashChange);
    }

    bindTabs() {
        const triggers = this.element.querySelectorAll('[data-bs-toggle="tab"]');

        triggers.forEach((trigger) => {
            if (trigger.dataset.tabBound === 'true') {
                return;
            }

            trigger.dataset.tabBound = 'true';

            trigger.addEventListener('shown.bs.tab', (event) => {
                const currentTrigger = event.target;
                const currentTarget = currentTrigger.getAttribute('data-bs-target');
                const tabLevel = currentTrigger.dataset.tabLevel;

                if (!currentTarget) {
                    return;
                }

                if (tabLevel === 'sub') {
                    const mainPane = currentTrigger.closest('.tab-pane');

                    if (!mainPane || !mainPane.id) {
                        return;
                    }

                    history.replaceState(null, '', `#${mainPane.id}|${currentTarget.replace('#', '')}`);
                    return;
                }

                if (tabLevel === 'main') {
                    const firstSubTrigger = this.findFirstSubTabTrigger(currentTarget);

                    if (firstSubTrigger) {
                        const firstSubTarget = firstSubTrigger.getAttribute('data-bs-target');
                        history.replaceState(null, '', `${currentTarget}|${firstSubTarget.replace('#', '')}`);
                    } else {
                        history.replaceState(null, '', currentTarget);
                    }

                    this.closeSidebar();
                }
            });
        });
    }

    activateTabsFromHash() {
        const hash = window.location.hash;

        if (!hash || !window.bootstrap?.Tab) {
            return;
        }

        const cleanedHash = hash.replace(/^#/, '');
        const [mainPaneId, subPaneId] = cleanedHash.split('|');

        if (!mainPaneId) {
            return;
        }

        const mainTrigger = this.element.querySelector(
            `[data-bs-target="#${mainPaneId}"][data-tab-level="main"]`
        );

        if (!mainTrigger) {
            return;
        }

        window.bootstrap.Tab.getOrCreateInstance(mainTrigger).show();

        if (subPaneId) {
            const subTrigger = this.element.querySelector(
                `[data-bs-target="#${subPaneId}"][data-tab-level="sub"]`
            );

            if (subTrigger) {
                window.setTimeout(() => {
                    window.bootstrap.Tab.getOrCreateInstance(subTrigger).show();
                }, 0);
            }

            return;
        }

        const firstSubTrigger = this.findFirstSubTabTrigger(`#${mainPaneId}`);

        if (firstSubTrigger) {
            window.setTimeout(() => {
                window.bootstrap.Tab.getOrCreateInstance(firstSubTrigger).show();
            }, 0);
        }
    }

    findFirstSubTabTrigger(mainTargetSelector) {
        const mainPane = this.element.querySelector(mainTargetSelector);

        if (!mainPane) {
            return null;
        }

        return mainPane.querySelector('[data-bs-toggle="tab"][data-tab-level="sub"]');
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