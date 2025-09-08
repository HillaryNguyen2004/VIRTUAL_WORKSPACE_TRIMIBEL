const updateStatus = () => {
    document.addEventListener("input", (e) => {
        const range = e.target.closest(".range");
        if (!range) return;
        const menu = range.closest(".status-menu");
        const pctEl = menu.querySelector(".menu-pct");
        pctEl.textContent = range.value;
    });
};

const closeAllMenus = (except) => {
    document.querySelectorAll(".status-menu").forEach((m) => {
        if (m !== except) m.classList.add("hidden");
    });
    document
        .querySelectorAll('.status-btn[aria-expanded="true"]')
        .forEach((b) => b.setAttribute("aria-expanded", "false"));
};

const openMenu = (btn, menu) => {
    closeAllMenus(menu);
    menu.classList.remove("hidden");
    btn.setAttribute("aria-expanded", "true");
};

const closeMenu = (btn, menu) => {
    menu.classList.add("hidden");
    btn.setAttribute("aria-expanded", "false");
};

document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("task-tbody");
    if (!tbody) return;

    const closeAll = (exceptMenu) => {
        tbody.querySelectorAll(".status-menu").forEach((m) => {
            if (m !== exceptMenu) m.classList.add("hidden");
        });
        tbody
            .querySelectorAll('.status-btn[aria-expanded="true"]')
            .forEach((b) => b.setAttribute("aria-expanded", "false"));
    };

    // Toggle when clicking the status pill
    tbody.addEventListener("click", (e) => {
        const btn = e.target.closest(".status-btn");
        if (!btn || !tbody.contains(btn)) return;

        const targetId = btn.getAttribute("aria-controls");
        const menu = document.getElementById(targetId);
        if (!menu) return;

        const willOpen = menu.classList.contains("hidden");
        closeAll(menu);
        if (willOpen) {
            menu.classList.remove("hidden");
            btn.setAttribute("aria-expanded", "true");
        } else {
            menu.classList.add("hidden");
            btn.setAttribute("aria-expanded", "false");
        }
        e.stopPropagation();
    });
});

updateStatus();
