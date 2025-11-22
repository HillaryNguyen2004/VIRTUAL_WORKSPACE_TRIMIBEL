$(function () {
    const $wrap = $("#userMenu");
    const $btn = $("#userButton");
    const $menu = $("#userList");

    const $notifPanel = $("#notificationPanel");
    const $notifBtn = $("#notificationBtn");
    const $langList = $("#langList");
    const $langBtn = $("#langButton");

    function closeMenu() {
        $menu.addClass("hidden");
        $btn.attr("aria-expanded", "false");
    }

    function closeIfOpen($panel, $button) {
        if ($panel.length && !$panel.hasClass("hidden")) {
            $panel.addClass("hidden");
            if ($button?.length) $button.attr("aria-expanded", "false");
        }
    }

    function toggleMenu(e) {
        e.stopPropagation();
        const isOpen = !$menu.toggleClass("hidden").hasClass("hidden");
        $btn.attr("aria-expanded", isOpen ? "true" : "false");

        // If userList just opened, hide the others
        if (isOpen) {
            closeIfOpen($notifPanel, $notifBtn);
            closeIfOpen($langList, $langBtn);
        }
    }

    // open/close
    $btn.on("click", toggleMenu);

    // click outside -> close
    $(document).on("click", function (e) {
        if (!$menu.is(e.target) &&
            $menu.has(e.target).length === 0 &&
            !$btn.is(e.target) &&
            $btn.has(e.target).length === 0
        ) {
            closeMenu();
        }
    });

    // ESC -> close
    $(document).on("keydown", function (e) {
        if (e.key === "Escape") closeMenu();
    });
});
