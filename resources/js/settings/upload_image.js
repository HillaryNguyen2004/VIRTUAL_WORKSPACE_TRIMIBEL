import { showToast } from "../show-toast";

$(function () {
    const $input = $("#avatar");
    const $chooseBtn = $("#choose-avatar");
    const $uploadBtn = $("#upload-btn");
    const $preview = $("#avatar-preview");
    const txt = $uploadBtn.text();
    let blobUrl;

    // open file dialog
    $chooseBtn.on("click", function (e) {
        e.preventDefault();
        $input.trigger("click");
    });

    // validate + preview when user picked a file
    $input.on("change", function () {
        const file = this.files && this.files[0];
        if (!file) return;

        // Prefer MIME; fall back to extension
        const okMime = /^image\/(png|jpeg)$/i.test(file.type);
        const okExt = /\.(png|jpe?g)$/i.test(file.name);
        if (!(okMime || okExt)) {
            showToast("Please select a PNG or JPG image", "error");
            $input.val("");
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            // 2MB
            showToast("Max size: 2MB", "error");
            $input.val("");
            return;
        }

        if (blobUrl) URL.revokeObjectURL(blobUrl);
        blobUrl = URL.createObjectURL(file);
        $preview.attr("src", blobUrl); // instant preview
        $uploadBtn.prop("disabled", false);
    });

    // upload via AJAX (no page reload)
    $("#avatar-form").on("submit", function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        $.ajax({
            url: this.action,
            method: "POST",
            data: fd,
            processData: false,
            contentType: false,
            headers: { Accept: "application/json" },
        })
            .done((res) => {
                showToast(res.message || "Avatar updated", "success");
                if (res.url) $preview.attr("src", res.url); // final stored URL
                $uploadBtn.prop("disabled", true).addClass("opacity-50").text("Saving...");
                $input.val("");
            })
            .fail((error) => {
                const msg = error.responseJSON?.message || "Upload failed";
                showToast(msg, "error");
            })
            .always(() => {
                $uploadBtn.prop("disabled", false).removeClass("opacity-50").text(txt);
            });

        // cleanup any previous blob URL after submit
        if (blobUrl) {
            URL.revokeObjectURL(blobUrl);
            blobUrl = undefined;
        }
    });
});
