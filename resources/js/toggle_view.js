// -----------------------------------------
// Toggle helpers (supports multi-open)
// -----------------------------------------
$(function () {
    $(document).on("click", ".toggle-row", function (e) {
        e.preventDefault();

        const $btn = $(this);
        const targetId = $btn.data("target");
        const $row = $("#" + targetId);

        if (!$row.length) return;

        const isOpen = !$row.hasClass("hidden");

        // Toggle only this row
        $row.toggleClass("hidden", isOpen);

        // Update aria-expanded for only this button
        $btn.attr("aria-expanded", isOpen ? "false" : "true");
    });

    $(document).on("click", ".toggle-project", function (e) {
        e.preventDefault();

        const $btn = $(this);
        const targetId = $btn.data("target");
        const $row = $("#" + targetId);

        if (!$row.length) return;

        const isOpen = !$row.hasClass("hidden");

        // Toggle only this project row
        $row.toggleClass("hidden", isOpen);

        // Update aria-expanded for only this button
        $btn.attr("aria-expanded", isOpen ? "false" : "true");
    });

    $(document).on("click", ".toggle-task", function (e) {
        e.preventDefault();

        const $btn = $(this);
        const targetId = $btn.data("target");
        const $row = $("#" + targetId);

        if (!$row.length) return;

        const isOpen = !$row.hasClass("hidden");

        // Toggle only this task detail row
        $row.toggleClass("hidden", isOpen);

        // Update aria-expanded for only this button
        $btn.attr("aria-expanded", isOpen ? "false" : "true");
    });

});
