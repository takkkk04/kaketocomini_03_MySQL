<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . "/db.php";

// =============================================
// 作物・病害虫・使用方法プルダウン
// =============================================
$category = $_GET["category"] ?? "殺虫剤";
$crop = trim($_GET["crop"] ?? "");
$target = trim($_GET["target"] ?? "");
$method = trim($_GET["method"] ?? "散布");
$methodGroups = [
    "散布" => ["散布"],
    "全面土壌散布" => ["全面土壌散布"],
    "常温煙霧" => ["常温煙霧"],
    "灌注" => ["灌注", "株元灌注", "灌水ﾁｭｰﾌﾞを用いた灌注処理", "苗床灌注"],
    "浸漬" => ["120分間鱗片浸漬", "30分間種球浸漬", "30分間苗浸漬"],
    "ドローン散布" => ["無人航空機による散布"],
    "その他" => ["主幹から株元に散布", "主幹部に吹きつけ", "散布､但し花穂の発生期にはﾏﾙﾁﾌｨﾙﾑ被覆により散布液が直接花穂に飛散しない状態で使用する｡",
        "木屑排出孔を中心に薬液が滴るまで樹幹注入", "本剤1g当り水1mLの割合で混合し､主幹から主枝の粗皮を環状に剥いだ部分に塗布する｡",
        "植溝内土壌散布", "樹幹散布", "添加"]
];
$methodLabels = ["散布", "灌注", "ドローン散布", "全面土壌散布", "常温煙霧", "浸漬", "その他"];

//作物プルダウン
$stmt = $pdo->prepare(
    "SELECT DISTINCT crop 
    FROM pesticides_rules 
    WHERE category = :category AND crop <> '' 
    ORDER BY crop ASC
    ");
$stmt->execute([":category" => $category]);
$cropOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

//病害虫プルダウン
$stmt = $pdo->prepare(
    "SELECT DISTINCT target 
    FROM pesticides_rules 
    WHERE category = :category AND target <> '' 
    ORDER BY target ASC
    ");
$stmt->execute([":category" => $category]);
$targetOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);


// =============================================
// DBから検索結果取得、作物・病害虫・使用方法絞り込み処理
// =============================================
$selectedMethods = $methodGroups[$method];
$in = [];
$methodParams = [];
foreach ($selectedMethods as $i => $m) {
    $key = ":m{$i}";
    $in[] = $key;
    $methodParams[$key] = $m;
}
$methodInSql = implode(",", $in);

$sql = "SELECT r.*, b.shopify_id
    FROM (
        SELECT 
            registration_number,
            MIN(id) AS pick_id
        FROM pesticides_rules
        WHERE 
            category = :category
            AND (:crop1 = '' OR crop = :crop2)
            AND (:target1 = '' OR target = :target2)
            AND method IN ($methodInSql)
        GROUP BY registration_number
    ) AS picked
    JOIN pesticides_rules AS r
        ON r.id = picked.pick_id
    LEFT JOIN pesticides_base AS b
        ON b.registration_number = r.registration_number
    ORDER BY r.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":category" => $category,
    ":crop1" => $crop,
    ":crop2" => $crop,
    ":target1" => $target,
    ":target2" => $target,
] + $methodParams);

$filtered = $stmt->fetchAll();
$count = count($filtered);

//作物、病害虫一覧取得
$cropListStmt = $pdo->prepare(
    "SELECT DISTINCT crop
    FROM pesticides_rules
    WHERE registration_number = :reg
        AND category = :category
        AND crop <> ''
    ORDER BY crop ASC"
);

