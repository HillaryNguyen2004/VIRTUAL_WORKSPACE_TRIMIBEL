import { showToast } from "../show-toast.js";

$(function () {
    const $dialog = $("#request-dayoff-dialog");
    const $open = $("#open-request-dayoff");
    const $form = $("#request-dayoff-form");
    const $submit = $form.find('button[type="submit"]');
    const txt = $submit.text();

    const openDialog = () => {
        $dialog.removeClass("hidden").addClass("flex");
        $("body").addClass("overflow-hidden");
    };

    const closeDialog = () => {
        $dialog.removeClass("flex").addClass("hidden");
        $("body").removeClass("overflow-hidden");
        $form[0]?.reset();
        // Reset date summary
        $("#date-summary").addClass("hidden");
        // $("#dates-input").val("");
    };

    // open / close
    $open.on("click", function (e) {
        e.preventDefault();
        openDialog();
    });
    $dialog.on("click", ".close-request-dayoff", function (e) {
        e.preventDefault();
        closeDialog();
    });
    // close when clicking backdrop only
    $dialog.on("click", function (e) {
        if (e.target === this) closeDialog();
    });

    // Date range functionality
    const startDateInput = $("#start_date");
    const endDateInput = $("#end_date");
    const dateSummary = $("#date-summary");
    const selectedDatesList = $("#selected-dates-list");
    const totalDaysElement = $("#total-days");

    // Half day handling
    const leaveTypeSelect = $form.find('#leave_type');
    const halfDayContainer = $('#half-day-container');
    const halfDaySelect = $('#half_day_period');

    function toggleHalfDay() {
        const isHalf = leaveTypeSelect.val() === 'OFF_HALF';
        halfDayContainer.toggleClass('hidden', !isHalf);
        if (isHalf) {
            halfDaySelect.attr('required', 'required');
        } else {
            halfDaySelect.removeAttr('required');
            halfDaySelect.val('');
        }
    }

    // Function to generate dates between start and end date
    function generateDateRange(startDate, endDate) {
        const dates = [];
        const currentDate = new Date(startDate);
        const end = new Date(endDate);

        while (currentDate <= end) {
            dates.push(new Date(currentDate).toISOString().split('T')[0]);
            currentDate.setDate(currentDate.getDate() + 1);
        }

        return dates;
    }

    // Function to update date summary
    function updateDateSummary() {
        const startDate = startDateInput.val();
        const endDate = endDateInput.val();

        if (!startDate || !endDate) {
            dateSummary.addClass("hidden");
            // datesInput.val("");
            return;
        }

        const start = new Date(startDate);
        const end = new Date(endDate);

        if (start > end) {
            dateSummary.addClass("hidden");
            // datesInput.val("");
            endDateInput[0].setCustomValidity('End date must be after or equal to start date');
            return;
        }

        endDateInput[0].setCustomValidity('');

        // Generate all dates
        const allDates = generateDateRange(startDate, endDate);

        if (allDates.length === 0) {
            dateSummary.addClass("hidden");
            // datesInput.val("");
            return;
        }

        // Show summary
        dateSummary.removeClass("hidden");

        // Update selected dates list (show first 3 dates and count)
        let datesHtml = '';
        const displayCount = Math.min(3, allDates.length);

        for (let i = 0; i < displayCount; i++) {
            datesHtml += `<div>${allDates[i]}</div>`;
        }

        if (allDates.length > displayCount) {
            datesHtml += `<div class="text-blue-500">... and ${allDates.length - displayCount} more days</div>`;
        }

        selectedDatesList.html(datesHtml);
        totalDaysElement.text(`Total: ${allDates.length} day(s)`);

        // Store all dates in hidden input (comma-separated)
        // datesInput.val(allDates.join(','));
    }

    // Event listeners for date inputs
    startDateInput.on('change', updateDateSummary);
    endDateInput.on('change', updateDateSummary);

    // Initialize if there are old values
    if (startDateInput.val() && endDateInput.val()) {
        updateDateSummary();
    }

    // Initialize half day toggle and bind change
    leaveTypeSelect.on('change', toggleHalfDay);
    toggleHalfDay();

    // submit (AJAX)
    $form.on("submit", function (e) {
        e.preventDefault();

        const startDate = startDateInput.val();
        const endDate = endDateInput.val();

        if (!startDate || !endDate) {
            showToast('Please select both start and end dates.', 'error');
            return;
        }

        const start = new Date(startDate);
        const end = new Date(endDate);

        if (start > end) {
            showToast('End date must be after or equal to start date.', 'error');
            return;
        }

        // Ensure dates are populated
        updateDateSummary();

        // Calculate difference in days
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

        if (diffDays > 30) { // Limit to 30 days max
            if (!confirm(`You are requesting ${diffDays} days off. Are you sure you want to continue?`)) {
                return;
            }
        }

        $submit
            .prop("disabled", true)
            .addClass("opacity-50")
            .text("Sending...");

        const fd = new FormData(this); // includes CSRF & form fields

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
                showToast(res?.message || "Request submitted successfully", "success");
                closeDialog();
            })
            .fail((error) => {
                const message = error.responseJSON?.message || error.responseJSON?.error || "Request failed";
                showToast(message, "error");
            })
            .always(() => {
                $submit
                    .prop("disabled", false)
                    .removeClass("opacity-50")
                    .text(txt);
            });
    });
});
