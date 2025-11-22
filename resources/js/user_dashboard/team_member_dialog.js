const dialog = document.getElementById("team-member-dialog");
const openButton = document.getElementById("open-team-member");
const closeButton = document.getElementById("close-team-member");

openButton?.addEventListener("click", () => {
    dialog.classList.remove("hidden");
    dialog.classList.add("flex");
    document.body.classList.add("overflow-hidden");
});

const closeDialog = () => {
    dialog.classList.remove("flex");
    dialog.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
};

closeButton?.addEventListener("click", closeDialog);
// close only when clicking backdrop, not the panel
dialog?.addEventListener("click", (e) => {
    if (e.target === dialog) closeDialog();
});
