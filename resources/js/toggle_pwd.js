const togglePwd = () => {
    const btn = document.getElementById("togglePwd");
    const input = document.getElementById("password");
    if (!btn || !input) return;

    const eyeOpen = btn.querySelector('[data-icon="eye-open"]');
    const eyeClosed = btn.querySelector('[data-icon="eye-closed"]');

    const toggle = () => {
        const show = input.type === "password";
        input.type = show ? "text" : "password";
        // swap icons
        eyeOpen.classList.toggle("hidden", show);
        eyeClosed.classList.toggle("hidden", !show);

        btn.setAttribute(
            "aria-label",
            show ? "Hide password" : "Show password"
        );
        btn.setAttribute("aria-pressed", show ? "true" : "false");
    }

    btn.addEventListener("click", toggle);
}

togglePwd();