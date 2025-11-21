import { showToast } from "../show-toast.js";

// Get CSRF token from meta tag
function getCSRFToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
}

document.getElementById("checkInBtn")?.addEventListener("click", () => {
    const username = document.getElementById("usernameInput").value.trim();

    if (!username) {
        showToast("Username required!", "error");
        return;
    }

    fetch("/api/check-in", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": getCSRFToken(), // Add CSRF token
        },
        body: JSON.stringify({ username }),
    })
        .then((res) => res.json().then((body) => ({ ok: res.ok, body })))
        .then(({ ok, body }) => {
            if (ok && body.token) {
                localStorage.setItem("api_token", body.token);
                showToast(body.message || "Check-in success", "success", 5000);
                document.getElementById("usernameInput").value = ""; // Fixed this line
            } else {
                showToast(body.message || "Check-in failed", "error", 5000);
            }
        })
        .catch((error) =>
            showToast(error || "Something wrong. Please try again.", "error")
        );
});

document.getElementById("checkOutBtn")?.addEventListener("click", () => {
    const username = document.getElementById("usernameInput").value.trim();
    const token = localStorage.getItem("api_token");

    if (!username) {
        showToast("Username required!", "error");
        return;
    }
    if (!token) {
        showToast("Missing API token.", "error");
        return;
    }

    fetch("/api/check-out", {
        method: "POST",
        headers: {
            Authorization: "Bearer " + token,
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": getCSRFToken(), // Add CSRF token
        },
        body: JSON.stringify({ username }),
    })
        .then((res) => res.json().then((body) => ({ ok: res.ok, body })))
        .then(({ ok, body }) => {
            showToast(
                body.message || (ok ? "Check-out success" : "Check-out failed"),
                ok ? "success" : "error"
            );

            if (ok) document.getElementById("usernameInput").value = ""; // Fixed this line
        })
        .catch(() => showToast("Something wrong. Please try again.", "error"));
});
