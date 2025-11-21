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

        // not show other description except current one
        $(".detail-row").not($row).addClass("hidden");

        $row.toggleClass("hidden");

        $btn.attr("aria-expanded", !$row.hasClass("hidden"));
    });

    // open first description
    // $(".toggle-row").first().trigger("click");
});