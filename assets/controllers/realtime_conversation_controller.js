import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String,
        conversationId: Number,
        authToken: { type: String, default: "" },
        eventName: { type: String, default: "message_created" },
        joinEventName: { type: String, default: "join_conversation" },
        streamSelector: { type: String, default: ".tm-msg-stream" },
        bodySelector: { type: String, default: ".tm-msg-body" },
        emptySelector: { type: String, default: ".tm-msg-empty--body" },
        currentUserType: { type: String, default: "" },
        layout: { type: String, default: "dashboard" },
        scrollPageToSelf: { type: Boolean, default: false },
    };

    connect() {
        this.scrollTimeout = null;
        this.boundLocalMessageHandler = this.handleLocalMessage.bind(this);
        this.element.addEventListener(
            "conversation:message-created",
            this.boundLocalMessageHandler
        );

        if (typeof io === "undefined") {
            console.error("Socket.IO non chargé");
            return;
        }

        if (!this.hasConversationIdValue || !this.conversationIdValue) {
            return;
        }

        this.socket = io(this.urlValue || "http://localhost:3001", {
            transports: ["websocket", "polling"],
        });

        this.socket.on("connect", () => {
            this.socket.emit(this.joinEventNameValue, {
                conversationId: this.conversationIdValue,
                token: this.authTokenValue,
            });
        });

        this.socket.on("connect_error", (error) => {
            console.error("Erreur Socket.IO :", error.message);
        });

        this.socket.on(this.eventNameValue, (payload) => {
            this.handleMessage(payload);
        });

        requestAnimationFrame(() => {
            if (this.scrollPageToSelfValue) {
                this.element.scrollIntoView({
                    behavior: "auto",
                    block: "start",
                });
            }

            this.scrollStreamToBottom("auto");
        });
    }

    disconnect() {
        this.element.removeEventListener(
            "conversation:message-created",
            this.boundLocalMessageHandler
        );

        if (this.scrollTimeout) {
            clearTimeout(this.scrollTimeout);
            this.scrollTimeout = null;
        }

        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
        }
    }

    handleMessage(payload) {
        if (
            !payload ||
            Number(payload.conversationId) !== Number(this.conversationIdValue)
        ) {
            return;
        }

        this.appendMessage(payload.message || {});
    }

    handleLocalMessage(event) {
        this.appendMessage(event.detail?.message || {});
    }

    appendMessage(message) {
        if (!message || this.hasRenderedMessage(message.id)) {
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

        const shouldStickToBottom = this.isNearBottom(stream);
        const row = this.buildMessageElement(message);

        stream.appendChild(row);

        if (shouldStickToBottom) {
            this.scrollStreamToBottom("smooth");
        }
    }

    hasRenderedMessage(messageId) {
        if (!messageId) {
            return false;
        }

        return this.element.querySelector(`[data-message-id="${CSS.escape(String(messageId))}"]`) !== null;
    }

    scrollStreamToBottom(behavior = "smooth") {
        const stream = this.element.querySelector(this.streamSelectorValue);
        const container = this.resolveScrollContainer();

        if (!stream || !container) {
            return;
        }

        const scroll = () => {
            container.scrollTo({
                top: container.scrollHeight,
                behavior,
            });
        };

        requestAnimationFrame(() => {
            scroll();

            if (this.scrollTimeout) {
                clearTimeout(this.scrollTimeout);
            }

            this.scrollTimeout = window.setTimeout(() => {
                scroll();
                this.scrollTimeout = null;
            }, 60);
        });
    }

    isNearBottom(_stream, threshold = 120) {
        const container = this.resolveScrollContainer();

        if (!container) {
            return true;
        }

        return (
            container.scrollHeight - container.scrollTop - container.clientHeight <= threshold
        );
    }

    resolveScrollContainer() {
        if (this.layoutValue === "client") {
            return this.element.querySelector(this.streamSelectorValue);
        }

        return this.element.querySelector(this.bodySelectorValue);
    }

    buildStreamElement() {
        const stream = document.createElement("div");

        if (this.layoutValue === "client") {
            stream.className =
                "tm-client-quotes-messages tm-client-quotes-messages--scroll";
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
        const isSystem = (message.authorType ?? "") === "system";
        const attachmentsHtml = this.buildDashboardAttachmentsHtml(message.attachments ?? []);
        const authorLabel = this.escapeHtml(
            message.authorName ?? (isOwn ? "Moi" : "Interlocuteur")
        );
        const createdAt = this.escapeHtml(message.createdAt ?? "À l’instant");

        row.className = `tm-msg-row ${
            isSystem ? "is-system" : (isOwn ? "is-own" : "is-other")
        }`;
        this.setMessageIdentifier(row, message.id);

        if (isSystem) {
            row.innerHTML = `
                <div class="tm-msg-system">
                    ${message.content ? this.nl2br(message.content) : ""}
                </div>
            `;

            return row;
        }

        row.innerHTML = `
            <div class="tm-msg-bubble">
                <div class="tm-msg-meta">
                    ${authorLabel}
                    <span>·</span>
                    <span>${createdAt}</span>
                </div>
                ${message.content ? `<div class="tm-msg-content">${this.nl2br(message.content)}</div>` : ""}
                ${attachmentsHtml}
            </div>
        `;

        return row;
    }

    buildClientMessageElement(message) {
        const article = document.createElement("article");
        const isOwn = this.isOwnMessage(message);
        const authorType = message.authorType ?? "";
        const isSystem = authorType === "system";
        const attachmentsHtml = this.buildClientAttachmentsHtml(message.attachments ?? []);

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
        this.setMessageIdentifier(article, message.id);
        article.innerHTML = `
            <div class="tm-client-quotes-message__meta">
                <strong>${this.escapeHtml(authorLabel)}</strong>
                <span>${this.escapeHtml(message.createdAt ?? "À l’instant")}</span>
            </div>
            ${message.content ? `<div class="tm-client-quotes-message__content">${this.nl2br(message.content)}</div>` : ""}
            ${attachmentsHtml}
        `;

        return article;
    }

    setMessageIdentifier(element, messageId) {
        if (!messageId) {
            return;
        }

        element.dataset.messageId = String(messageId);
    }

    buildDashboardAttachmentsHtml(attachments) {
        if (!Array.isArray(attachments) || attachments.length === 0) {
            return "";
        }

        const items = attachments
            .filter((attachment) => attachment && attachment.url)
            .map((attachment) => {
                const url = this.escapeAttribute(attachment.url);
                const alt = this.escapeAttribute(attachment.originalName || "Photo jointe");

                return `
                    <a
                        href="${url}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tm-msg-attachment"
                    >
                        <img
                            src="${url}"
                            alt="${alt}"
                            loading="lazy"
                        >
                    </a>
                `;
            })
            .join("");

        if (!items) {
            return "";
        }

        return `<div class="tm-msg-attachments">${items}</div>`;
    }

    buildClientAttachmentsHtml(attachments) {
        if (!Array.isArray(attachments) || attachments.length === 0) {
            return "";
        }

        const items = attachments
            .filter((attachment) => attachment && attachment.url)
            .map((attachment) => {
                const url = this.escapeAttribute(attachment.url);
                const alt = this.escapeAttribute(attachment.originalName || "Photo jointe");

                return `
                    <a
                        href="${url}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tm-client-quotes-message__attachment"
                    >
                        <img
                            src="${url}"
                            alt="${alt}"
                            loading="lazy"
                        >
                    </a>
                `;
            })
            .join("");

        if (!items) {
            return "";
        }

        return `<div class="tm-client-quotes-message__attachments">${items}</div>`;
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

    escapeAttribute(value) {
        return this.escapeHtml(value).replace(/"/g, "&quot;");
    }

    nl2br(value) {
        return this.escapeHtml(value).replace(/\n/g, "<br>");
    }
}
