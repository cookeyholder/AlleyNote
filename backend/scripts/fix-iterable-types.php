#!/usr/bin/env php
<?php

declare(strict_types=1);

// 批量修復 missing_iterable_value_type 錯誤的腳本

$files = [
    'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
    'app/Application/DTOs/Statistics/StatisticsOverviewDTO.php', 
    'app/Application/DTOs/Statistics/UserActivityDTO.php',
];

$replacements = [
    // SourceDistributionDTO
    [
        'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'pattern' => '/public function getTopSources\(int \$limit = 3\): array/',
        'replacement' => "/**\n     * 取得前 N 個來源.\n     * \n     * @return array<int, SourceStatistics>\n     */\n    public function getTopSources(int \$limit = 3): array"
    ],
    [
        'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'pattern' => '/public function getSourceRanking\(\): array/',
        'replacement' => "/**\n     * 取得來源排名.\n     * \n     * @return array<string, mixed>\n     */\n    public function getSourceRanking(): array"
    ],
    [
        'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'pattern' => '/public function getDistributionSummary\(\): array/',
        'replacement' => "/**\n     * 取得分佈摘要.\n     * \n     * @return array<string, mixed>\n     */\n    public function getDistributionSummary(): array"
    ],
    [
        'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'pattern' => '/public function getFormattedDistribution\(\): array/',
        'replacement' => "/**\n     * 取得格式化的分佈資料.\n     * \n     * @return array<string, mixed>\n     */\n    public function getFormattedDistribution(): array"
    ],
    [
        'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'pattern' => '/public function compareWith\(SourceDistributionDTO \$other\): array/',
        'replacement' => "/**\n     * 與其他分佈比較.\n     * \n     * @return array<string, mixed>\n     */\n    public function compareWith(SourceDistributionDTO \$other): array"
    ],
    [
        'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'pattern' => '/public function toArray\(\): array/',
        'replacement' => "/**\n     * 轉換為陣列.\n     * \n     * @return array<string, mixed>\n     */\n    public function toArray(): array"
    ],
    [
        'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
        'pattern' => '/public function jsonSerialize\(\): array/',
        'replacement' => "/**\n     * JSON 序列化.\n     * \n     * @return array<string, mixed>\n     */\n    public function jsonSerialize(): array"
    ],
];

// 執行替換
foreach ($replacements as $replacement) {
    $filePath = "/var/www/html/{$replacement['file']}";
    
    if (!file_exists($filePath)) {
        echo "檔案不存在: {$filePath}\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "無法讀取檔案: {$filePath}\n";
        continue;
    }
    
    $pattern = $replacement['pattern'];
    $newContent = preg_replace($pattern, $replacement['replacement'], $content);
    
    if ($newContent === null) {
        echo "正規表達式錯誤: {$filePath}\n";
        continue;
    }
    
    if ($newContent !== $content) {
        $result = file_put_contents($filePath, $newContent);
        if ($result === false) {
            echo "無法寫入檔案: {$filePath}\n";
        } else {
            echo "成功修復: {$filePath}\n";
        }
    } else {
        echo "無變更: {$filePath}\n";
    }
}

echo "\n修復完成！\n";
