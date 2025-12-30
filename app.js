// =============================================
// ハンバーガーメニュー
// =============================================
$(function () {
    const $btn = $("#menu_btn");
    const $panel = $("#menu_panel");

    function closeMenu() {
        $panel.prop("hidden", true);
        $btn.attr("aria-expanded", "false");
    }

    function toggleMenu() {
        const isOpen = !$panel.prop("hidden");
        if (isOpen) closeMenu();
        else {
            $panel.prop("hidden", false);
            $btn.attr("aria-expanded", "true");
        }
    }

    $btn.on("click", function(e) {
        e.stopPropagation();
        toggleMenu();
    });

    $panel.on("click", function(e) {
        e.stopPropagation();
    });

    $(document).on("click", function() {
        closeMenu();
    });

    $(document).on("keydown", function(e) {
        if (e.key === "Escape") closeMenu();
    });
});

// =============================================
// 検索結果カード作物・病害虫一覧 閉じる処理
// =============================================
$(function() {
    $(document).on("click", ".card_detail .detail_body", function(){
        const $details = $(this).closest("details");
        if ($details.prop("open")) {
            $details.prop("open", false);
        }
    });
});