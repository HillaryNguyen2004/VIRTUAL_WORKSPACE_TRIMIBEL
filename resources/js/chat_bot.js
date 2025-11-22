$(function () {
    const $box = $("#chatbot-chatbox");
    const $btn = $("#chatbot-btn");

    const openBox = () => {
        $box.removeClass(
            "opacity-0 translate-y-4 scale-95 pointer-events-none invisible"
        ).addClass(
            "opacity-100 translate-y-0 scale-100 pointer-events-auto visible"
        );
        $btn.attr("aria-expanded", "true");
    }

    const closeBox = () => {
        $box.removeClass(
            "opacity-100 translate-y-0 scale-100 pointer-events-auto visible"
        ).addClass(
            "opacity-0 translate-y-4 scale-95 pointer-events-none invisible"
        );
        $btn.attr("aria-expanded", "false");
    }

    const toggleBox = () => {
        if ($box.hasClass("invisible")) openBox();
        else closeBox();
    }

    $btn.on("click", function (e) {
        e.preventDefault();
        toggleBox();
    });

    $(document).on("click", "#close-bot-btn", function (e) {
        e.preventDefault();
        closeBox();
    });
});
