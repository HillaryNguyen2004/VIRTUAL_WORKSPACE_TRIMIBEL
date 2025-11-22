const alertsRoot = document.getElementById("alerts");

// simple XSS-safe text
function escapeHtml(s = "") {
    return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

export const showToast = (message, type = "success", life = 5000) => {
    if (!alertsRoot) return;

    // cap number of toasts
    if (alertsRoot.children.length >= 6) {
        alertsRoot.firstElementChild?.remove();
    }

    const base =
        "px-3 py-2 bg-white text-sm rounded-xl shadow-lg border transition-all duration-300 pointer-events-auto";
    const color =
        type === "success"
            ? "text-green-600 border-green-400"
            : "text-red-600 border-red-400";

    const el = document.createElement("div");
    el.className = `${base} ${color} opacity-0 translate-y-1`;

    el.innerHTML = `
    <div class="flex items-center gap-2">
      <span>${escapeHtml(message)}</span>
      <button type="button" aria-label="Close"
              class="ml-2 text-xs text-gray-400 hover:text-gray-600"
              data-close-toast>&times;</button>
    </div>
  `;

    alertsRoot.appendChild(el);

    // animate in
    requestAnimationFrame(() => {
        el.classList.remove("opacity-0", "translate-y-1");
        el.classList.add("opacity-100", "translate-y-0");
    });

    const close = () => {
        el.classList.remove("opacity-100", "translate-y-0");
        el.classList.add("opacity-0", "translate-y-1");
        el.addEventListener("transitionend", () => el.remove(), { once: true });
        clearTimeout(timer);
    };

    const timer = setTimeout(close, life);
    el.querySelector("[data-close-toast]")?.addEventListener("click", close);

    return close;
}