$targetListStmt = $pdo->prepare(
    "SELECT DISTINCT target
    FROM pesticides_rules
    WHERE registration_number = :reg
        AND category = :category
        AND target <> ''
    ORDER BY target ASC"
);

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
        <h1 class="app_title">
            <a href="./index.php">カケトコ mini</a>
        </h1>

        <div class="header_menu">
            <button type="button" id="menu_btn" class="menu_btn" aria-expanded="false" aria-controls="menu_panel">
                <span class="menu_icon" aria-hidden="true"></span>
                <span class="sr_only">メニュー</span>
            </button>

            <div id="menu_panel" class="menu_panel" hidden>
                <a href="./admin/admin.php" class="admin_item">管理画面</a>
            </div>
        </div>
    </header>

    <main class="app_main">
        <section class="search_section">
            <h2>検索条件</h2>

            <form id="search_form" method="GET" action="">
                <div class="form_row">
                    <label for="category">カテゴリ</label>
                        <div class="category_picker" role="radiogroup" aria-label="カテゴリ">
                            <label class="cat_item">
                                <input type="radio" name="category" value="殺虫剤" <?php echo ($category === "殺虫剤") ? "checked" : ""; ?>>
                                <span class="cat_btn">
                                    <img src="image/icon_butterfly.png" alt="">
                                    <span class="cat_text">殺虫剤</span>
                                </span>
                            </label>

                            <label class="cat_item">
                                <input type="radio" name="category" value="殺菌剤" <?php echo ($category === "殺菌剤") ? "checked" : ""; ?>>
                                <span class="cat_btn">
                                    <img src="image/icon_virus.png" alt="">
                                    <span class="cat_text">殺菌剤</span>
                                </span>
                            </label>

                            <label class="cat_item">
                                <input type="radio" name="category" value="除草剤" <?php echo ($category === "除草剤") ? "checked" : ""; ?>>
                                <span class="cat_btn">
                                    <img src="image/icon_leaf.png" alt="">
                                    <span class="cat_text">除草剤</span>
                                </span>
                            </label>
                        </div>
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
                                echo($crop === $c) ? "selected" : ""; ?>
                            >
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
                        <!-- 病害虫プルダウン -->
                        <?php foreach ($targetOptions as $t): ?>
                            <option value="<?php echo htmlspecialchars($t, ENT_QUOTES, "UTF-8"); ?>"
                                <?php echo($target === $t) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($t, ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form_row">
                    <!-- 使用方法プルダウン、デフォルトは"散布" -->
                    <label for="method">使用方法</label>
                    <select name="method" id="method">
                        <?php foreach ($methodLabels as $m): ?>
                            <option value="<?php echo htmlspecialchars($m, ENT_QUOTES, "UTF-8"); ?>"
                                <?php echo ($method === $m) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($m, ENT_QUOTES, "UTF-8"); ?>
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
                            $pid = (string)($p["shopify_id"] ?? "");
                            $boxId = "buy-" . $i;
                            $reg = (string)($p["registration_number"] ?? "");
                            //カード内作物・病害虫一覧
                            $cropListStmt->execute([":reg" => $reg, ":category" => $category]);
                            $cropList = $cropListStmt->fetchAll(PDO::FETCH_COLUMN);
                            $targetListStmt->execute([":reg" => $reg, ":category" => $category]);
                            $targetList = $targetListStmt->fetchAll(PDO::FETCH_COLUMN);
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
                                                    ? htmlspecialchars((string)$p["magnification"], ENT_QUOTES, "UTF-8") : "";
                                            ?>
                                        </span>
                                    </div>

                                    <div class="spec_row">
                                        <span class="spec_label">使用回数</span>
                                        <span class="spec_val">
                                            <?php
                                                echo isset($p["times"])
                                                    ? htmlspecialchars((string)$p["times"], ENT_QUOTES, "UTF-8") : ""; 
                                            ?>
                                        </span>
                                    </div>

                                    <div class="spec_row">
                                        <span class="spec_label">収穫前日数</span>
                                        <span class="spec_val">
                                            <?php
                                                echo isset($p["timing"])
                                                    ? htmlspecialchars((string)$p["timing"], ENT_QUOTES, "UTF-8") : "";
                                            ?>
                                        </span>
                                    </div>

                                    <div class="spec_row">
                                        <span class="spec_label">使用方法</span>
                                        <span class="spec_val">
                                            <?php echo htmlspecialchars((string)$p["method"] ?? "", ENT_QUOTES, "UTF-8");?>
                                        </span>
                                    </div>

                                    <div class="spec_row">
                                        <span class="spec_label">カケトコスコア</span>
                                        <span class="spec_val">
                                            <?php echo htmlspecialchars((string)($p["score"] ?? ""), ENT_QUOTES, "UTF-8"); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="card_lists">
                                <details class="card_detail">
                                    <summary>登録作物(<?php echo count($cropList); ?>)</summary>
                                    <div class="detail_body">
                                        <?php if (count($cropList) ===0): ?>
                                            <p>なし</p>
                                        <?php else: ?>
                                            <ul>
                                                <?php foreach ($cropList as $cItem): ?>
                                                    <li><?php echo htmlspecialchars((string)$cItem, ENT_QUOTES, "UTF-8"); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </details>

                                <details class="card_detail">
                                    <summary>適用病害虫(<?php echo count($targetList); ?>)</summary>
                                    <div class="detail_body">
                                        <?php if (count($targetList) ===0): ?>
                                            <p>なし</p>
                                        <?php else: ?>
                                            <ul>
                                                <?php foreach ($targetList as $tItem): ?>
                                                    <li><?php echo htmlspecialchars((string)$tItem, ENT_QUOTES, "UTF-8"); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </details>

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
    <script src="app.js"></script>
</body>
</html>