$(function () {
    const $btn = $("#notificationBtn");
    const $panel = $("#notificationPanel");
    const $badge = $("#notificationBadge");
    const $list = $("#alertsList");
    const $empty = $("#emptyState");
    const $markAllBtn = $("#markAllRead");
    const csrf = $('meta[name="csrf-token"]').attr("content");

    const $langList = $("#langList");
    const $langBtn = $('#langButton');
    const $userList = $("#userList");
    const $userBtn = $('#userButton');

    function isOpen() { 
        return !$panel.hasClass('hidden'); 
    }

    function openPanel() {
        // if notification panel just open, close others
        if (!$langList.hasClass("hidden")) {
            $langList.addClass("hidden");
            $langBtn.attr('aria-expanded', 'false');
        }

        if (!$userList.hasClass("hidden")) {
            $userList.addClass("hidden");
            $userBtn.attr('aria-expanded', 'false');
        }

        $btn.attr("aria-expanded", "true");
        $panel.removeClass("hidden");
    }

    function closePanel() {
        $btn.attr("aria-expanded", "false");
        $panel.addClass("hidden")
    }

    $btn.on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        isOpen() ? closePanel() : openPanel();
    });

    $(document).on("click", function (e) {
        if (!isOpen()) return;
        if (
            !$(e.target).closest($panel).length &&
            !$(e.target).closest($btn).length
        ) {
            closePanel();
        }
    });

    $(document).on("keydown", function (e) {
        if (e.key === "Escape" && isOpen()) closePanel();
    });

    function setBadge(count) {
        if (count > 0) {
            $badge.text(count > 99 ? "99+" : count).removeClass("hidden").addClass("flex");
        } else {
            $badge.text("").removeClass("flex").addClass("hidden");
        }
    }

    function renderItem(n) {
        return `
            <button type="button"
                class="notification-item flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-gray-50"
                data-id="${n.id}">
                <span class="flex h-2 w-2 items-center justify-center rounded-full bg-indigo-500"></span>
                <span class="flex-1">
                <span class="block text-xs text-gray-500">${n.data.date || ""}</span>
                <span class="block text-sm font-medium text-gray-900">${n.data.message || ""}</span>
                </span>
            </button>
            `;
    }

    function loadNotifications() {
        $empty.addClass("hidden");
        $list.removeClass("hidden")

        $.getJSON("/notifications/unread", function (data) {
            if (!Array.isArray(data) || data.length === 0) {
                $empty.removeClass("hidden");
                $list.addClass("hidden")
                setBadge(0);
                return;
            }
            const html = data.map(renderItem).join("");
            $list.html(html);
            setBadge(data.length);
        }).fail(function () {
            $empty.text("Failed to load notifications").removeClass("hidden");
        });
    }

    loadNotifications();

    // Delegate click: mark one as read
    $list.on("click", ".notification-item", function () {
        const $item = $(this);
        const id = $item.data("id");

        $.ajax({
            url: `/notifications/read/${id}`,
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            success: function () {
                $item.remove();
                const remaining = $list.find(".notification-item").length;
                setBadge(remaining);
                if (remaining === 0) {
                    $empty.removeClass("hidden");
                    $list.addClass("hidden");
                }
            },
        });
    });

    // Mark all as read
    $markAllBtn.on("click", function (e) {
        e.preventDefault();
        $.ajax({
            url: "/notifications/read-all",
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" },
            success: function () {
                $list.empty();
                setBadge(0);
                $empty.removeClass("hidden");
                $list.addClass("hidden");
            },
        });
    });
});
