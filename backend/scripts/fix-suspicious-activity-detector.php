<?php

declare(strict_types=1);

/**
 * SuspiciousActivityDetector.php 專項修復腳本
 *
 * 專門修復 SuspiciousActivityDetector.php 中的複雜語法錯誤，
 * 包括未閉合括號、不完整的if語句、try-catch結構等問題。
 */

echo "🔧 開始修復 SuspiciousActivityDetector.php...\n";

$filePath = __DIR__ . '/../app/Domains/Security/Services/SuspiciousActivityDetector.php';

if (!file_exists($filePath)) {
    echo "❌ 檔案不存在: {$filePath}\n";
    exit(1);
}

$content = file_get_contents($filePath);
if ($content === false) {
    echo "❌ 無法讀取檔案: {$filePath}\n";
    exit(1);
}

$originalContent = $content;
$fixCount = 0;

echo "📝 開始修復語法錯誤...\n";

// 1. 修復未閉合的方括號問題
$patterns = [
    // 修復 if ($activity['user_id'] { 這類錯誤
    '/if\s*\(\s*\$activity\[\'([^\']+)\'\]\s*\{/' => 'if ($activity[\'$1\']) {',
    '/if\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\[\'([^\']+)\'\]\s*\{/' => 'if ($$$1[\'$2\']) {',

    // 修復 $result['suspicious'] { 這類錯誤
    '/\$result\[\'suspicious\'\]\s*\{/' => '$result[\'suspicious\']) {',
    '/\$([a-zA-Z_][a-zA-Z0-9_]*)\[\'([^\']+)\'\]\s*\{/' => '$$$1[\'$2\']) {',

    // 修復未閉合的方法調用括號
    '/\$this->escalateSeverity\(\$severityLevel,\s*\$result\[\'severity\'\];/' => '$this->escalateSeverity($severityLevel, $result[\'severity\']);',
    '/array_merge\(\$detectionRules,\s*\$result\[\'rules\'\];/' => 'array_merge($detectionRules, $result[\'rules\']);',
    '/array_merge\(\$anomalyScores,\s*\$result\[\'scores\'\];/' => 'array_merge($anomalyScores, $result[\'scores\']);',
    '/max\(\$confidence,\s*\$result\[\'confidence\'\];/' => 'max($confidence, $result[\'confidence\']);',

    // 修復方法調用中的語法錯誤
    '/\$this->detectPatternAnomalies\(\$activities\];/' => '$this->detectPatternAnomalies($activities);',
    '/\$this->isDetectionEnabled\(\'([^\']+)\'\)\]/' => '$this->isDetectionEnabled(\'$1\')',

    // 修復陣列存取語法錯誤
    '/\$activities\[0\]\[\'user_id\'\]\s*\?\?\s*0;/' => '$activities[0][\'user_id\'] ?? 0;',
    '/\$activity\[\'([^\']+)\'\]\s*\?\?\s*([^;]+);/' => '$activity[\'$1\'] ?? $2;',
];

foreach ($patterns as $pattern => $replacement) {
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== $content) {
        $matches = preg_match_all($pattern, $content);
        if ($matches > 0) {
            $content = $newContent;
            $fixCount += $matches;
            echo "  ✅ 修復語法模式: " . substr($pattern, 0, 50) . "... ({$matches} 次)\n";
        }
    }
}

// 2. 修復複雜的條件語句語法錯誤
$complexPatterns = [
    // 修復 if 語句中缺少右括號的問題
    '/if\s*\(\s*([^{]+?)\s*\{/' => function($matches) {
        $condition = trim($matches[1]);
        // 檢查括號平衡
        $openParens = substr_count($condition, '(');
        $closeParens = substr_count($condition, ')');
        if ($openParens > $closeParens) {
            $condition .= str_repeat(')', $openParens - $closeParens);
        }
        return "if ({$condition}) {";
    },

    // 修復方法調用語法
    '/\$this->([a-zA-Z_][a-zA-Z0-9_]*)\(([^)]*);/' => '$this->$1($2);',

    // 修復陣列存取語法錯誤
    '/\$([a-zA-Z_][a-zA-Z0-9_]*)\[\'([^\']+)\'\]\s*;/' => '$$$1[\'$2\'];',
];

