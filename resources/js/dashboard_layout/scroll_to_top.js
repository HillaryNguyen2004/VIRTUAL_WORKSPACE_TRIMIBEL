$(function () {
    const $btn = $("#scroll-to-top-btn");
    if (!$btn.length) return;

    const $scroller = $("#main-scroll").length ? $("#main-scroll") : $(window);

    const scrollToTop = () => {
        if ($scroller.is($(window))) {
            $("html, body").animate({ scrollTop: 0 }, 400);
        } else {
            $scroller.animate({ scrollTop: 0 }, 400);
        }
    };

    const toggleVisibility = () => {
        const y = $scroller.scrollTop();
        if (y > 120) {
            $btn.stop(true, true).fadeIn(150);
        } else {
            $btn.stop(true, true).fadeOut(150);
        }
    };

    // Listen on the actual scroller
    $scroller.on("scroll", toggleVisibility);
    $btn.on("click", scrollToTop);

    // Initial state
    toggleVisibility();
});
