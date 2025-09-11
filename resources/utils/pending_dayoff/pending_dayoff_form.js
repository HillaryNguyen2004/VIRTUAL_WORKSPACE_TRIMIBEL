import { showToast } from "../show-toast.js";

$(function() {
    const $acceptedForm = $("#accepted-form");
    const $acceptedBtn = $("#accepted-btn");
    const $rejectedForm = $("#rejected-form");
    const $rejectedBtn = $("#rejected-btn");

    $acceptedForm.on('submit', function (e) {
        console.log("accepted")
        e.preventDefault();
        $acceptedBtn.prop("disabled", true);

        const fd = new FormData(this);

        $.ajax({
            url: this.action,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .done((res) => {
                showToast(res?.message || "Accepted successfully", "success");
            })
            .fail((error) => {
                showToast(error.responseJSON?.message || "Accepted failed", "error");
            })
            .always(() => {
                $acceptedBtn.prop("disabled", false);
            });
    });

    $rejectedForm.on('submit', function (e) {
        e.preventDefault();
        $rejectedBtn.prop("disabled", true);

        const fd = new FormData(this);

        $.ajax({
            url: this.action,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .done((res) => {
                showToast(res?.message || "Rejected successfully", "success");
            })
            .fail((error) => {
                showToast(error.responseJSON?.message || "Rejected failed", "error");
            })
            .always(() => {
                $rejectedBtn.prop("disabled", false);
            });
    });
});