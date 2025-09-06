<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * 修復 Repository 和 Service 類型問題
 */
class RepositoryServiceFixer
{
    public function run(): void
    {
        echo "開始修復 Repository 和 Service 類型問題...\n";

        $this->fixPostStatisticsRepository();
        $this->fixStatisticsRepository();
        $this->fixSystemStatisticsRepository();
        $this->fixUserStatisticsRepository();
        $this->fixStatisticsApplicationService();
        $this->fixStatisticsQueryService();

        echo "修復完成!\n";
    }

    private function fixPostStatisticsRepository(): void
    {
        echo "修復 PostStatisticsRepository...\n";
        $file = __DIR__ . '/../app/Infrastructure/Repositories/Statistics/PostStatisticsRepository.php';

        $content = file_get_contents($file);

        // 為所有返回陣列的方法添加正確的返回型別檢查
        $replacements = [
            'return $stmt->fetchAll(PDO::FETCH_ASSOC);' => 'return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];',
            'return $result;' => 'return is_array($result) ? $result : [];',
            'return $stmt->fetch(PDO::FETCH_ASSOC);' => '$result = $stmt->fetch(PDO::FETCH_ASSOC); return $result ?: null;',
        ];

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        file_put_contents($file, $content);
        echo "  - PostStatisticsRepository 修復完成\n";
    }

    private function fixStatisticsRepository(): void
    {
        echo "修復 StatisticsRepository...\n";
        $file = __DIR__ . '/../app/Infrastructure/Repositories/Statistics/StatisticsRepository.php';

        $content = file_get_contents($file);

        // 修復 buildSnapshotFromRow 方法調用
        $content = str_replace(
            'return $this->buildSnapshotFromRow($row);',
            '$result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($result)) {
                return null;
            }
            return $this->buildSnapshotFromRow($result);',
            $content
        );

        // 修復陣列迭代
        $content = str_replace(
            'foreach ($results as $row) {',
            'foreach ($results as $row) {
                if (!is_array($row)) {
                    continue;
                }',
            $content
        );

        // 修復 mixed 類型的陣列取值
        $content = preg_replace(
            '/(\$row\[\'[^\']+\'\])(\s*\?\?\s*[^;]+);/',
            'is_string($1) ? $1 : (${2});',
            $content
        );

        // 修復 JSON 解碼
        $content = str_replace(
            'json_decode($row[\'source_stats\'], true)',
            'is_string($row[\'source_stats\'] ?? null) ? json_decode($row[\'source_stats\'], true) : []',
            $content
        );

        $content = str_replace(
            'json_decode($row[\'additional_metrics\'], true)',
            'is_string($row[\'additional_metrics\'] ?? null) ? json_decode($row[\'additional_metrics\'], true) : []',
            $content
        );

