import $ from "jquery";
import { showToast } from "../show-toast.js";

$(function () {
    const $dialog = $("#holidayModal");
    const $open = $("#openHolidayModal");
    const $form = $("#holidayForm");
    const $submit = $form.find("button[type='submit']");
    const $modalTitle = $("#modal-title");

    // Form Inputs
    const $methodInput = $("#holidayMethod");
    const $titleInput = $("#holidayTitle");
    const $startInput = $("#holidayStart");
    const $endInput = $("#holidayEnd");

    const originalBtnText = $submit.text();

    // --- Helpers ---
    const openDialog = () => {
        $dialog.removeClass("hidden").addClass("flex");
        $("body").addClass("overflow-hidden");
    };

    const closeDialog = () => {
        $dialog.removeClass("flex").addClass("hidden");
        $("body").removeClass("overflow-hidden");
    };

    // --- 1. Open Dialog (Create Mode) ---
    // Triggered by the "Add New" button in admindashboard
    $open.on("click", function (e) {
        e.preventDefault();

        $modalTitle.text('Add New Holiday');
        $form.attr('action', '/holidays');
        $methodInput.val('POST');
        $form[0].reset();

        // In create mode: hide both delete and cancel buttons
        $("#deleteHolidayBtn").addClass("hidden");
        $("#cancelHolidayBtn").addClass("hidden");

        openDialog();
    });

    // --- 2. Open Dialog (Edit Mode) ---
    // Triggered by clicking a holiday card. 
    // We attach the listener to the document to handle dynamic elements if needed.
    $(document).on("click", ".edit-holiday-trigger", function (e) {
        e.preventDefault();

        // Parse the data attribute
        const data = $(this).data('holiday');

        $modalTitle.text('Edit Holiday');
        $form.attr('action', `/holidays/${data.id}`);
        $methodInput.val('PUT');

        $titleInput.val(data.title);
        // Format YYYY-MM-DDTHH:MM
        $startInput.val(data.start_date ? data.start_date.slice(0, 16) : '');
        $endInput.val(data.end_date ? data.end_date.slice(0, 16) : '');

        openDialog();
    });

    // --- 3. Close Logic ---
    // Close button inside modal
    $dialog.on("click", ".close-holiday-modal", function (e) {
        e.preventDefault();
        closeDialog();
    });

    // Close on Backdrop Click
    $dialog.on("click", function (e) {
        if (e.target === this) closeDialog();
    });

    // --- 4. Submit (AJAX) ---
    $form.on("submit", function (e) {
        e.preventDefault();

        $submit
            .prop("disabled", true)
            .addClass("opacity-50 cursor-not-allowed")
            .text("Saving...");

        const fd = new FormData(this);

        $.ajax({
            url: this.action,
            method: "POST", // _method input handles PUT spoofing
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .done((res) => {
                showToast(res?.message || "Holiday saved successfully!", "success");
                closeDialog();
                setTimeout(() => { window.location.reload(); }, 500);
            })
            .fail((error) => {
                let msg = error.responseJSON?.message || "Update failed";
                if (error.responseJSON?.errors) {
                    msg = Object.values(error.responseJSON.errors)[0][0];
                }
                showToast(msg, "error");
            })
            .always(() => {
                $submit
                    .prop("disabled", false)
                    .removeClass("opacity-50 cursor-not-allowed")
                    .text(originalBtnText);
            });
    });
});

// --- Global function for onclick handlers ---
// This needs to be outside the jQuery ready block to be accessible from HTML onclick
window.openHolidayModal = function (mode, holidayData) {
    if (mode === 'edit' && holidayData) {
        const $dialog = $("#holidayModal");
        const $form = $("#holidayForm");
        const $modalTitle = $("#modal-title");
        const $methodInput = $("#holidayMethod");
        const $titleInput = $("#holidayTitle");
        const $startInput = $("#holidayStart");
        const $endInput = $("#holidayEnd");

        $modalTitle.text('Edit Holiday');
        $form.attr('action', `/holidays/${holidayData.id}`);
        $methodInput.val('PUT');

        $titleInput.val(holidayData.title);
        // Use the pre-formatted local dates from the model
        $startInput.val(holidayData.start_date_local || '');
        $endInput.val(holidayData.end_date_local || '');

        // In edit mode: show delete button, hide cancel button
        $("#deleteHolidayBtn").removeClass("hidden").data('holiday-id', holidayData.id);
        $("#cancelHolidayBtn").addClass("hidden");

        // Open the modal
        $dialog.removeClass("hidden").addClass("flex");
        $("body").addClass("overflow-hidden");
    }
};

// --- Delete Holiday Handler ---
$(document).on("click", "#deleteHolidayBtn", function (e) {
    e.preventDefault();

    const holidayId = $(this).data('holiday-id');

    if (!holidayId) {
        showToast("Unable to delete: Holiday ID not found", "error");
        return;
    }

    // Confirmation dialog
    if (!confirm("Are you sure you want to delete this holiday? This action cannot be undone.")) {
        return;
    }

    const $btn = $(this);
    const originalText = $btn.text();

    $btn.prop("disabled", true)
        .addClass("opacity-50 cursor-not-allowed")
        .text("Deleting...");

    $.ajax({
        url: `/holidays/${holidayId}`,
        method: "POST",
        data: { _method: "DELETE" },
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .done((res) => {
            showToast(res?.message || "Holiday deleted successfully!", "success");
            $("#holidayModal").removeClass("flex").addClass("hidden");
            $("body").removeClass("overflow-hidden");
            setTimeout(() => { window.location.reload(); }, 500);
        })
        .fail((error) => {
            let msg = error.responseJSON?.message || "Delete failed";
            showToast(msg, "error");
        })
        .always(() => {
            $btn.prop("disabled", false)
                .removeClass("opacity-50 cursor-not-allowed")
                .text(originalText);
        });
});