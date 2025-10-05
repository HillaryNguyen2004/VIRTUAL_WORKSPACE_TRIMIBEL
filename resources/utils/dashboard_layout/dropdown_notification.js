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

    // Request notification permission for browser notifications
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Set up real-time notifications if Echo is available
    if (typeof window.Echo !== 'undefined') {
        // Get user ID from meta tag or data attribute
        const userId = $('meta[name="user-id"]').attr('content') || document.body.dataset.userId;
        
        console.log('Setting up real-time notifications for user:', userId);
        
        if (userId) {
            try {
                window.Echo.private(`App.Models.User.${userId}`)
                    .notification((notification) => {
                        console.log('New notification received:', notification);
                        console.log('Notification type:', typeof notification);
                        console.log('Notification keys:', Object.keys(notification));
                        
                        // Create notification object in expected format
                        const notificationData = {
                            id: notification.id || Date.now(), // fallback ID if not provided
                            data: {
                                message: notification.message || 'You have a new notification',
                                date: notification.date || new Date().toLocaleString()
                            }
                        };
                        
                        console.log('Processed notification data:', notificationData);
                        
                        // Add the new notification to the list
                        const newItem = renderItem(notificationData);
                        
                        $list.prepend(newItem);
                        
                        // Update badge count
                        const currentCount = $list.find(".notification-item").length;
                        setBadge(currentCount);
                        
                        // Show the list and hide empty state
                        $list.removeClass("hidden");
                        $empty.addClass("hidden");
                        
                        // Show browser notification if permission granted
                        if (Notification.permission === 'granted') {
                            new Notification('New Notification', {
                                body: notification.message || 'You have a new notification',
                                icon: '/favicon.ico'
                            });
                        }
                    })
                    .error((error) => {
                        console.error('Echo notification channel error:', error);
                    });
                    
                console.log('Real-time notification listener set up successfully');
            } catch (error) {
                console.error('Error setting up Echo notification listener:', error);
                // Fallback to polling
                setInterval(loadNotifications, 30000);
            }
        } else {
            console.warn('User ID not found, cannot set up real-time notifications');
            setInterval(loadNotifications, 30000);
        }
    } else {
        console.warn('Laravel Echo not available, using polling fallback');
        // Fallback: poll for new notifications every 30 seconds
        setInterval(loadNotifications, 30000);
    }

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
