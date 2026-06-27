import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String,
        conversationId: Number,
        eventName: { type: String, default: "message_created" },
        joinEventName: { type: String, default: "join_conversation" },
        streamSelector: { type: String, default: ".tm-msg-stream" },
        bodySelector: { type: String, default: ".tm-msg-body" },
        emptySelector: { type: String, default: ".tm-msg-empty--body" },
        currentUserType: { type: String, default: "" },
        layout: { type: String, default: "dashboard" }
    };

    connect() {
        if (typeof io === "undefined") {
            console.error("Socket.IO non chargé");
            return;
        }

        if (!this.hasConversationIdValue || !this.conversationIdValue) {
            return;
        }

        this.socket = io(this.urlValue || "http://localhost:3001", {
            transports: ["websocket", "polling"]
        });

        this.socket.on("connect", () => {
            this.socket.emit(this.joinEventNameValue, this.conversationIdValue);
        });

        this.socket.on("connect_error", (error) => {
            console.error("Erreur Socket.IO :", error.message);
        });

        this.socket.on(this.eventNameValue, (payload) => {
            this.handleMessage(payload);
        });
    }

    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
        }
    }

    handleMessage(payload) {
        if (!payload || Number(payload.conversationId) !== Number(this.conversationIdValue)) {
            return;
        }

        let stream = this.element.querySelector(this.streamSelectorValue);
        const emptyState = this.element.querySelector(this.emptySelectorValue);

        if (!stream) {
            if (emptyState) {
                emptyState.remove();
            }

            const body = this.element.querySelector(this.bodySelectorValue);
            if (!body) {
                return;
            }

            stream = this.buildStreamElement();
            body.appendChild(stream);
        }

        const message = payload.message || {};
        const row = this.buildMessageElement(message);

        stream.appendChild(row);
        stream.scrollTop = stream.scrollHeight;
    }

    buildStreamElement() {
        const stream = document.createElement("div");

        if (this.layoutValue === "client") {
            stream.className = "tm-client-quotes-messages tm-client-quotes-messages--scroll";
        } else {
            stream.className = "tm-msg-stream";
        }

        return stream;
    }

    buildMessageElement(message) {
        return this.layoutValue === "client"
            ? this.buildClientMessageElement(message)
            : this.buildDashboardMessageElement(message);
    }

    buildDashboardMessageElement(message) {
        const row = document.createElement("div");
        const isOwn = this.isOwnMessage(message);

        row.className = `tm-msg-row ${isOwn ? "is-own" : "is-other"}`;
        row.innerHTML = `
            <div class="tm-msg-bubble">
                <div class="tm-msg-meta">
                    ${this.escapeHtml(message.authorName ?? (isOwn ? "Moi" : "Interlocuteur"))}
                    <span>·</span>
                    <span>${this.escapeHtml(message.createdAt ?? "À l’instant")}</span>
                </div>
                <div class="tm-msg-content">${this.nl2br(message.content ?? "")}</div>
            </div>
        `;

        return row;
    }

    buildClientMessageElement(message) {
        const article = document.createElement("article");
        const isOwn = this.isOwnMessage(message);
        const authorType = message.authorType ?? "";
        const isSystem = authorType === "system";

        let variantClass = "tm-client-quotes-message--incoming";
        let authorLabel = message.authorName ?? "Prestataire";

        if (isSystem) {
            variantClass = "tm-client-quotes-message--system";
            authorLabel = "Système";
        } else if (isOwn) {
            variantClass = "tm-client-quotes-message--outgoing";
            authorLabel = "Vous";
        }

        article.className = `tm-client-quotes-message ${variantClass}`;
        article.innerHTML = `
            <div class="tm-client-quotes-message__meta">
                <strong>${this.escapeHtml(authorLabel)}</strong>
                <span>${this.escapeHtml(message.createdAt ?? "À l’instant")}</span>
            </div>
            <div class="tm-client-quotes-message__content">
                ${this.nl2br(message.content ?? "")}
            </div>
        `;

        return article;
    }

    isOwnMessage(message) {
        if (!this.currentUserTypeValue || !message.authorType) {
            return false;
        }

        return this.currentUserTypeValue === message.authorType;
    }

    escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value ?? "";
        return div.innerHTML;
    }

    nl2br(value) {
        return this.escapeHtml(value).replace(/\n/g, "<br>");
    }
}