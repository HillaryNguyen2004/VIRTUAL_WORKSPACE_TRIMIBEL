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

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('task-search-input');
    const sortHeaders = document.querySelectorAll('[data-sort-key]');
    
    // State to track sort direction
    let sortDirection = {};

    // 1. Initialize Search Listener
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            filterTasks(this.value);
        });
    }

    // 2. Initialize Sort Listeners
    sortHeaders.forEach(header => {
        header.addEventListener('click', function () {
            const columnKey = this.getAttribute('data-sort-key');
            sortTable(columnKey, this);
        });
    });

    /**
     * Filter the table rows based on input
     */
    function filterTasks(searchTerm) {
        const filter = searchTerm.toLowerCase();
        const rows = document.querySelectorAll('#task-tbody .task-row');

        rows.forEach(row => {
            const taskId = row.getAttribute('data-task-id');
            // Get text content from the Title and ID columns
            const title = row.querySelector('.task-title').innerText.toLowerCase();
            const idText = row.querySelector('.font-mono').innerText.toLowerCase();
            
            // The associated description row
            const descRow = document.getElementById('desc-' + taskId);

            if (title.includes(filter) || idText.includes(filter)) {
                row.classList.remove('hidden');
                // We do not force show the descRow here; we leave it to the user click
                // But we must ensure it isn't hidden by a previous filter if it was open
                if (descRow && descRow.classList.contains('!hidden')) { 
                    // Logic to handle specific "forced hidden" state if you implement complex filtering
                    // For now, standard classList manipulation is sufficient
                }
            } else {
                row.classList.add('hidden');
                // If parent is hidden, ensuring details are hidden visually
                if (descRow) descRow.classList.add('hidden'); 
            }
        });
    }

    /**
     * Sort the table rows
     */
    function sortTable(column, headerElement) {
        const tbody = document.getElementById('task-tbody');
        const rows = Array.from(document.querySelectorAll('#task-tbody .task-row'));
        
        // Toggle direction (default to asc if undefined)
        const currentDir = sortDirection[column] === 'asc' ? 'desc' : 'asc';
        sortDirection[column] = currentDir;

        // Visual Feedback: Reset other icons and update current
        document.querySelectorAll('.sort-icon').forEach(icon => icon.classList.remove('text-primary', 'rotate-180'));
        const currentIcon = headerElement.querySelector('.sort-icon');
        if (currentIcon) {
            currentIcon.classList.add('text-primary');
            if (currentDir === 'desc') currentIcon.classList.add('rotate-180');
        }

        // Sort Data
        rows.sort((a, b) => {
            let valA = a.getAttribute('data-sort-' + column);
            let valB = b.getAttribute('data-sort-' + column);

            // Parse ID as integer for correct numerical sorting
            if (column === 'id') {
                valA = parseInt(valA) || 0;
                valB = parseInt(valB) || 0;
            }

            if (valA < valB) return currentDir === 'asc' ? -1 : 1;
            if (valA > valB) return currentDir === 'asc' ? 1 : -1;
            return 0;
        });

        // Re-append rows to DOM
        rows.forEach(row => {
            tbody.appendChild(row); // Move Main Row
            
            // Move associated Description Row immediately after
            const taskId = row.getAttribute('data-task-id');
            const descRow = document.getElementById('desc-' + taskId);
            if (descRow) {
                tbody.appendChild(descRow);
            }
        });
    }
});
