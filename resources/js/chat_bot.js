$(function () {
    const $box = $("#chatbot-chatbox");
    const $btn = $("#chatbot-btn");
    const $messages = $("#chat-section");
    const $input = $("#chatbot-input");
    const $sendBtn = $("#chatbot-send-btn");

    let chatLang = window.CHAT_LANG || "en";
    let $typingBubble = null;

    const openBox = () => {
        $box.removeClass("hidden").addClass("flex");

        setTimeout(() => {
            $box.removeClass(
                "opacity-0 translate-y-4 scale-95 pointer-events-none invisible"
            ).addClass(
                "opacity-100 translate-y-0 scale-100 pointer-events-auto visible"
            );
        }, 50);

        $btn.attr("aria-expanded", "true");
    };

    const closeBox = () => {
        $box.removeClass(
            "opacity-100 translate-y-0 scale-100 pointer-events-auto visible"
        ).addClass(
            "opacity-0 translate-y-4 scale-95 pointer-events-none invisible"
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
            '<div class="max-w-96 shadow-lg rounded-2xl px-3 py-2 bg-[#5D3FD3] text-white"></div>'
        ).text(text);

        const $wrapper = $('<div class="flex justify-end"></div>').append(
            $bubble
        );

        $messages.append($wrapper);
        scrollMessages();
    };

    const appendBotMessage = (text) => {
        const $avatar = $(
            '<div class="flex items-center justify-center rounded-full p-2 border bg-white"></div>'
        ).append('<img src="/img/bot.png" alt="" class="w-6 h-6">');

        const $bubble = $(
            '<div class="max-w-96 shadow-lg rounded-2xl px-3 py-2 border border-gray-300 whitespace-pre-line"></div>'
        ).text(text);

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
            '<div class="flex items-center justify-center rounded-full p-2 border bg-white"></div>'
        ).append('<img src="/img/bot.png" alt="" class="w-6 h-6">');

        const $dots = $(
            '<div class="flex items-center justify-center gap-1 h-full w-full">' +
                '<span class="chat-typing-dot"></span>' +
                '<span class="chat-typing-dot"></span>' +
                '<span class="chat-typing-dot"></span>' +
                "</div>"
        );

        const $bubble = $(
            '<div class="max-w-96 shadow-lg rounded-2xl px-3 h-8 border border-gray-300"></div>'
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

    const sendMessage = () => {
        const message = $input.val().trim();
        if (!message) return;

        appendUserMessage(message);

        $input.val("").prop("disabled", true);
        $sendBtn.prop("disabled", true);

        // show typing skeleton
        showTypingBubble();

        axios
            .post("/api/chat-bot", {
                message: message,
                k: 5,
                lang: chatLang,
            })
            .then((res) => {
                const data = res.data;
                // remove skeleton
                hideTypingBubble();
                appendBotMessage(
                    data.answer || "I don't have an answer right now."
                );
            })
            .catch((error) => {
                console.error(error);
                // remove skeleton
                hideTypingBubble();
                appendBotMessage(
                    "Sorry, something went wrong. Please try again."
                );
            })
            .finally(() => {
                $input.prop("disabled", false).focus();
                $sendBtn.prop("disabled", false);
            });
    };

    $sendBtn.on("click", function (e) {
        e.preventDefault();
        sendMessage();
    });

    $input.on("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});
