const updateStatus = () => {
    document.addEventListener("input", (e) => {
        const range = e.target.closest(".range");
        if (!range) return;
        const menu = range.closest(".status-menu");
        const pctEl = menu.querySelector(".menu-pct");
        if (pctEl) pctEl.textContent = range.value;
    });
};

// ====== Menu toggle helpers ======
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

// ====== DOMContentLoaded ======
document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("task-tbody");
    if (!tbody) return;

    // Toggle when clicking the status pill
    tbody.addEventListener("click", (e) => {
        const btn = e.target.closest(".status-btn");
        if (!btn || !tbody.contains(btn)) return;

        const targetId = btn.getAttribute("aria-controls");
        const menu = document.getElementById(targetId);
        if (!menu) return;

        const willOpen = menu.classList.contains("hidden");
        closeAllMenus(menu);
        if (willOpen) {
            openMenu(btn, menu);
        } else {
            closeMenu(btn, menu);
        }
        e.stopPropagation();
    });

    // Listen for clicks inside menus (Apply + Completed)
    tbody.addEventListener("click", async (e) => {
        const btn = e.target.closest("button, div[role='menuitem']");
        if (!btn) return;

        const row = btn.closest("tr[data-task-id]");
        if (!row) return;

        const taskId = row.getAttribute("data-task-id");
        const menu = btn.closest(".status-menu");

        //Pending button
        if (btn.dataset.status === "pending") {
            await updateTaskStatus(taskId, "pending", 0);
        }

        // Apply button (from in_progress menu)
        if (btn.textContent.trim() === "Apply") {
            const range = menu.querySelector(".range");
            const percentage = range ? range.value : null;
            await updateTaskStatus(taskId, "in_progress", percentage);
        }

        // Completed button
        if (btn.dataset.status === "completed") {
            await updateTaskStatus(taskId, "completed", 100);
        }
    });
});

updateStatus();

// ====== AJAX helper ======
async function updateTaskStatus(taskId, status, percentage = null) {
    try {
        const formData = new FormData();
        formData.append("status", status);
        if (percentage !== null) formData.append("percentage", percentage);

        // Build correct URL from Laravel (must be set in Blade)
        const url = window.updateStatusUrl.replace(":id", taskId);

        const response = await fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: formData
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `Server error: ${response.status}`);
        }

        console.log("✅ Updated:", data);
        
        // Refresh the page or update the UI accordingly
        location.reload();
        
        return data;
    } catch (err) {
        console.error("❌ Update failed:", err);
        alert("Failed to update task status: " + err.message);
    }
}