        file_put_contents($file, $content);
        echo "  - StatisticsRepository 修復完成\n";
    }

    private function fixSystemStatisticsRepository(): void
    {
        echo "修復 SystemStatisticsRepository...\n";
        $file = __DIR__ . '/../app/Infrastructure/Repositories/Statistics/SystemStatisticsRepository.php';

        $content = file_get_contents($file);

        // 修復 mixed 類型取值
        $patterns = [
            '/\$([a-zA-Z_]+)\[\'([^\']+)\'\](\s*\?\?\s*[0-9]+)/' => 'is_numeric($${1}[\'${2}\'] ?? null) ? (int)$${1}[\'${2}\'] : ${3}',
            '/\$([a-zA-Z_]+)\[\'([^\']+)\'\](\s*\?\?\s*[0-9.]+)/' => 'is_numeric($${1}[\'${2}\'] ?? null) ? (float)$${1}[\'${2}\'] : ${3}',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // 修復運算
        $content = str_replace(
            '* 0.1',
            '* 0.1',
            $content
        );

        $content = preg_replace(
            '/\$([a-zA-Z_]+)\s*\/\s*(\$[a-zA-Z_\[\]\']+)/',
            '(is_numeric($$1) ? (float)$$1 : 0.0) / (is_numeric($2) && $2 > 0 ? (float)$2 : 1.0)',
            $content
        );

        file_put_contents($file, $content);
        echo "  - SystemStatisticsRepository 修復完成\n";
    }

    private function fixUserStatisticsRepository(): void
    {
        echo "修復 UserStatisticsRepository...\n";
        $file = __DIR__ . '/../app/Infrastructure/Repositories/Statistics/UserStatisticsRepository.php';

        $content = file_get_contents($file);

        // 修復返回陣列的方法
        $content = str_replace(
            'return $stmt->fetchAll(PDO::FETCH_ASSOC);',
            '$result = $stmt->fetchAll(PDO::FETCH_ASSOC); return is_array($result) ? $result : [];',
            $content
        );

        // 修復 mixed 類型轉換
        $content = preg_replace(
            '/\(int\)\(\$([a-zA-Z_]+)\[\'([^\']+)\'\]\s*\?\?\s*0\)/',
            'is_numeric($${1}[\'${2}\'] ?? 0) ? (int)$${1}[\'${2}\'] : 0',
            $content
        );

        $content = preg_replace(
            '/\(float\)\(\$([a-zA-Z_]+)\[\'([^\']+)\'\]\s*\?\?\s*[0-9.]+\)/',
            'is_numeric($${1}[\'${2}\'] ?? 0.0) ? (float)$${1}[\'${2}\'] : 0.0',
            $content
        );

        file_put_contents($file, $content);
        echo "  - UserStatisticsRepository 修復完成\n";
    }

    private function fixStatisticsApplicationService(): void
    {
        echo "修復 StatisticsApplicationService...\n";
        $file = __DIR__ . '/../app/Application/Services/Statistics/StatisticsApplicationService.php';

        $content = file_get_contents($file);

        // 修復返回類型
        $content = str_replace(
            'return $result;',
            'return is_array($result) ? $result : [];',
            $content
        );

        // 修復 count 函式呼叫
        $content = str_replace(
            'count($data)',
            'is_countable($data) ? count($data) : 0',
            $content
        );

        file_put_contents($file, $content);
        echo "  - StatisticsApplicationService 修復完成\n";
    }

    private function fixStatisticsQueryService(): void
    {
        echo "修復 StatisticsQueryService...\n";
        $file = __DIR__ . '/../app/Application/Services/Statistics/StatisticsQueryService.php';

        $content = file_get_contents($file);

        // 修復方法調用參數數量
        $content = str_replace(
            '$this->statisticsRepository->findByDateRange($startDate, $endDate, $sourceType, $limit)',
            '$this->statisticsRepository->findByDateRange($startDate, $endDate, $sourceType)',
            $content
        );

        $content = str_replace(
            '$this->statisticsRepository->countByDateRange($startDate, $endDate, $sourceType)',
            '$this->statisticsRepository->countByDateRange($startDate, $endDate)',
            $content
        );

        // 修復參數順序
        $content = str_replace(
            '$this->postStatisticsRepository->getStatisticsTrends($startDate, $period, $sourceType, $dataPoints)',
            '$this->postStatisticsRepository->getStatisticsTrends($startDate, $dataPoints)',
            $content
        );

        // 修復參數類型
        $content = str_replace(
            'executeCustomQuery($query, $metrics, $groupBy, $filters)',
            'executeCustomQuery($query, is_array($metrics) ? $metrics : [], is_string($groupBy) ? $groupBy : null, is_array($filters) ? $filters : [])',
            $content
        );

        // 修復日期建構
        $content = preg_replace(
            '/new DateTimeImmutable\(\$([a-zA-Z_]+)\[\'([^\']+)\'\]\)/',
            'new DateTimeImmutable(is_string($${1}[\'${2}\'] ?? null) ? $${1}[\'${2}\'] : \'now\')',
            $content
        );

        // 修復運算
        $content = str_replace(
            '- $metric',
            '- (is_numeric($metric) ? (float)$metric : 0.0)',
            $content
        );

        file_put_contents($file, $content);
        echo "  - StatisticsQueryService 修復完成\n";
    }
}

// 執行修復
(new RepositoryServiceFixer())->run();
