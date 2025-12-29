<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . "/db.php";

//確認用、あとで消す
$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM pesticides_base");
$row = $stmt->fetch();
$baseCount = (int)($row["cnt"] ?? 0);

// =============================================
// 作物・病害虫プルダウン
// =============================================
$category = $_GET["category"] ?? "殺虫剤";
$crop = trim($_GET["crop"] ?? "");
$target = trim($_GET["target"] ?? "");

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
// DBから検索結果取得
// =============================================
$sql = "SELECT * 
    FROM pesticides_rules 
    WHERE category = :category 
        AND (:crop1 = '' OR crop = :crop2)
        AND (:target1 = '' OR target = :target2)
    ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":category" => $category,
    ":crop1" => $crop,
    ":crop2" => $crop,
    ":target1" => $target,
    ":target2" => $target,
]);

$filtered = $stmt->fetchAll();
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

    <p>DB接続完了 / pesticides_base 件数: <?php echo $baseCount; ?></p>

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