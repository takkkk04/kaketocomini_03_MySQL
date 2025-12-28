<?php
$filePath = __DIR__ . '/data/data.json';
//JSONを配列に変換する
$list = [];
if (file_exists($filePath)) {
    $json = file_get_contents($filePath);
    $decoded = json_decode($json, true);
    if (is_array($decoded)){
        $list = $decoded;
    }
}

//JSONから受け取る
$category = $_GET["category"] ?? "殺虫剤";
$crop = trim($_GET["crop"] ?? "");
$target = trim($_GET["target"] ?? "");
$sort = $_GET["sort"] ?? "score_desc";

// =============================================
// 作物、病害虫プルダウン
// =============================================
$cropSet = [];
$targetSet = [];

//foreach(配列 as 変数)は配列の中身を順番に取り出す
//$listは農薬リスト、$pはその中のひとつの農薬データ
foreach ($list as $p) {
    //cropが配列じゃなかったら空配列で返す、$cropsは配列になる
    $crops = is_array($p["crop"] ?? null) ? $p["crop"] : [];
    //$cropsは作物リスト、$cはその中のひとつの作物名
    //$cを文字列に変換して前後の空白を削る
    //$cが空じゃなければ$cropSetの配列の中に追加する、重複は上書きされるので勝手に消える
    foreach ($crops as $c) {
        $c = trim((string)$c);
        if ($c !== "") $cropSet[$c] = true;
    }

    //targetでも同じ処理
    $targets = is_array($p["target"] ?? null) ? $p["target"] : [];
    foreach ($targets as $t) {
        $t = trim((string)$t);
        if ($t !== "") $targetSet[$t] = true;
    }
}

//array_keys()は配列のキーだけ取り出す
$cropOptions = array_keys($cropSet);
$targetOptions = array_keys($targetSet);

//ざっくり文字順ソート
sort($cropOptions, SORT_STRING);
sort($targetOptions, SORT_STRING);

// =============================================
// 絞り込み、検索結果用の配列 $filtered を作る
// =============================================

//array_values()は配列のキーを0から振り直す
//array_filter(A,B)はAの中のBだけ残す
//use($aaa, $bbb)は外で定義した変数を関数の中で使えるようにする
$filtered = array_values(array_filter($list, function($p) use ($category, $crop, $target) {

    //カテゴリーが違ったらfalseを返す
    if (($p["category"] ?? "") !== $category) return false;

    // 作物が指定されていたらチェック
    if ($crop !== "") {
        //is_array()は配列かどうか調べる関数、配列ならtrue
        //三項演算子… 条件 ? A : B (条件がtrueならA、falseならB)
        //null合体演算子… A ?? B (Aが存在すればA、なければB)
        //配列じゃなかったら空配列で返す
        $crops = is_array($p["crop"] ?? null) ? $p["crop"] : [];
        //in_array(A,B,C)はBの中にAがあるか調べる、Cは厳密に調べる(trueなら型も見る)
        //!in_array()はBの中にAがなかったらtrue
        if (!in_array($crop, $crops, true)) return false;
    }

    if ($target !== "") {
        $targets = is_array($p["target"] ?? null) ? $p["target"] : [];
        if (!in_array($target, $targets, true)) return false;
    }

    return true;
}));

// =============================================
// 並び替え（スコア順、あいうえお順ソート）
// =============================================
if ($sort === "name_asc") {
    //あいうえお順ソート
    //usort(A,B)はAをBの条件でソートする
    usort($filtered, function($a, $b){
        $na = (string)($a["name"] ?? "");
        $nb = (string)($b["name"] ?? "");
    //b <=> a にすると降順ソートになる
        return strcmp($na, $nb);
    });
} else {
    //スコア順ソート（こっちがデフォルト）
    usort($filtered, function($a, $b){
        //スコアを整数(int)でふたつ取り出す、なければ0
        $sa = (int)($a["score"] ?? 0);
        $sb = (int)($b["score"] ?? 0);
        //a <=> b 宇宙船演算子、aが小さいと-1、同じなら0、大きいと1を返す
        //b <=> a にすると降順ソートになる
        return $sb <=> $sa;
    });
}

