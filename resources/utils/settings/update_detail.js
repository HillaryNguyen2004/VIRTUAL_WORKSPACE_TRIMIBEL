import { showToast } from "../show-toast";

$(function () {
    $("#detail-form").on("submit", function (e) {
        e.preventDefault(); // no reload

        const $btn = $("#update-detail-btn");
        const txt = $btn.text();
        $btn.prop("disabled", true).addClass("opacity-50").text("Saving...");

        $.ajax({
            url: this.action,
            method: "POST",
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { Accept: "application/json" },
        })
            .done((res) => {
                showToast(res?.message || "Updated detail successfully", "success", 5000);
                // window.location.reload();
            })
            .fail((error) => {
                const msg = error.responseJSON?.message || "Updated failed";
                showToast(msg, "error");
            })
            .always(() => {
                $btn.prop("disabled", false).removeClass("opacity-50").text(txt);
            });
    });
});
