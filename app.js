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