$count = count($filtered);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カケトコ_mini</title>
    <link rel="stylesheet" href="./css/reset.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <header class="app_header">
        <h1>カケトコ mini</h1>
        <a href="./admin/admin.php" class="admin_link">管理画面へ</a>
    </header>

    <main class="app_main">
        <section class="search_section">
            <h2>検索条件</h2>

            <form id="search_form" method="GET" action="">
                <div class="form_row">
                    <label for="category">カテゴリ</label>
                    <select name="category" id="category">
                        <option value="殺虫剤">殺虫剤</option>
                        <option value="殺菌剤">殺菌剤</option>
                        <option value="除草剤">除草剤</option>
                    </select>
                </div>

                <div class="form_row">
                    <label for="crop">作物名</label>
                    <select name="crop" id="crop">
                        <option value="">指定なし</option>
                        <!-- 作物名プルダウン -->
                        <?php foreach ($cropOptions as $c): ?>
                            <!-- <select>のプルダウンの中身<option>をHTMLで作っている -->
                            <!-- htmlspecialchars()は安全装置,記号とかをエスケープする -->
                            <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, "UTF-8"); ?>"
                                <?php 
                                // selectedがあると検索ボタン押しても選択状態になる
                                echo($crop === $c) ? "selected" : ""; ?>>
                                <?php 
                                //<option>トマト</option>のトマトの部分
                                echo htmlspecialchars($c, ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form_row">
                    <label for="target">病害虫</label>
                    <select name="target" id="target">
                        <option value="">指定なし</option>
                        <?php foreach ($targetOptions as $t): ?>
                            <option value="<?php echo htmlspecialchars($t, ENT_QUOTES, "UTF-8"); ?>"
                                <?php echo($target === $t) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($t, ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form_row_btn">
                    <button type="submit" id="search_btn">検索</button>
                    <button type="button" id="reset_btn">リセット</button>
                </div>

            </form>
        </section>

        <section class="result_section">
            
            <div class="result_header">
                <div class="result_left">
                    <h2>検索結果</h2>
                    <span id="result_count"><?php echo (int)$count ?>件</span>
                </div>

                <div class="result_right">
                    <label for="sort" class="sort_label"></label>
                    <select name="sort" id="sort">
                        <option value="score_desc" <?php echo ($sort ==="score_desc") ? "selected" : ""; ?>>カケトコスコア順</option>
                        <option value="name_asc" <?php echo ($sort === "name_asc") ? "selected" : "";?>>名前順</option>
                    </select>
                </div>
            </div>

            <!-- 検索結果表示エリア -->
            <div id="result_list" class="result_list">
                <?php if ($count === 0): ?>
                    <p>該当する農薬がありません。</p>
                <?php else: ?>
                    <?php foreach ($filtered as $i =>$p): ?>
                        <?php 
                            $pid = $p["shopify_id"] ?? "";
                            $boxId = "buy-" . $i;
                            ?>
                            
                        <article class="result_card">
                            <div class="card_title">
                                <?php echo htmlspecialchars($p["name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                            </div>
                            <div class="card_mid">
                                <div class="card_left">
                                    <div 
                                        class="shopify_img shopify_cell" 
                                        data-product-id="<?php echo htmlspecialchars((string)($p["shopify_id"] ?? ""), ENT_QUOTES, "UTF-8"); ?>">
                                    </div>
                                </div>

                                <div class="card_specs">
                                    <div class="spec_row">
                                        <span class="spec_label">希釈倍率</span>
                                        <span class="spec_val">
                                            <?php
                                                echo isset($p["magnification"])
                                                    ? htmlspecialchars((string)$p["magnification"], ENT_QUOTES, "UTF-8") . "倍" : "";
                                            ?>
                                        </span>
                                    </div>
                                    <div class="spec_row">
                                        <span class="spec_label">使用回数</span>
                                        <span class="spec_val">
                                            <?php
                                                echo isset($p["times"])
                                                    ? htmlspecialchars((string)$p["times"], ENT_QUOTES, "UTF-8") . "回" : ""; 
                                            ?>
                                        </span>
                                    </div>
                                    <div class="spec_row">
                                        <span class="spec_label">収穫前日数</span>
                                        <span class="spec_val">
                                            <?php
                                                echo isset($p["interval"])
                                                    ? htmlspecialchars((string)$p["interval"], ENT_QUOTES, "UTF-8") . "日前まで" : "";
                                            ?>
                                        </span>
                                    </div>
                                    <div class="spec_row">
                                        <span class="spec_label">カケトコスコア</span>
                                        <span class="spec_val"><?php echo htmlspecialchars((string)($p["score"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card_bottom">
                                <div class="shopify_price"></div>
                                <div class="shopify_btn"></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </section>
    </main>

    

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="module" src="shopify.js"></script>
</body>
</html>