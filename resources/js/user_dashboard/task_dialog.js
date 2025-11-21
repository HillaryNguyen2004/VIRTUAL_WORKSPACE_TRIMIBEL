const dialog = document.getElementById("task-dialog");
const openButton = document.getElementById("open-task");
const closeButton = document.getElementById("close-task");

function resetTaskUI(scope = dialog) {
    if (!scope) return;

    // 1) Close ALL status dropdowns
    scope
        .querySelectorAll(".status-menu")
        .forEach((m) => m.classList.add("hidden"));
    scope
        .querySelectorAll('.status-btn[aria-expanded="true"]')
        .forEach((b) => b.setAttribute("aria-expanded", "false"));

    // 2) Collapse ALL description rows
    scope
        .querySelectorAll(".desc-row")
        .forEach((r) => r.classList.add("hidden"));
    scope
        .querySelectorAll('tr[aria-expanded="true"]')
        .forEach((tr) => tr.setAttribute("aria-expanded", "false"));
    scope
        .querySelectorAll('.show-desc[aria-expanded="true"]')
        .forEach((btn) => btn.setAttribute("aria-expanded", "false"));
}

openButton?.addEventListener("click", () => {
    dialog.classList.remove("hidden");
    dialog.classList.add("flex");
    document.body.classList.add("overflow-hidden");
});

const closeDialog = () => {
    // close all menus and collapse all descriptions
    resetTaskUI();

    dialog.classList.remove("flex");
    dialog.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
};

closeButton?.addEventListener("click", closeDialog);
// close only when clicking backdrop, not the panel
dialog?.addEventListener("click", (e) => {
    if (e.target === dialog) closeDialog();
});
