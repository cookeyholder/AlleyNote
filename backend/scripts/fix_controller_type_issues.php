<?php

declare(strict_types=1);

/**
 * 修正 StatisticsController 中的型別問題
 */

$controllerFile = '/Users/cookeyholder/Projects/AlleyNote/backend/app/Application/Controllers/Api/Statistics/StatisticsController.php';

if (!file_exists($controllerFile)) {
    echo "檔案不存在: $controllerFile\n";
    exit(1);
}

$content = file_get_contents($controllerFile);

// 修正 validate 方法呼叫的型別轉換
$patterns = [
    // 修正 getQueryParams() 呼叫
    '/\$this->validate(\w+)Params\(\$request->getQueryParams\(\)\)/' => '$this->validate$1Params((array) $request->getQueryParams())',

    // 修正 mixed 型別轉換
    '/\(\$params\[\'(\w+)\'\]\)/' => '((int) $params[\'$1\'])',

    // 修正字串型別轉換
    '/explode\(\'([^\']+)\', \$(\w+)\[\'(\w+)\'\]\)/' => 'explode(\'$1\', (string) $$2[\'$3\'])',

    // 修正 DateTimeImmutable 建構子
    '/new DateTimeImmutable\(\$(\w+)\[\'(\w+)\'\]\)/' => 'new DateTimeImmutable((string) $$1[\'$2\'])',
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// 修正特定的型別轉換問題
$specificFixes = [
    // SourceType::from() 參數
    'SourceType::from($params[\'source\'])' => 'SourceType::from((string) $params[\'source\'])',

    // count() 函式參數
    'count($sources)' => 'count((array) $sources)',
    'count($data)' => 'count((array) $data)',

    // 字串轉換
    '$period (mixed)' => '(string) $period',
    '$type (mixed)' => '(string) $type',
    '$queryParams[\'report_type\'] (mixed)' => '(string) $queryParams[\'report_type\']',
    '$queryParams[\'start_date\'] (mixed)' => '(string) $queryParams[\'start_date\']',
    '$queryParams[\'end_date\'] (mixed)' => '(string) $queryParams[\'end_date\']',
];

foreach ($specificFixes as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// 寫入修正後的內容
file_put_contents($controllerFile, $content);

echo "已完成 StatisticsController 型別問題修正\n";
