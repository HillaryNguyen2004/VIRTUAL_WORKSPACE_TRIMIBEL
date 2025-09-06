document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("langButton");
    const menu = document.getElementById("langList");

    const close = () => {
        menu.classList.add("hidden");
        btn.setAttribute("aria-expanded", "false");
    };
    const toggle = () => {
        const open = menu.classList.toggle("hidden") === false;
        btn.setAttribute("aria-expanded", open ? "true" : "false");
    };

    btn.addEventListener("click", (e) => {
        e.stopPropagation();
        toggle();
    });
    document.addEventListener("click", close);
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            close();
            btn.focus();
        }
    });
});
