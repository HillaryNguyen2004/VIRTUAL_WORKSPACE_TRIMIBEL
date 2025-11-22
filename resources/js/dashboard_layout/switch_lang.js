$(function () {
    const $btn = $("#langButton");
    const $menu = $("#langList");

    const $userList = $("#userList");
    const $userBtn = $("#userButton");
    const $notifPanel = $("#notificationPanel");
    const $notifBtn = $("#notificationBtn");

    function closeLang() {
        $menu.addClass("hidden");
        $btn.attr("aria-expanded", "false");
    }

    function closeIfOpen($panel, $button) {
        if ($panel.length && !$panel.hasClass("hidden")) {
            $panel.addClass("hidden");
            if ($button?.length) $button.attr("aria-expanded", "false");
        }
    }

    function toggleLang(e) {
        e?.stopPropagation();
        const isOpen = !$menu.toggleClass("hidden").hasClass("hidden");
        $btn.attr("aria-expanded", isOpen ? "true" : "false");

        // If lang menu just opened, hide the others
        if (isOpen) {
            closeIfOpen($userList, $userBtn);
            closeIfOpen($notifPanel, $notifBtn);
        }
    }

    // open/close
    $btn.on("click", toggleLang);

    // click outside -> close
    $(document).on("click", function (e) {
        if (
            !$menu.is(e.target) &&
            $menu.has(e.target).length === 0 &&
            !$btn.is(e.target) &&
            $btn.has(e.target).length === 0
        ) {
            closeLang();
        }
    });

    // ESC -> close
    $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
            closeLang();
            $btn.trigger("focus");
        }
    });
});
