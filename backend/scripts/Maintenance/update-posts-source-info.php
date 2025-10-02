<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Maintenance;

/**
 * 文章來源資訊更新腳本
 *
 * 此腳本用於更新現有文章的來源資訊，設定預設值為 'web'
 * 可以在需要時手動執行，具有以下特點：
 *
 * 1. 安全性：只更新尚未設定來源的文章
 * 2. 可重複執行：不會重複更新已有來源資訊的文章
 * 3. 驗證機制：提供詳細的統計資訊和驗證結果
 * 4. 備份提醒：執行前提供備份建議
 *
 * 使用方式：
 * cd backend && php scripts/update-posts-source-info.php
 */

// 確認是否在正確的目錄執行
if (!file_exists('phinx.php')) {
    echo "❌ 錯誤：請在 backend 目錄中執行此腳本\n";
    echo "正確用法： cd backend && php scripts/update-posts-source-info.php\n";
    exit(1);
}

// 載入必要的檔案
require_once __DIR__ . '/../../vendor/autoload.php';

// 載入環境配置
$config = require 'phinx.php';
$dbConfig = $config['environments']['development'];

// 建立資料庫連線
try {
    $dsn = "sqlite:" . $dbConfig['name'];
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ 資料庫連線成功：{$dbConfig['name']}\n\n";
} catch (PDOException $e) {
    echo "❌ 資料庫連線失敗：" . $e->getMessage() . "\n";
    exit(1);
}

/**
 * 執行 SQL 查詢並回傳結果
 */
function executeQuery(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 執行 SQL 更新並回傳影響的行數
 */
function executeUpdate(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

// 顯示執行前資訊
echo "📋 文章來源資訊更新腳本\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// 檢查目前的資料狀況
echo "🔍 檢查目前的資料狀況...\n";

$totalPostsSql = "SELECT COUNT(*) as count FROM posts";
$totalPosts = executeQuery($pdo, $totalPostsSql)[0]['count'];

$postsWithoutSourceSql = "
    SELECT COUNT(*) as count
    FROM posts
    WHERE creation_source IS NULL OR creation_source = ''
";
$postsWithoutSource = executeQuery($pdo, $postsWithoutSourceSql)[0]['count'];

$sourceDistributionSql = "
    SELECT
        CASE
            WHEN creation_source IS NULL OR creation_source = '' THEN 'NULL/空值'
            ELSE creation_source
        END as source,
        COUNT(*) as count
    FROM posts
    GROUP BY creation_source
    ORDER BY creation_source
";
$sourceDistribution = executeQuery($pdo, $sourceDistributionSql);

echo "📊 目前狀況統計：\n";
echo "  - 總文章數：{$totalPosts}\n";
echo "  - 未設定來源：{$postsWithoutSource}\n";
echo "  - 來源分佈：\n";

foreach ($sourceDistribution as $row) {
    echo "    * {$row['source']}: {$row['count']} 筆\n";
}

echo "\n";

// 如果沒有需要更新的記錄，提早結束
if ($postsWithoutSource == 0) {
    echo "✅ 所有文章都已正確設定來源資訊，無需更新\n";
    echo "🎉 任務完成！\n";
    exit(0);
}

// 提供備份建議
echo "⚠️  重要提醒：\n";
echo "   此腳本將會更新 {$postsWithoutSource} 筆文章的來源資訊\n";
echo "   建議在執行前先備份資料庫\n\n";

echo "是否繼續執行更新？(y/N): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'y') {
    echo "❌ 使用者取消操作\n";
    exit(0);
}

// 開始更新程序
echo "\n🚀 開始更新文章來源資訊...\n";

try {
    // 開始交易
    $pdo->beginTransaction();

    // 執行更新
    $updateSql = "
        UPDATE posts
        SET
            creation_source = 'web',
            creation_source_detail = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE
            creation_source IS NULL
            OR creation_source = ''
    ";

    $updatedCount = executeUpdate($pdo, $updateSql);

    // 驗證更新結果
    $verificationSql = "
        SELECT COUNT(*) as count
        FROM posts
        WHERE creation_source IS NULL OR creation_source = ''
    ";
    $remainingUnsourcedCount = executeQuery($pdo, $verificationSql)[0]['count'];

    if ($remainingUnsourcedCount > 0) {
        throw new Exception("驗證失敗：仍有 {$remainingUnsourcedCount} 筆文章未正確設定來源");
    }

    // 提交交易
    $pdo->commit();

    echo "✅ 成功更新 {$updatedCount} 筆文章的來源資訊\n\n";

    // 顯示更新後的統計資訊
    echo "📊 更新後的來源分佈統計：\n";
    $newDistribution = executeQuery($pdo, $sourceDistributionSql);

    foreach ($newDistribution as $row) {
        echo "  - {$row['source']}: {$row['count']} 筆\n";
    }

    echo "\n✅ 驗證完成：所有文章都已正確設定來源資訊\n";
    echo "🎉 任務完成！\n";

} catch (Exception $e) {
    // 回滾交易
    $pdo->rollback();

    echo "❌ 更新失敗：" . $e->getMessage() . "\n";
    echo "🔄 已回滾所有變更\n";
    exit(1);
}
