$(function () {
    const info = $('#info-alert');
    const btnClose = $('#close-info');

    btnClose.on('click', function() {
        info.addClass("hidden");
    })
});