function toggleRow(id) {
    $("#" + id).toggleClass("hidden");
}

$(function () {
    $(document).on("click", ".toggle-row", function (e) {
        e.preventDefault();

        const $btn = $(this);
        const target = $btn.data("target");
        const $row = $("#" + target);

        if (!$row.length) return;

        const isCurrentlyOpen = !$row.hasClass("hidden");

        // Close all detail rows
        $(".detail-row").addClass("hidden");

        // Reset aria-expanded for all buttons
        $(".toggle-row").attr("aria-expanded", "false");

        // If it was closed, open it (if it was open, leave all closed)
        if (!isCurrentlyOpen) {
            $row.removeClass("hidden");
            $btn.attr("aria-expanded", "true");
        }
    });
});

$(function () {
    // PROJECT TOGGLE
    $(document).on("click", ".toggle-project", function (e) {
        e.preventDefault();

        const $btn = $(this);
        const target = $btn.data("target");
        const $row = $("#" + target);

        if (!$row.length) return;

        const isOpen = !$row.hasClass("hidden");

        // Close all projects
        $(".project-row").addClass("hidden");
        $(".toggle-project").attr("aria-expanded", "false");

        // Also close any open task detail rows
        $(".detail-row").addClass("hidden");
        $(".toggle-task").attr("aria-expanded", "false");

        // Open clicked project if it was closed
        if (!isOpen) {
            $row.removeClass("hidden");
            $btn.attr("aria-expanded", "true");
        }
    });

    // TASK DETAILS TOGGLE (only inside currently open project)
    $(document).on("click", ".toggle-task", function (e) {
        e.preventDefault();

        const $btn = $(this);
        const target = $btn.data("target");
        const $row = $("#" + target);

        if (!$row.length) return;

        const isOpen = !$row.hasClass("hidden");

        // Close other task detail rows (optional: keep it as accordion)
        $(".detail-row").not($row).addClass("hidden");
        $(".toggle-task").not($btn).attr("aria-expanded", "false");

        // Toggle current
        if (isOpen) {
            $row.addClass("hidden");
            $btn.attr("aria-expanded", "false");
        } else {
            $row.removeClass("hidden");
            $btn.attr("aria-expanded", "true");
        }
    });
});
