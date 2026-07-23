import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        userId: Number,
        authToken: { type: String, default: '' },
        joinEventName: { type: String, default: 'join_user' },
        eventName: { type: String, default: 'notification_created' },
        activeConversationId: { type: Number, default: 0 },
        activeTab: { type: String, default: '' },
    };

    connect() {
        this.sidebarBadge = document.querySelector('[data-unread-conversations-badge]');

        if (typeof io === 'undefined') {
            console.error('Socket.IO non charge');
            return;
        }

        if (!this.hasUserIdValue || !this.userIdValue) {
            return;
        }

        this.socket = io(this.urlValue || 'http://localhost:3001', {
            transports: ['websocket', 'polling'],
        });

        this.socket.on('connect', () => {
            this.socket.emit(this.joinEventNameValue, {
                userId: this.userIdValue,
                token: this.authTokenValue,
            });
        });

        this.socket.on('connect_error', (error) => {
            console.error('Erreur Socket.IO messagerie prestataire :', error.message);
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

        const conversationId = this.extractConversationId(payload.notification.targetUrl);

        if (!conversationId) {
            return;
        }

        if (
            this.activeTabValue === 'messages'
            && Number(conversationId) === Number(this.activeConversationIdValue)
        ) {
            return;
        }

        const thread = this.element.querySelector(`[data-conversation-id="${conversationId}"]`);

        if (!thread) {
            this.incrementSidebarBadge();
            return;
        }

        const currentUnreadCount = Number.parseInt(thread.dataset.unreadCount || '0', 10);

        if (Number.isNaN(currentUnreadCount) || currentUnreadCount === 0) {
            this.incrementSidebarBadge();
        }

        this.promoteThread(thread);
        this.bumpUnreadBadge(thread);
    }

    markThreadRead(event) {
        const thread = event.currentTarget;
        const currentUnreadCount = Number.parseInt(thread.dataset.unreadCount || '0', 10);

        if (Number.isNaN(currentUnreadCount) || currentUnreadCount <= 0) {
            return;
        }

        thread.dataset.unreadCount = '0';
        thread.classList.remove('is-unread', 'is-unread-live');

        const badge = thread.querySelector('[data-unread-badge]');
        if (badge) {
            badge.remove();
        }

        this.decrementSidebarBadge();
    }

    extractConversationId(targetUrl) {
        if (!targetUrl) {
            return null;
        }

        try {
            const url = new URL(targetUrl, window.location.origin);

            if (url.searchParams.get('tab') !== 'messages') {
                return null;
            }

            return url.searchParams.get('conversation');
        } catch (_error) {
            return null;
        }
    }

    promoteThread(thread) {
        const list = this.element.querySelector('.tm-msg-list');

        if (!list) {
            return;
        }

        thread.classList.add('is-unread', 'is-unread-live');
        list.prepend(thread);

        window.setTimeout(() => {
            thread.classList.remove('is-unread-live');
        }, 1800);
    }

    bumpUnreadBadge(thread) {
        const currentCount = Number.parseInt(thread.dataset.unreadCount || '0', 10);
        const nextCount = Number.isNaN(currentCount) ? 1 : currentCount + 1;

        thread.dataset.unreadCount = String(nextCount);

        const side = thread.querySelector('.tm-msg-thread-side');

        if (!side) {
            return;
        }

        let badge = thread.querySelector('[data-unread-badge]');

        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'tm-msg-thread-unread';
            badge.setAttribute('data-unread-badge', '');
            side.prepend(badge);
        }

        badge.setAttribute(
            'aria-label',
            `${nextCount} message${nextCount > 1 ? 's' : ''} non lu${nextCount > 1 ? 's' : ''}`
        );
        badge.innerHTML = '<i class="fa-solid fa-envelope"></i><span>' + nextCount + '</span>';
    }

    incrementSidebarBadge() {
        this.renderSidebarBadge(this.readSidebarBadgeCount() + 1);
    }

    decrementSidebarBadge() {
        this.renderSidebarBadge(Math.max(0, this.readSidebarBadgeCount() - 1));
    }

    readSidebarBadgeCount() {
        if (!this.sidebarBadge) {
            return 0;
        }

        const raw = this.sidebarBadge.dataset.countValue || this.sidebarBadge.textContent || '0';
        const parsed = Number.parseInt(raw, 10);

        return Number.isNaN(parsed) ? 0 : parsed;
    }

    renderSidebarBadge(count) {
        if (!this.sidebarBadge) {
            return;
        }

        this.sidebarBadge.dataset.countValue = String(count);
        this.sidebarBadge.textContent = String(count);
        this.sidebarBadge.classList.toggle('d-none', count <= 0);
    }
}
