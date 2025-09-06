import { showToast } from "../show-toast.js";

$(function () {
    const $dialog = $("#request-dayoff-dialog");
    const $open = $("#open-request-dayoff");
    const $close = $("#close-request-dayoff");
    const $form = $("#request-dayoff-form");
    const $submit = $("#submitBtn");
    const txt = $submit.text();

    const openDialog = () => {
        $dialog.removeClass("hidden").addClass("flex");
        $("body").addClass("overflow-hidden");
    };

    const closeDialog = () => {
        $dialog.removeClass("flex").addClass("hidden");
        $("body").removeClass("overflow-hidden");
        $form[0]?.reset();
    };

    // open / close
    $open.on("click", (e) => {
        e.preventDefault();
        openDialog();
    });
    $close.on("click", (e) => {
        e.preventDefault();
        closeDialog();
    });
    // close when clicking backdrop only
    $dialog.on("click", function (e) {
        if (e.target === this) closeDialog();
    });

    // submit (AJAX)
    $form.on("submit", function (e) {
        e.preventDefault();
        $submit.prop("disabled", true).addClass("opacity-50").text("Sending...");

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
                showToast(res?.message || "Submitted successfully", "success");
                this.reset();
            })
            .fail((error) => {
                showToast(error.responseJSON?.message || "Submitted failed", "error");
            })
            .always(() => {
                $submit.prop("disabled", false).removeClass("opacity-50").text(txt);
            });
    });
});
