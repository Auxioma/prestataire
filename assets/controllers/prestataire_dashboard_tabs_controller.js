import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'backdrop', 'toggle'];

    connect() {
        this.onHashChange = this.activateTabsFromHash.bind(this);
        this.boundTabShownHandlers = new Map();
        this.bindTabs();
        this.activateTabsFromHash();
        window.addEventListener('hashchange', this.onHashChange);
    }

    disconnect() {
        window.removeEventListener('hashchange', this.onHashChange);

        this.boundTabShownHandlers?.forEach((handler, trigger) => {
            trigger.removeEventListener('shown.bs.tab', handler);
            delete trigger.dataset.tabBound;
        });

        this.boundTabShownHandlers?.clear();
    }

    bindTabs() {
        const triggers = this.element.querySelectorAll('[data-bs-toggle="tab"]');

        triggers.forEach((trigger) => {
            if (trigger.dataset.tabBound === 'true') {
                return;
            }

            trigger.dataset.tabBound = 'true';

            const handler = (event) => {
                const currentTrigger = event.target;
                const currentTarget = currentTrigger.getAttribute('data-bs-target');
                const tabLevel = currentTrigger.dataset.tabLevel;

                if (!currentTarget) {
                    return;
                }

                if (tabLevel === 'sub') {
                    const mainPane = currentTrigger.closest('.tab-pane');
                    const revenueSubtab = currentTrigger.dataset.revenueSubtab;

                    if (!mainPane || !mainPane.id) {
                        return;
                    }

                    if (mainPane.id === 'revenus-main-panel' && revenueSubtab) {
                        this.syncRevenueSubtabState(revenueSubtab);
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
            };

            this.boundTabShownHandlers.set(trigger, handler);
            trigger.addEventListener('shown.bs.tab', handler);
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

    syncRevenueSubtabState(subtab) {
        this.element.querySelectorAll('[data-revenue-subtab-input]').forEach((input) => {
            input.value = subtab;
        });

        this.element.querySelectorAll('[data-revenue-subtab-link]').forEach((link) => {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('revenues_subtab', subtab);
            link.href = `${url.pathname}${url.search}${url.hash}`;
        });

        this.element.querySelectorAll('[data-revenue-subtab-pagination] a').forEach((link) => {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('revenues_subtab', subtab);
            link.href = `${url.pathname}${url.search}${url.hash}`;
        });

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('revenues_subtab', subtab);
        history.replaceState(null, '', `${currentUrl.pathname}${currentUrl.search}#revenus-main-panel|revenus-${subtab}-panel`);
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