foreach ($complexPatterns as $pattern => $replacement) {
    if (is_callable($replacement)) {
        $newContent = preg_replace_callback($pattern, $replacement, $content);
    } else {
        $newContent = preg_replace($pattern, $replacement, $content);
    }

    if ($newContent !== null && $newContent !== $content) {
        $content = $newContent;
        $fixCount++;
        echo "  ✅ 修復複雜語法模式: " . substr($pattern, 0, 30) . "...\n";
    }
}

// 3. 修復 try-catch 結構問題
$tryCatchPatterns = [
    // 修復不完整的 try 塊
    '/try\s*\{\s*\/\*\s*empty\s*\*\/\s*\}/' => 'try {',
    '/try\s*\{\s*\}/' => 'try {',
];

foreach ($tryCatchPatterns as $pattern => $replacement) {
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== $content) {
        $content = $newContent;
        $fixCount++;
        echo "  ✅ 修復 try-catch 結構\n";
    }
}

// 4. 修復字串和引號問題
$stringPatterns = [
    // 修復字串插值問題
    '/throw\s+new\s+InvalidArgumentException\("([^"]*)\{\$([^}]+)\}([^"]*)"\];/' => 'throw new InvalidArgumentException("$1{$$2}$3");',
    '/throw\s+new\s+InvalidArgumentException\("([^"]*)\{\$([^}]+)\}([^"]*)"\)\];/' => 'throw new InvalidArgumentException("$1{$$2}$3");',
];

foreach ($stringPatterns as $pattern => $replacement) {
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== $content) {
        $content = $newContent;
        $fixCount++;
        echo "  ✅ 修復字串語法\n";
    }
}

// 5. 修復方法結構問題
$methodPatterns = [
    // 確保方法有正確的結尾
    '/(\s+)}\s*$/' => '$1}' . "\n",

    // 修復多餘的分號
    '/;;\s*/' => '; ',

    // 修復多餘的括號
    '/\)\s*\)\s*;/' => ');',
];

foreach ($methodPatterns as $pattern => $replacement) {
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== $content) {
        $content = $newContent;
        $fixCount++;
        echo "  ✅ 修復方法結構\n";
    }
}

// 6. 特殊的語法修復
$specialFixes = [
    // 修復特定的已知問題
    'if ($activity[\'user_id\'] {' => 'if ($activity[\'user_id\']) {',
    '$result[\'suspicious\'] {' => '$result[\'suspicious\']) {',
    'if ($this->isDetectionEnabled(\'pattern_analysis\']' => 'if ($this->isDetectionEnabled(\'pattern_analysis\'))',
    '$this->detectPatternAnomalies($activities];' => '$this->detectPatternAnomalies($activities);',
    'max($confidence, $result[\'confidence\'];' => 'max($confidence, $result[\'confidence\']);',
    '$this->escalateSeverity($severityLevel, $result[\'severity\'];' => '$this->escalateSeverity($severityLevel, $result[\'severity\']);',
    'array_merge($detectionRules, $result[\'rules\'];' => 'array_merge($detectionRules, $result[\'rules\']);',
    'array_merge($anomalyScores, $result[\'scores\'];' => 'array_merge($anomalyScores, $result[\'scores\']);',
];

foreach ($specialFixes as $search => $replace) {
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        $fixCount++;
        echo "  ✅ 修復特定語法錯誤: " . substr($search, 0, 30) . "...\n";
    }
}

// 寫回檔案
if ($content !== $originalContent) {
    if (file_put_contents($filePath, $content) !== false) {
        echo "\n📊 修復摘要:\n";
        echo "==================================================\n";
        echo "檔案: SuspiciousActivityDetector.php\n";
        echo "總修復數量: {$fixCount}\n";
        echo "修復成功: ✅\n";

        echo "\n🔍 建議後續檢查:\n";
        echo "  1. 執行 PHP 語法檢查: php -l {$filePath}\n";
        echo "  2. 運行 PHPStan 檢查: docker compose exec -T web ./vendor/bin/phpstan analyse {$filePath}\n";
        echo "  3. 運行相關測試確保功能正常\n";

        echo "\n📈 預期改善:\n";
        echo "  - 消除括號不匹配錯誤\n";
        echo "  - 修復 if 語句語法問題\n";
        echo "  - 改善方法調用語法\n";
        echo "  - 修復陣列存取語法\n";

    } else {
        echo "❌ 無法寫入檔案: {$filePath}\n";
        exit(1);
    }
} else {
    echo "ℹ️  沒有發現需要修復的語法錯誤\n";
}

echo "\n✅ SuspiciousActivityDetector.php 修復完成！\n";
