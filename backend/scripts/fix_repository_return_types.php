<?php

declare(strict_types=1);

/**
 * 修復 Repository 層 PDO fetchAll 回傳型別問題
 *
 * 根據 Context7 MCP 指導原則，使用 Dynamic Method Return Type Extensions 模式
 * 為 PDO fetchAll 方法添加明確的型別註解
 */

$repositoryPath = '/var/www/html/app/Infrastructure/Repositories/Statistics/PostStatisticsRepository.php';

if (!file_exists($repositoryPath)) {
    echo "檔案不存在: $repositoryPath\n";
    exit(1);
}

$content = file_get_contents($repositoryPath);

// 修復模式：為各種 PDO fetchAll 調用添加型別註解
$fixes = [
    // getViewTrendsByPeriod
    [
        'pattern' => '/(\$stmt->execute\(\[\s*\'start_date\' => \$period->startDate->format\(\'Y-m-d H:i:s\'\),\s*\'end_date\' => \$period->endDate->format\(\'Y-m-d H:i:s\'\),\s*\]\);\s*)(return \$stmt->fetchAll\(PDO::FETCH_ASSOC\) \?\: \[\];)(\s*\} catch \(PDOException \$e\) \{\s*throw new RuntimeException\(\s*"取得瀏覽量趨勢失敗:)/s',
        'replacement' => '$1/** @var array<array{date: string, view_count: int, unique_views: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return $result;$3'
    ],
    // getMostViewedPostByPeriod
    [
        'pattern' => '/(\$stmt->execute\(\[\s*\'start_date\' => \$period->startDate->format\(\'Y-m-d H:i:s\'\),\s*\'end_date\' => \$period->endDate->format\(\'Y-m-d H:i:s\'\),\s*\]\);\s*)(return \$stmt->fetch\(PDO::FETCH_ASSOC\) \?\: null;)(\s*\} catch \(PDOException \$e\) \{\s*throw new RuntimeException\(\s*"取得最熱門文章失敗:)/s',
        'replacement' => '$1/** @var array{id: int, title: string, views: int, created_at: string}|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;$3'
    ],
    // getNewPostsStatsByPeriod
    [
        'pattern' => '/(\$stmt->execute\(\[\s*\'start_date\' => \$period->startDate->format\(\'Y-m-d H:i:s\'\),\s*\'end_date\' => \$period->endDate->format\(\'Y-m-d H:i:s\'\),\s*\]\);\s*)(return \$stmt->fetch\(PDO::FETCH_ASSOC\) \?\: null;)(\s*\} catch \(PDOException \$e\) \{\s*throw new RuntimeException\(\s*"取得新文章統計失敗:)/s',
        'replacement' => '$1/** @var array{total_new_posts: int, total_views: int, avg_views_per_post: float}|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;$3'
    ]
];

foreach ($fixes as $fix) {
    $newContent = preg_replace($fix['pattern'], $fix['replacement'], $content);
    if ($newContent !== null && $newContent !== $content) {
        $content = $newContent;
        echo "✅ 已應用修復模式\n";
    }
}

// 簡單的字串替換模式
$simpleReplacements = [
    // getViewsDistributionByPeriod
    'return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得瀏覽量分布統計失敗:' => '/** @var array<array{range: string, count: int, percentage: float}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得瀏覽量分布統計失敗:',

    // getStatisticsTrends
    'return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得統計趨勢失敗:' => '/** @var array<array{date: string, post_count: int, view_count: int, unique_views: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得統計趨勢失敗:',
];

foreach ($simpleReplacements as $search => $replace) {
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        echo "✅ 已修復簡單替換模式\n";
    }
}

file_put_contents($repositoryPath, $content);

echo "✅ PostStatisticsRepository 型別修復完成\n";
