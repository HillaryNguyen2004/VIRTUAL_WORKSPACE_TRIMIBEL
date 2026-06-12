$(document).ready(function () {
    const $modal = $("#edit-phase-dialog");
    const $form = $("#editPhaseForm");

    // Open Modal
    $(document).on("click", ".edit-phase-btn", function () {
        const $btn = $(this);

        // Populate Data
        $form.attr("action", $btn.data("action"));
        $form.find('input[name="title"]').val($btn.data("title"));
        $("#edit_start_date").val($btn.data("start-date"));
        $("#edit_due_date").val($btn.data("due-date"));

        // Show
        $modal.fadeIn(200);
    });

    // Close Modal
    $(document).on("click", "#close-edit-phase", function () {
        $modal.fadeOut(200);
    });

    // Close on Click Outside
    $(window).on("click", function (e) {
        if ($(e.target).is($modal)) {
            $modal.fadeOut(200);
        }
    });

    // Ensure hidden on load (failsafe)
    $modal.hide();
});
