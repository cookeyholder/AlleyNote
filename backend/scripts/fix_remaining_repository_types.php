<?php

declare(strict_types=1);

/**
 * 修復 PostStatisticsRepository 剩餘的 PDO 回傳型別問題
 */

$repositoryPath = '/var/www/html/app/Infrastructure/Repositories/Statistics/PostStatisticsRepository.php';

if (!file_exists($repositoryPath)) {
    echo "檔案不存在: $repositoryPath\n";
    exit(1);
}

$content = file_get_contents($repositoryPath);

// 定義需要修復的方法及其返回型別
$methodFixes = [
    'getViewsDistributionByPeriod' => 'array<array{range: string, count: int, percentage: float}>',
    'getPostActivityHeatmapByPeriod' => 'array<array{date: string, hour: int, activity_count: int}>',
    'getMostActiveAuthorsByPeriod' => 'array<array{user_id: int, username: string, post_count: int, total_views: int}>',
    'getTagUsageStatsByPeriod' => 'array<array{tag: string, usage_count: int, post_count: int}>',
    'getPostsByPublishTime' => 'array<array{publish_hour: string, publish_day: string, avg_views: float}>',
    'getPostHistoricalPerformance' => 'array<array{date: string, daily_views: int}>',
];

foreach ($methodFixes as $method => $returnType) {
    // 為 fetchAll 方法添加型別註解
    $pattern = '/(\$stmt->execute\(\[[\s\S]*?\]\);\s*)(return \$stmt->fetchAll\(PDO::FETCH_ASSOC\) \?\: \[\];)(\s*\} catch)/';
    $replacement = '$1/** @var ' . $returnType . ' $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return $result;$3';

    $newContent = preg_replace($pattern, $replacement, $content, 1);
    if ($newContent !== null && $newContent !== $content) {
        $content = $newContent;
        echo "✅ 已修復 $method 方法\n";
    }
}

// 特殊處理 getPostStatsByPeriod (使用 fetch 而非 fetchAll)
$pattern = '/(\$stmt->execute\(\[\s*\'post_id\' => \$postId,[\s\S]*?\]\);\s*)(return \$stmt->fetch\(PDO::FETCH_ASSOC\) \?\: \[\];)(\s*\} catch)/';
$replacement = '$1/** @var array{views: int, comments: int, likes: int, shares: int, source: string}|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [];$3';

$newContent = preg_replace($pattern, $replacement, $content, 1);
if ($newContent !== null && $newContent !== $content) {
    $content = $newContent;
    echo "✅ 已修復 getPostStatsByPeriod 方法\n";
}

file_put_contents($repositoryPath, $content);

echo "✅ PostStatisticsRepository 剩餘型別修復完成\n";
