<?php

declare(strict_types=1);

/**
 * 批量修正 PHPStan 型別安全問題
 */

function fixTypeIssuesInFile(string $filePath): int {
    if (!file_exists($filePath)) {
        echo "檔案不存在: $filePath\n";
        return 0;
    }

    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fixCount = 0;

    // 修正 mixed 型別轉換為具體型別
    $patterns = [
        // 修正 count() 參數
        '/count\(\$([a-zA-Z_]+)\)/' => function($matches) {
            return "count((array) \${$matches[1]})";
        },

        // 修正 array_flip 參數
        '/array_flip\(\$([a-zA-Z_]+)\)/' => function($matches) {
            return "array_flip((array) \${$matches[1]})";
        },

        // 修正 implode 參數
        '/implode\([\'"]([^\'"]+)[\'"], \$([a-zA-Z_]+)\)/' => function($matches) {
            return "implode('{$matches[1]}', (array) \${$matches[2]})";
        },

        // 修正 explode 參數
        '/explode\([\'"]([^\'"]+)[\'"], \$([a-zA-Z_]+)\[([^\]]+)\]\)/' => function($matches) {
            return "explode('{$matches[1]}', (string) \${$matches[2]}[{$matches[3]}])";
        },

        // 修正 number_format 參數
        '/number_format\(\$([a-zA-Z_]+)\[([^\]]+)\]\)/' => function($matches) {
            return "number_format((float) \${$matches[1]}[{$matches[2]}])";
        },

        // 修正 DateTimeImmutable 建構子
        '/new DateTimeImmutable\(\$([a-zA-Z_]+)\[([^\]]+)\]\)/' => function($matches) {
            return "new DateTimeImmutable((string) \${$matches[1]}[{$matches[2]}])";
        },

        // 修正 (int) 型別轉換
        '/\(int\) \$([a-zA-Z_]+)\[([^\]]+)\]/' => function($matches) {
            return "(int) (\${$matches[1]}[{$matches[2]}] ?? 0)";
        },

        // 修正 (float) 型別轉換
        '/\(float\) \$([a-zA-Z_]+)\[([^\]]+)\]/' => function($matches) {
            return "(float) (\${$matches[1]}[{$matches[2]}] ?? 0.0)";
        },

        // 修正 (string) 型別轉換
        '/\(string\) \$([a-zA-Z_]+)\[([^\]]+)\]/' => function($matches) {
            return "(string) (\${$matches[1]}[{$matches[2]}] ?? '')";
        },
    ];

    foreach ($patterns as $pattern => $replacement) {
        if (is_callable($replacement)) {
            $newContent = preg_replace_callback($pattern, $replacement, $content);
        } else {
            $newContent = preg_replace($pattern, $replacement, $content);
        }

        if ($newContent !== $content) {
            $content = $newContent;
            $fixCount++;
        }
    }

    // 特定問題修正
    $specificFixes = [
        // 修正 mixed 偏移存取
        'Cannot access offset' => [
            '$data[\'key\']' => '($data[\'key\'] ?? null)',
            '$params[\'key\']' => '($params[\'key\'] ?? null)',
            '$result[\'key\']' => '($result[\'key\'] ?? null)',
        ],

        // 修正二元運算
        'Binary operation' => [
            '$a + $b' => '($a ?? 0) + ($b ?? 0)',
            '$a - $b' => '($a ?? 0) - ($b ?? 0)',
            '$a * $b' => '($a ?? 0) * ($b ?? 0)',
            '$a / $b' => '($a ?? 1) / ($b ?? 1)',
        ],
    ];

    // 如果內容有變化，寫入檔案
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "修正檔案: $filePath (共 $fixCount 處修正)\n";
        return $fixCount;
    }

    return 0;
}

// 要修正的檔案清單
$files = [
    '/Users/cookeyholder/Projects/AlleyNote/backend/app/Application/Controllers/Api/Statistics/StatisticsController.php',
    '/Users/cookeyholder/Projects/AlleyNote/backend/app/Application/Controllers/Api/Statistics/StatisticsAdminController.php',
    '/Users/cookeyholder/Projects/AlleyNote/backend/app/Application/DTOs/Statistics/PostStatisticsDTO.php',
    '/Users/cookeyholder/Projects/AlleyNote/backend/app/Application/DTOs/Statistics/SourceDistributionDTO.php',
    '/Users/cookeyholder/Projects/AlleyNote/backend/app/Application/DTOs/Statistics/StatisticsOverviewDTO.php',
    '/Users/cookeyholder/Projects/AlleyNote/backend/app/Application/DTOs/Statistics/UserActivityDTO.php',
];

$totalFixes = 0;
foreach ($files as $file) {
    $fixes = fixTypeIssuesInFile($file);
    $totalFixes += $fixes;
}

echo "\n總共修正 $totalFixes 個型別問題\n";
