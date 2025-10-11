<?php

declare(strict_types=1);

/**
 * 時區資料遷移腳本
 * 
 * 將現有的時間資料轉換為 RFC3339 格式（UTC）
 * 執行方式：php scripts/migrate-timezone-data.php
 */

// 載入環境變數
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$dbPath = $_ENV['DB_DATABASE'] ?? __DIR__ . '/../database/alleynote.sqlite3';

echo "時區資料遷移腳本\n";
echo "==================\n\n";

try {
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 備份資料庫
    echo "1. 備份資料庫...\n";
    $backupPath = $dbPath . '.backup.' . date('YmdHis');
    if (!copy($dbPath, $backupPath)) {
        throw new Exception("無法備份資料庫");
    }
    echo "   ✓ 備份完成: {$backupPath}\n\n";
    
    // 檢查現有時間格式
    echo "2. 分析現有時間格式...\n";
    $stmt = $pdo->query("SELECT id, created_at, updated_at, publish_date FROM posts LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $sample) {
        echo "   範例 #{$sample['id']}:\n";
        echo "     created_at: {$sample['created_at']}\n";
        echo "     updated_at: {$sample['updated_at']}\n";
        echo "     publish_date: {$sample['publish_date']}\n";
    }
    echo "\n";
    
    // 檢查格式是否已經是 RFC3339
    $firstSample = $samples[0] ?? null;
    if ($firstSample && (strpos($firstSample['created_at'], 'T') !== false)) {
        echo "   ✓ 時間格式已經是 RFC3339 格式，無需遷移\n\n";
        exit(0);
    }
    
    // 開始遷移
    echo "3. 開始遷移時間格式到 RFC3339 (UTC)...\n";
    $pdo->beginTransaction();
    
    // 假設現有時間是 UTC+8（Asia/Taipei）
    $sourceTimezone = 'Asia/Taipei';
    echo "   假設來源時區: {$sourceTimezone}\n\n";
    
    // 遷移 posts 表
    echo "   遷移 posts 表...\n";
    $posts = $pdo->query("SELECT id, created_at, updated_at, publish_date FROM posts")->fetchAll(PDO::FETCH_ASSOC);
    $updateStmt = $pdo->prepare("UPDATE posts SET created_at = :created_at, updated_at = :updated_at, publish_date = :publish_date WHERE id = :id");
    
    $migratedCount = 0;
    foreach ($posts as $post) {
        try {
            // 轉換時間
            $createdAt = convertToRFC3339($post['created_at'], $sourceTimezone);
            $updatedAt = convertToRFC3339($post['updated_at'], $sourceTimezone);
            $publishDate = $post['publish_date'] ? convertToRFC3339($post['publish_date'], $sourceTimezone) : null;
            
            $updateStmt->execute([
                ':id' => $post['id'],
                ':created_at' => $createdAt,
                ':updated_at' => $updatedAt,
                ':publish_date' => $publishDate,
            ]);
            
            $migratedCount++;
        } catch (Exception $e) {
            echo "     ⚠️  警告: 文章 #{$post['id']} 遷移失敗: {$e->getMessage()}\n";
        }
    }
    
    echo "   ✓ 已遷移 {$migratedCount} 篇文章\n\n";
    
    // 提交事務
    $pdo->commit();
    
    echo "4. 驗證遷移結果...\n";
    $stmt = $pdo->query("SELECT id, created_at, updated_at, publish_date FROM posts LIMIT 3");
    $newSamples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($newSamples as $sample) {
        echo "   文章 #{$sample['id']}:\n";
        echo "     created_at: {$sample['created_at']}\n";
        echo "     updated_at: {$sample['updated_at']}\n";
        echo "     publish_date: " . ($sample['publish_date'] ?: 'NULL') . "\n";
        
        // 驗證格式
        if (strpos($sample['created_at'], 'T') !== false && strpos($sample['created_at'], 'Z') !== false) {
            echo "     ✓ 格式正確\n";
        } else {
            echo "     ⚠️  格式可能不正確\n";
        }
    }
    
    echo "\n===================\n";
    echo "✓ 遷移完成！\n";
    echo "備份檔案: {$backupPath}\n";
    echo "如需回復，請執行: cp {$backupPath} {$dbPath}\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ 錯誤: {$e->getMessage()}\n";
    echo "已回滾所有變更\n";
    exit(1);
}

/**
 * 將時間轉換為 RFC3339 UTC 格式
 */
function convertToRFC3339(?string $dateTime, string $sourceTimezone): ?string
{
    if (!$dateTime) {
        return null;
    }
    
    try {
        // 嘗試解析時間（假設為來源時區）
        $dt = new DateTimeImmutable($dateTime, new DateTimeZone($sourceTimezone));
        
        // 轉換為 UTC
        $dt = $dt->setTimezone(new DateTimeZone('UTC'));
        
        // 返回 RFC3339 格式
        return $dt->format('Y-m-d\TH:i:s\Z');
    } catch (Exception $e) {
        throw new Exception("無法轉換時間 '{$dateTime}': {$e->getMessage()}");
    }
}
