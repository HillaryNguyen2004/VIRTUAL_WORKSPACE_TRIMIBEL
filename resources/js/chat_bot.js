import { marked } from "marked";

$(function () {
    const $box = $("#chatbot-chatbox");
    const $btn = $("#chatbot-btn");
    const $messages = $("#chat-section");
    const $input = $("#chatbot-input");
    const $sendBtn = $("#chatbot-send-btn");
    const $workspaceSelect = $("#chatbot-workspace-select");
    const $suggestedPrompts = $("#chatbot-suggested-prompts");

    let chatLang = window.CHAT_LANG || "en";
    let userId = window.AUTH_USER_ID | "";
    let userRole = (window.AUTH_USER_ROLE || "user").toLowerCase();
    const workspaceMatch = window.location.pathname.match(
        /\/ai-workspaces\/([^/]+)/,
    );
    const currentWorkspaceId =
        window.AI_WORKSPACE_ID || (workspaceMatch ? workspaceMatch[1] : "");
    let $typingBubble = null;
    let $streamingBubble = null;
    let isRequestInFlight = false;
    let activeController = null;
    let activeRequestId = null;
    const sendBtnDefaultHtml = $sendBtn.html();

    const stopBtnHtml =
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 block">' +
        '<rect x="176" y="176" width="288" height="288" rx="28"/>' +
        "</svg>";

    const setSendButtonState = (isStopState) => {
        if (isStopState) {
            $sendBtn
                .html(stopBtnHtml)
                .removeClass("bg-[#5D3FD3]")
                .addClass("bg-[#DC2626]")
                .attr("title", "Stop");
        } else {
            $sendBtn
                .html(sendBtnDefaultHtml)
                .removeClass("bg-[#DC2626]")
                .addClass("bg-[#5D3FD3]")
                .attr("title", "Send");
        }
    };

    const getSelectedWorkspaceId = () => {
        const selected = ($workspaceSelect.val() || "").toString().trim();
        if (selected) return selected;
        if (currentWorkspaceId) return currentWorkspaceId;
        return "global";
    };

    const openBox = () => {
        $box.removeClass("hidden").addClass("flex");

        setTimeout(() => {
            $box.removeClass(
                "opacity-0 translate-y-4 scale-95 pointer-events-none invisible",
            ).addClass(
                "opacity-100 translate-y-0 scale-100 pointer-events-auto visible",
            );
        }, 50);

        $btn.attr("aria-expanded", "true");
    };

    const closeBox = () => {
        $box.removeClass(
            "opacity-100 translate-y-0 scale-100 pointer-events-auto visible",
        ).addClass(
            "opacity-0 translate-y-4 scale-95 pointer-events-none invisible",
        );

        setTimeout(() => {
            $box.removeClass("flex").addClass("hidden");
        }, 100);

        $btn.attr("aria-expanded", "false");
    };

    const toggleBox = () => {
        if ($box.hasClass("invisible")) openBox();
        else closeBox();
    };

    $btn.on("click", function (e) {
        e.preventDefault();
        toggleBox();
    });

    $(document).on("click", "#close-bot-btn", function (e) {
        e.preventDefault();
        closeBox();
    });

    const scrollMessages = () => {
        const el = $messages[0];
        el.scrollTop = el.scrollHeight;
    };

    const appendUserMessage = (text) => {
        const $bubble = $(
            '<div class="max-w-[280px] shadow-lg rounded-2xl px-3 py-2 bg-[#5D3FD3] text-white text-sm whitespace-pre-line"></div>',
        ).text(text);

        const $wrapper = $('<div class="flex justify-end"></div>').append(
            $bubble,
        );

        $messages.append($wrapper);
        scrollMessages();
    };

    const isListLikeLine = (line) => {
        const t = String(line || "").trim();
        return /^(?:[-*+•]\s+|\d+\.\s+)/.test(t);
    };

    const isSectionHeaderLine = (line) => {
        const t = String(line || "").trim();
        if (!t || isListLikeLine(t)) return false;
        // Heuristic: label-like header such as "Good employees:".
        return /:\s*$/.test(t);
    };

    const isStandaloneSentenceLine = (line) => {
        const t = String(line || "").trim();
        if (!t || isListLikeLine(t)) return false;
        // Likely a new top-level paragraph, e.g. "This information...".
        return /^[A-Z][^\n]*[.!?)]$/.test(t);
    };

    const compactMarkdownSpacing = (text) => {
        const lines = String(text || "")
            .replace(/\r\n/g, "\n")
            .split("\n");
        const out = [];

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            const trimmed = line.trim();
            const prev = out.length ? out[out.length - 1] : "";

            // If a header-like line follows a list item, force a paragraph break so it
            // renders at root level instead of being nested under the previous item.
            if (isSectionHeaderLine(trimmed) && isListLikeLine(prev)) {
                if (prev.trim() !== "") {
                    out.push("");
                }
                line = trimmed;
            }

            // If a normal sentence follows a list item, force it to become a
            // top-level paragraph rather than a lazy list continuation.
            if (isStandaloneSentenceLine(trimmed) && isListLikeLine(prev)) {
                if (prev.trim() !== "") {
                    out.push("");
                }
                line = trimmed;
            }

            if (trimmed === "") {
                // Keep at most one blank line in a row.
                if (prev.trim() === "") continue;

                // If blank line is between list-like lines, remove it.
                let j = i + 1;
                while (j < lines.length && lines[j].trim() === "") j++;
                const nextNonEmpty = j < lines.length ? lines[j] : "";
                if (isListLikeLine(prev) || isListLikeLine(nextNonEmpty)) {
                    continue;
                }
            }

            out.push(line);
        }

        return out.join("\n").trim();
    };

    const renderMarkdown = (md) => {
        const normalized = compactMarkdownSpacing(md);
        const rawHtml = marked.parse(normalized, { breaks: true }); // breaks: keep line breaks
        return DOMPurify.sanitize(rawHtml); // sanitize to prevent XSS
    };

    const appendBotMessage = (text) => {
        const $avatar = $(
            '<div class="flex items-center justify-center rounded-full p-2 border bg-white"></div>',
        ).append('<img src="/img/bot.png" alt="" class="w-6 h-6">');

        const safeHtml = renderMarkdown(text);

        const $bubble = $(
            '<div class="chatbot-markdown max-w-[280px] shadow-lg rounded-2xl px-3 py-2 border border-gray-300 bg-gray-50 text-sm break-words"></div>',
        ).html(safeHtml);

        const $wrapper = $('<div class="flex items-end gap-2"></div>')
            .append($avatar)
            .append($bubble);

        $messages.append($wrapper);
        scrollMessages();
    };

    // typing / skeleton bubble
    const showTypingBubble = () => {
        // if already showing, do nothing
        if ($typingBubble) return;

        const $avatar = $(
            '<div class="flex items-center justify-center rounded-full p-2 border bg-white"></div>',
        ).append('<img src="/img/bot.png" alt="" class="w-6 h-6">');

        const $dots = $(
            '<div class="flex items-center justify-center gap-1 h-full w-full">' +
                '<span class="chat-typing-dot"></span>' +
                '<span class="chat-typing-dot"></span>' +
                '<span class="chat-typing-dot"></span>' +
                "</div>",
        );

        const $bubble = $(
            '<div class="max-w-[280px] shadow-lg rounded-2xl px-3 h-8 border border-gray-300 bg-gray-50"></div>',
        ).append($dots);

        const $wrapper = $('<div class="flex items-center gap-2"></div>')
            .append($avatar)
            .append($bubble);

        $messages.append($wrapper);
        $typingBubble = $wrapper; // store reference
        scrollMessages();
    };

    const hideTypingBubble = () => {
        if ($typingBubble) {
            $typingBubble.remove();
            $typingBubble = null;
        }
    };

    const hideSuggestedPrompts = () => {
        if ($suggestedPrompts.length) {
            $suggestedPrompts.remove();
        }
    };

    const generateRequestId = () => {
        if (window.crypto && typeof window.crypto.randomUUID === "function") {
            return window.crypto.randomUUID();
        }

        return "req-" + Date.now() + "-" + Math.random().toString(16).slice(2);
    };

    const sendStopSignal = () => {
        if (!activeRequestId) return;

        axios
            .post("/api/chat-bot/stop", {
                request_id: activeRequestId,
            })
            .catch((error) => {
                console.warn("Failed to send stop signal", error);
            });
    };

    const CITATION_SENTINEL = "\n__CITATIONS__:";

    // Split accumulated stream text into {text, citations}.
    // The sentinel line is never shown to the user.
    const parseSentinel = (raw) => {
        const idx = raw.indexOf(CITATION_SENTINEL);
        if (idx === -1) return { text: raw, citations: [] };
        const text = raw.slice(0, idx);
        try {
            const citations = JSON.parse(raw.slice(idx + CITATION_SENTINEL.length));
            return { text, citations: Array.isArray(citations) ? citations : [] };
        } catch (_) {
            return { text, citations: [] };
        }
    };

    const renderCitationCards = (citations) => {
        if (!citations || citations.length === 0) return null;
        const $list = $('<div class="mt-2 flex flex-col gap-1"></div>');
        citations.forEach((c) => {
            const label = c.source || c.id || "source";
            const loc   = c.location ? ` · ${c.location}` : "";
            $list.append(
                $(`<div class="flex items-center gap-1.5 text-[11px] text-muted-500 bg-white border border-muted-200 rounded-lg px-2 py-1">
                    <svg class="w-3 h-3 shrink-0 text-primary/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="truncate font-medium">[${c.rank}] ${label}</span><span class="text-muted-400 shrink-0">${loc}</span>
                </div>`)
            );
        });
        return $list;
    };

    const createStreamingBubble = () => {
        const $avatar = $(
            '<div class="flex items-center justify-center rounded-full p-2 border bg-white"></div>',
        ).append('<img src="/img/bot.png" alt="" class="w-6 h-6">');

        const $bubble = $(
            '<div class="chatbot-markdown max-w-[280px] shadow-lg rounded-2xl px-3 py-2 border border-gray-300 bg-gray-50 text-sm break-words"></div>',
        );

        const $wrapper = $('<div class="flex items-end gap-2"></div>')
            .append($avatar)
            .append($bubble);

        $messages.append($wrapper);
        scrollMessages();

        return $bubble;
    };

    const updateStreamingBubble = (rawAccumulated) => {
        if (!$streamingBubble) {
            $streamingBubble = createStreamingBubble();
        }

        const { text } = parseSentinel(rawAccumulated);
        $streamingBubble.html(renderMarkdown(text));
        scrollMessages();
    };

    const finalizeBubbleWithCitations = (rawAccumulated) => {
        const { text, citations } = parseSentinel(rawAccumulated);
        if ($streamingBubble) {
            $streamingBubble.html(renderMarkdown(text));
            if (citations.length > 0) {
                const $cards = renderCitationCards(citations);
                $streamingBubble.closest(".flex").append(
                    $('<div class="max-w-[280px] ml-10"></div>').append($cards)
                );
            }
        }
    };

    const sendMessage = async (forcedMessage = null) => {
        if (isRequestInFlight) return;

        const message =
            forcedMessage !== null
                ? String(forcedMessage).trim()
                : $input.val().trim();
        if (!message) return;

        hideSuggestedPrompts();
        appendUserMessage(message);

        $input.val("").prop("disabled", true);
        isRequestInFlight = true;
        setSendButtonState(true);
        activeController = new AbortController();
        activeRequestId = generateRequestId();
        $streamingBubble = null;

        showTypingBubble();

        const payload = {
            message: message,
            k: 5,
            lang: chatLang,
            user_id: userId.toString(),
            user_role: userRole,
            workspace_id: getSelectedWorkspaceId(),
            request_id: activeRequestId,
        };

        try {
            const response = await fetch("/api/chat-bot/stream", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "text/plain",
                },
                body: JSON.stringify(payload),
                signal: activeController.signal,
            });

            if (!response.ok || !response.body) {
                throw new Error(`Stream request failed: ${response.status}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder("utf-8");
            let accumulated = "";
            let firstChunk = true;

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value, { stream: true });
                if (!chunk) continue;

                accumulated += chunk;

                if (firstChunk) {
                    hideTypingBubble();
                    firstChunk = false;
                }

                updateStreamingBubble(accumulated);
            }

            hideTypingBubble();

            const { text: finalText } = parseSentinel(accumulated);
            if (!finalText.trim()) {
                appendBotMessage("I don't have an answer right now.");
            } else {
                finalizeBubbleWithCitations(accumulated);
            }
        } catch (error) {
            console.error(error);
            hideTypingBubble();

            if (
                error?.name === "AbortError" ||
                error?.code === "ERR_CANCELED" ||
                error?.name === "CanceledError"
            ) {
                appendBotMessage("Stopped.");
                return;
            }

            appendBotMessage("Sorry, something went wrong. Please try again.");
        } finally {
            $input.prop("disabled", false).focus();
            isRequestInFlight = false;
            activeController = null;
            activeRequestId = null;
            $streamingBubble = null;
            setSendButtonState(false);
        }
    };

    $sendBtn.on("click", function (e) {
        e.preventDefault();

        if (isRequestInFlight && activeController) {
            sendStopSignal();
            activeController.abort();
            return;
        }

        sendMessage();
    });

    $input.on("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    $(document).on("click", ".chatbot-prompt-btn", function (e) {
        e.preventDefault();
        const message = ($(this).data("message") || "").toString();
        sendMessage(message);
    });
});
