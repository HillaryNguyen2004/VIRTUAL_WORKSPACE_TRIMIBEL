document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("task-tbody");
    if (!tbody) return;

    tbody.addEventListener("click", (e) => {
        // handle clicks on the button or its SVG children
        const btn = e.target.closest(".js-show-desc");
        if (!btn || !tbody.contains(btn)) return;

        const targetId = btn.getAttribute("aria-controls");
        const descRow = document.getElementById(targetId);
        const mainRow = btn.closest("tr");

        if (!descRow || !mainRow) return;

        const isHidden = descRow.classList.toggle("hidden");
        btn.setAttribute("aria-expanded", String(!isHidden));
        mainRow.setAttribute("aria-expanded", String(!isHidden));
    });
});
