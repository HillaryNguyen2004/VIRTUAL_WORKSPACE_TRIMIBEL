import $ from "jquery";
import { showToast } from "../show-toast.js"; // Ensure this path matches your folder structure

$(function () {
    const $dialog = $("#edit-company-hours-dialog");
    const $open = $("#open-company-hours-btn"); // ID of the button in dashboard
    const $form = $("#edit-company-hours-form");
    const $submit = $("#submit-company-hours-btn");
    const originalBtnText = $submit.text();

    const openDialog = () => {
        $dialog.removeClass("hidden").addClass("flex");
        $("body").addClass("overflow-hidden");
    };

    const closeDialog = () => {
        $dialog.removeClass("flex").addClass("hidden");
        $("body").removeClass("overflow-hidden");
    };

    // 1. Open Dialog
    $open.on("click", function (e) {
        e.preventDefault();
        openDialog();
    });

    // 2. Close Dialog (X button or Cancel)
    $dialog.on("click", ".close-company-hours", function (e) {
        e.preventDefault();
        closeDialog();
    });

    // 3. Close on Backdrop Click
    $dialog.on("click", function (e) {
        if (e.target === this) closeDialog();
    });

    // 4. Submit (AJAX)
    $form.on("submit", function (e) {
        e.preventDefault();

        // Disable button & show loading state
        $submit
            .prop("disabled", true)
            .addClass("opacity-50 cursor-not-allowed")
            .text("Saving...");

        const fd = new FormData(this);

        $.ajax({
            url: this.action,
            method: "POST",
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .done((res) => {
                showToast(res?.message || "Hours updated successfully!", "success");
                closeDialog();

                // Reload page to reflect changes on the dashboard UI
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            })
            .fail((error) => {
                let msg = error.responseJSON?.message || "Update failed";
                // If validation errors exist, try to show the first one
                if (error.responseJSON?.errors) {
                    msg = Object.values(error.responseJSON.errors)[0][0];
                }
                showToast(msg, "error");
            })
            .always(() => {
                // Reset button state
                $submit
                    .prop("disabled", false)
                    .removeClass("opacity-50 cursor-not-allowed")
                    .text(originalBtnText);
            });
    });
});