#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 修復 AuthenticationServiceTest 中的方法命名
 */

$filePath = '/var/www/html/tests/Unit/Domains/Auth/Services/AuthenticationServiceTest.php';

if (!file_exists($filePath)) {
    echo "檔案不存在: $filePath\n";
    exit(1);
}

$content = file_get_contents($filePath);

// 定義需要修復的方法模式
$patterns = [
    '/public function (login_[^:]+):/m' => 'public function test$1:',
    '/public function (refresh_[^:]+):/m' => 'public function test$1:',
    '/public function (logout_[^:]+):/m' => 'public function test$1:',
    '/public function (validate[^:]+):/m' => 'public function test$1:',
    '/public function (revoke[^:]+):/m' => 'public function test$1:',
    '/public function (get[^:]+):/m' => 'public function test$1:',
    '/public function (cleanup[^:]+):/m' => 'public function test$1:',
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

if (file_put_contents($filePath, $content)) {
    echo "已成功修復 AuthenticationServiceTest.php 的方法命名\n";
} else {
    echo "修復失敗\n";
    exit(1);
}

echo "修復完成！\n";