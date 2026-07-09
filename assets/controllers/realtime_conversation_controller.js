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
        layout: { type: String, default: "dashboard" },
        scrollPageToSelf: { type: Boolean, default: false },
    };

    connect() {
        this.scrollTimeout = null;

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
            this.socket.emit(this.joinEventNameValue, this.conversationIdValue);
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
        const message = payload.message || {};
        const row = this.buildMessageElement(message);

        stream.appendChild(row);

        if (shouldStickToBottom) {
            this.scrollStreamToBottom("smooth");
        }
    }

    scrollStreamToBottom(behavior = "smooth") {
        const stream = this.element.querySelector(this.streamSelectorValue);

        if (!stream) {
            return;
        }

        const scroll = () => {
            const lastMessage = stream.lastElementChild;

            if (lastMessage) {
                lastMessage.scrollIntoView({
                    behavior,
                    block: "end",
                    inline: "nearest",
                });
            } else {
                stream.scrollTo({
                    top: stream.scrollHeight,
                    behavior,
                });
            }
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

    isNearBottom(stream, threshold = 120) {
        return (
            stream.scrollHeight - stream.scrollTop - stream.clientHeight <= threshold
        );
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
