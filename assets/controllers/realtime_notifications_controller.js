import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['badge', 'countText', 'list', 'empty'];

    static values = {
        url: String,
        userId: Number,
        joinEventName: { type: String, default: 'join_user' },
        eventName: { type: String, default: 'notification_created' },
        maxItems: { type: Number, default: 5 },
        markReadUrlPattern: String,
    };

    connect() {
        if (typeof io === 'undefined') {
            console.error('Socket.IO non chargé');
            return;
        }

        if (!this.hasUserIdValue || !this.userIdValue) {
            return;
        }

        this.socket = io(this.urlValue || 'http://localhost:3001', {
            transports: ['websocket', 'polling'],
        });

        this.socket.on('connect', () => {
            this.socket.emit(this.joinEventNameValue, this.userIdValue);
        });

        this.socket.on('connect_error', (error) => {
            console.error('Erreur Socket.IO notifications :', error.message);
        });

        this.socket.on(this.eventNameValue, (payload) => {
            this.handleNotification(payload);
        });
    }

    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
        }
    }

    handleNotification(payload) {
        if (
            !payload ||
            Number(payload.userId) !== Number(this.userIdValue) ||
            !payload.notification
        ) {
            return;
        }

        const notification = payload.notification;

        this.hideEmptyState();
        this.prependNotification(notification);
        this.trimList();
        this.incrementUnreadCount();

        if (notification.title || notification.body) {
            this.dispatchToastEvent(notification);
        }
    }

    hideEmptyState() {
        if (!this.hasEmptyTarget) {
            return;
        }

        this.emptyTarget.classList.add('d-none');
    }

    prependNotification(notification) {
        if (!this.hasListTarget) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = this.buildNotificationItem(notification);

        const node = wrapper.firstElementChild;

        if (!node) {
            return;
        }

        this.listTarget.prepend(node);
    }

    trimList() {
        if (!this.hasListTarget) {
            return;
        }

        const items = this.listTarget.querySelectorAll('[data-notification-item]');

        if (items.length <= this.maxItemsValue) {
            return;
        }

        items.forEach((item, index) => {
            if (index >= this.maxItemsValue) {
                item.remove();
            }
        });
    }

    incrementUnreadCount() {
        const currentCount = this.readCount();
        const nextCount = currentCount + 1;

        this.renderBadge(nextCount);
        this.renderCountText(nextCount);
    }

    readCount() {
        if (this.hasBadgeTarget) {
            const raw = this.badgeTarget.dataset.countValue || this.badgeTarget.textContent || '0';
            const normalized = raw.trim();

            if (normalized === '99+') {
                return 99;
            }

            const parsed = parseInt(normalized, 10);

            if (!Number.isNaN(parsed)) {
                return parsed;
            }
        }

        if (this.hasCountTextTarget) {
            const raw = this.countTextTarget.dataset.countValue || this.countTextTarget.textContent || '0';
            const parsed = parseInt(raw, 10);

            if (!Number.isNaN(parsed)) {
                return parsed;
            }
        }

        return 0;
    }

    renderBadge(count) {
        if (!this.hasBadgeTarget) {
            return;
        }

        this.badgeTarget.dataset.countValue = String(count);
        this.badgeTarget.innerHTML = `
            ${count > 99 ? '99+' : count}
            <span class="visually-hidden">notifications non lues</span>
        `;
        this.badgeTarget.classList.toggle('d-none', count <= 0);
    }

    renderCountText(count) {
        if (!this.hasCountTextTarget) {
            return;
        }

        this.countTextTarget.dataset.countValue = String(count);
        this.countTextTarget.textContent = `${count} non lue${count > 1 ? 's' : ''}`;
    }

    buildNotificationItem(notification) {
        const title = this.escapeHtml(notification.title ?? '');
        const body = this.escapeHtml(this.truncate(notification.body ?? '', 100));
        const createdAt = this.escapeHtml(notification.createdAt ?? 'À l’instant');
        const readUrl = this.escapeAttribute(this.resolveReadUrl(notification));
        const csrfToken = this.escapeAttribute(notification.csrfToken ?? '');
        const targetUrl = this.escapeAttribute(notification.targetUrl ?? '');

        return `
            <form method="post" action="${readUrl}" data-notification-item>
                <input type="hidden" name="_token" value="${csrfToken}">
                ${targetUrl ? `<input type="hidden" name="_target_url" value="${targetUrl}">` : ''}

                <button
                    type="submit"
                    class="dropdown-item tm-navbardropdown-link tm-navbar-notification-item px-3 py-3 border-bottom w-100 text-start tm-navbar-notification-item-unread"
                >
                    <div class="d-flex align-items-start gap-3">
                        <div class="mt-1">
                            <i class="bi bi-bell-fill text-primary"></i>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span class="tm-navbar-notification-title">
                                    ${title || 'Nouvelle notification'}
                                </span>
                                <span class="badge rounded-pill text-bg-primary">Nouveau</span>
                            </div>

                            <div class="tm-navbar-notification-content mt-1">
                                ${body}
                            </div>

                            <div class="tm-navbar-notification-date mt-2">
                                ${createdAt}
                            </div>
                        </div>
                    </div>
                </button>
            </form>
        `;
    }

    resolveReadUrl(notification) {
        if (notification.readUrl) {
            return notification.readUrl;
        }

        if (this.hasMarkReadUrlPatternValue) {
            return this.markReadUrlPatternValue.replace('__ID__', String(notification.id ?? ''));
        }

        return '#';
    }

    truncate(value, maxLength) {
        const normalized = String(value ?? '').trim();

        if (normalized.length <= maxLength) {
            return normalized;
        }

        return `${normalized.slice(0, maxLength)}…`;
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    escapeAttribute(value) {
        return this.escapeHtml(value);
    }

    dispatchToastEvent(notification) {
        window.dispatchEvent(new CustomEvent('app:notification-received', {
            detail: {
                title: notification.title ?? 'Nouvelle notification',
                body: notification.body ?? '',
                notification,
            },
        }));
    }
}