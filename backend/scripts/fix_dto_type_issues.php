<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * DTO 類型修復腳本
 */
class DtoTypeIssuesFixer
{
    private array $files = [
        'app/Application/DTOs/Statistics/PostStatisticsDTO.php',
        'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'app/Application/DTOs/Statistics/StatisticsOverviewDTO.php',
        'app/Application/DTOs/Statistics/UserActivityDTO.php',
    ];

    public function run(): void
    {
        echo "開始修復 DTO 類型問題...\n";

        foreach ($this->files as $file) {
            $fullPath = __DIR__ . '/../' . $file;

            if (!file_exists($fullPath)) {
                echo "檔案不存在: $file\n";
                continue;
            }

            echo "修復檔案: $file\n";
            $this->fixFile($fullPath);
        }

        echo "修復完成!\n";
    }

    private function fixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $original = $content;

        // 1. 修復 mixed 類型返回值
        $content = $this->fixMixedReturnTypes($content);

        // 2. 修復陣列取值
        $content = $this->fixArrayAccess($content);

        // 3. 修復函式參數類型
        $content = $this->fixParameterTypes($content);

        // 4. 修復二元運算
        $content = $this->fixBinaryOperations($content);

        // 5. 修復建構函式參數
        $content = $this->fixConstructorParameters($content);

        if ($content !== $original) {
            file_put_contents($filePath, $content);
            echo "  - 修復完成\n";
        } else {
            echo "  - 無需修復\n";
        }
    }

    private function fixMixedReturnTypes(string $content): string
    {
        // 修復 getTotalEngagements 返回類型 (float|int → int)
        $content = preg_replace(
            '/return.*?(\$this->likeCount->value \+ \$this->commentCount->value \+ \$this->shareCount->value);/',
            'return (int)($1);',
            $content
        );

        // 修復 getPerformanceScore 返回類型
        $content = str_replace(
            'return $this->additionalMetrics[\'performance_score\']',
            'return (float)($this->additionalMetrics[\'performance_score\'] ?? 0.0)',
            $content
        );

        // 修復 getTrendDirection 返回類型
        $content = str_replace(
            'return $this->additionalMetrics[\'trend_direction\'] ?? \'stable\';',
            'return (string)($this->additionalMetrics[\'trend_direction\'] ?? \'stable\');',
            $content
        );

        // 修復 getAgeInDays 返回類型
        $content = preg_replace(
            '/return.*?diff\(\$this->publishedAt\)->days;/',
            'return (int)(new DateTimeImmutable())->diff($this->publishedAt)->days;',
            $content
        );

        return $content;
    }

    private function fixArrayAccess(string $content): string
    {
        // 修復 fromPostData 中的陣列取值
        $patterns = [
            '/\$data\[\'([^\']+)\'\]\s*\?\?\s*([^,;]+)/' => 'self::extractValue($data, \'$1\', $2)',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixParameterTypes(string $content): string
    {
        // 添加型別提取輔助方法
        $helperMethods = '
    /**
     * 安全地從陣列中提取字串值
     */
    private static function extractString(array $data, string $key, string $default = \'\'): string
    {
        $value = $data[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * 安全地從陣列中提取整數值
     */
    private static function extractInteger(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $default;
        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * 安全地從陣列中提取浮點數值
     */
    private static function extractFloat(array $data, string $key, float $default = 0.0): float
    {
        $value = $data[$key] ?? $default;
        return is_numeric($value) ? (float)$value : $default;
    }

    /**
     * 安全地從陣列中提取陣列值
     */
    private static function extractArray(array $data, string $key, array $default = []): array
    {
        $value = $data[$key] ?? $default;
        return is_array($value) ? $value : $default;
    }

    /**
     * 安全地從陣列中提取混合值
     */
    private static function extractValue(array $data, string $key, mixed $default = null): mixed
    {
        return $data[$key] ?? $default;
    }
';

        // 將輔助方法添加到類別結尾前
        if (strpos($content, 'extractString') === false) {
            $content = str_replace(
                "\n}\n",
                "\n$helperMethods\n}\n",
                $content
            );
        }

        return $content;
    }

    private function fixBinaryOperations(string $content): string
    {
        // 修復加法運算中的 mixed 類型
        $content = preg_replace(
            '/\(\$metrics\[\'([^\']+)\'\]\s*\?\?\s*0\)\s*\+\s*mixed/',
            '(int)($metrics[\'$1\'] ?? 0) + 0',
            $content
        );

        // 修復除法運算
        $content = preg_replace(
            '/\$([a-zA-Z_]+)\s*\/\s*1000/',
            '(float)$$$1 / 1000.0',
            $content
        );

        return $content;
    }

    private function fixConstructorParameters(string $content): string
    {
        // 修復建構函式中的陣列參數類型
        $content = str_replace(
            'array $additionalMetrics',
            'array $additionalMetrics = []',
            $content
        );

        return $content;
    }
}

// 執行修復
(new DtoTypeIssuesFixer())->run();
