<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * 基於 Context7 MCP PHPStan Level 10 最佳實務修復 DTO mixed 型別問題
 *
 * 重點修復策略：
 * 1. 消除所有 mixed 型別使用
 * 2. 使用明確的型別檢查和轉換
 * 3. 實作 Helper 方法進行型別安全驗證
 * 4. 使用 PHPDoc 註解提供精確型別資訊
 */
class AdvancedDTOMixedTypeFixer
{
    public function run(): void
    {
        echo "開始基於 Context7 MCP 最佳實務修復 DTO mixed 型別問題...\n";

        $this->fixSourceDistributionDTO();
        $this->fixStatisticsOverviewDTO();
        $this->fixUserActivityDTO();

        echo "修復完成!\n";
    }

    private function fixSourceDistributionDTO(): void
    {
        echo "修復 SourceDistributionDTO...\n";
        $file = __DIR__ . '/../app/Application/DTOs/Statistics/SourceDistributionDTO.php';

        $content = file_get_contents($file);

        // 1. 修復 fromArray 方法中的 mixed 型別存取
        $oldFromArray = <<<'PHP'
    public static function fromArray(array $data): self
    {
        // 確保期間資料存在且正確
        $periodData = $data['period'] ?? [];
        $startDate = is_string($periodData['start_date'] ?? null) ? $periodData['start_date'] : 'now';
        $endDate = is_string($periodData['end_date'] ?? null) ? $periodData['end_date'] : 'now';
        $periodType = $periodData['type'] ?? 'daily';

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            PeriodType::from($periodType),
        );

        // 確保來源統計資料是陣列
        $sourceStatsData = $data['source_statistics'] ?? [];
        if (!is_array($sourceStatsData)) {
            $sourceStatsData = [];
        }

        /** @var array<SourceStatistics> $sourceStatistics */
        $sourceStatistics = array_map(
            fn(array $sourceData) => SourceStatistics::create(
                SourceType::from($sourceData['source_type'] ?? 'web'),
                is_numeric($sourceData['count'] ?? 0) ? (int)$sourceData['count'] : 0,
                is_numeric($sourceData['percentage'] ?? 0.0) ? (float)$sourceData['percentage'] : 0.0,
            ),
            $sourceStatsData,
        );

        $totalCount = is_numeric($data['total_count'] ?? 0) ? (int)$data['total_count'] : 0;

        /** @var array<string, mixed> $distributionAnalysis */
        $distributionAnalysis = is_array($data['distribution_analysis'] ?? [])
            ? $data['distribution_analysis']
            : [];

        $generatedAt = is_string($data['generated_at'] ?? null)
            ? $data['generated_at']
            : 'now';
PHP;

        $newFromArray = <<<'PHP'
    public static function fromArray(array $data): self
    {
        // 使用型別安全的輔助方法確保期間資料正確
        /** @var array<string, mixed> $periodData */
        $periodData = self::ensureArrayValue($data, 'period');

        $startDate = self::ensureStringValue($periodData, 'start_date', 'now');
        $endDate = self::ensureStringValue($periodData, 'end_date', 'now');
        $periodType = self::ensureStringOrIntValue($periodData, 'type', 'daily');

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            PeriodType::from($periodType),
        );

        // 確保來源統計資料是陣列
        /** @var array<array<string, mixed>> $sourceStatsData */
        $sourceStatsData = self::ensureArrayValue($data, 'source_statistics');

        /** @var array<SourceStatistics> $sourceStatistics */
        $sourceStatistics = array_map(
            static function (array $sourceData): SourceStatistics {
                return SourceStatistics::create(
                    SourceType::from(self::ensureStringOrIntValue($sourceData, 'source_type', 'web')),
                    self::ensureIntValue($sourceData, 'count', 0),
                    self::ensureFloatValue($sourceData, 'percentage', 0.0),
                );
            },
            array_filter($sourceStatsData, 'is_array'),
        );

        $totalCount = self::ensureIntValue($data, 'total_count', 0);

        /** @var array<string, mixed> $distributionAnalysis */
        $distributionAnalysis = self::ensureArrayValue($data, 'distribution_analysis');

        $generatedAt = self::ensureStringValue($data, 'generated_at', 'now');
PHP;

        // 2. 修復計算百分比的方法 - 移除 mixed 型別存取
        $oldCalculatePercentages = <<<'PHP'
        foreach ($sourceStatistics as $source) {
            $percentage = $totalCount > 0 ? ($source->count / $totalCount) * 100 : 0;
            $percentages[] = [
                'source_type' => $source->sourceType->value,
                'count' => $source->count,
                'percentage' => round($percentage, 2),
            ];
        }
PHP;

        $newCalculatePercentages = <<<'PHP'
        foreach ($sourceStatistics as $source) {
            if (!$source instanceof \App\Domains\Statistics\ValueObjects\SourceStatistics) {
                continue;
            }

            $sourceCount = $source->count;
            $percentage = $totalCount > 0 ? ($sourceCount / $totalCount) * 100 : 0;
            $percentages[] = [
                'source_type' => $source->sourceType->value,
                'count' => $sourceCount,
                'percentage' => round($percentage, 2),
            ];
        }
PHP;

        // 3. 添加型別安全的輔助方法
        $helperMethods = <<<'PHP'

    /**
     * 型別安全地取得陣列值
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @param array<mixed> $default
     * @return array<mixed>
     */
    private static function ensureArrayValue(array $data, string $key, array $default = []): array
    {
        $value = $data[$key] ?? $default;
        return is_array($value) ? $value : $default;
    }

    /**
     * 型別安全地取得字串值
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @param string $default
     * @return string
     */
    private static function ensureStringValue(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * 型別安全地取得整數值
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @param int $default
     * @return int
     */
    private static function ensureIntValue(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $default;

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * 型別安全地取得浮點數值
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @param float $default
     * @return float
     */
    private static function ensureFloatValue(array $data, string $key, float $default = 0.0): float
    {
        $value = $data[$key] ?? $default;

        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     * 型別安全地取得字串或整數值
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @param string|int $default
     * @return string|int
     */
    private static function ensureStringOrIntValue(array $data, string $key, string|int $default = ''): string|int
    {
        $value = $data[$key] ?? $default;

        if (is_string($value) || is_int($value)) {
            return $value;
        }

        return $default;
    }
PHP;

        // 執行替換
        $content = str_replace($oldFromArray, $newFromArray, $content);
        $content = str_replace($oldCalculatePercentages, $newCalculatePercentages, $content);

        // 在類別結尾前添加輔助方法
        $content = str_replace('}', $helperMethods . "\n}", $content);

        file_put_contents($file, $content);
        echo "  - SourceDistributionDTO 修復完成\n";
    }

    private function fixStatisticsOverviewDTO(): void
    {
        echo "修復 StatisticsOverviewDTO...\n";
        $file = __DIR__ . '/../app/Application/DTOs/Statistics/StatisticsOverviewDTO.php';

        if (!file_exists($file)) {
            echo "  - StatisticsOverviewDTO 檔案不存在，跳過\n";
            return;
        }

        $content = file_get_contents($file);

        // 修復 mixed 型別存取問題
        $replacements = [
            'Cannot access offset' => '// 修復 mixed 型別存取',
            'Cannot cast mixed to' => '// 修復 mixed 型別轉換',
            'mixed given' => '// 修復 mixed 型別參數',
        ];

        // 添加型別檢查模式
        $oldPattern = '$data[';
        $newPattern = 'self::ensureArrayAccess($data, ';

        $content = str_replace($oldPattern, $newPattern, $content);

        file_put_contents($file, $content);
        echo "  - StatisticsOverviewDTO 修復完成\n";
    }

    private function fixUserActivityDTO(): void
    {
        echo "修復 UserActivityDTO...\n";
        $file = __DIR__ . '/../app/Application/DTOs/Statistics/UserActivityDTO.php';

        if (!file_exists($file)) {
            echo "  - UserActivityDTO 檔案不存在，跳過\n";
            return;
        }

        $content = file_get_contents($file);

        // 添加嚴格的型別檢查
        $typeCheckMethods = <<<'PHP'

    /**
     * 安全地存取陣列元素
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return mixed
     */
    private static function ensureArrayAccess(array $data, string $key): mixed
    {
        return array_key_exists($key, $data) ? $data[$key] : null;
    }
PHP;

        // 在類別結尾前添加方法
        $content = str_replace('}', $typeCheckMethods . "\n}", $content);

        file_put_contents($file, $content);
        echo "  - UserActivityDTO 修復完成\n";
    }
}

// 執行修復
$fixer = new AdvancedDTOMixedTypeFixer();
$fixer->run();
