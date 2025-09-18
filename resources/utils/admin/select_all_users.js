$(function () {
    const $chk = $("#send_to_all");
    const $sel = $("#users");

    function selectAll(state) {
        $sel.find("option").prop("selected", state);
        // If you're using Select2/Choices/etc., keep the UI in sync:
        $sel.trigger("change");
    }

    // On first load
    if ($chk.is(":checked")) selectAll(true);

    // Toggle all on checkbox change
    $chk.on("change", function () {
        selectAll(this.checked);
    });

    // Keep the checkbox state in sync if user manually changes selection
    function syncCheckbox() {
        const total = $sel[0].options.length;
        const selected = $sel.find("option:selected").length;
        $chk.prop("checked", selected === total).prop(
            "indeterminate",
            selected > 0 && selected < total
        );
    }
    $sel.on("change", syncCheckbox);
    syncCheckbox();
});
