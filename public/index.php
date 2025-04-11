<?php
require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'AlleyNote 公布欄系統開發環境已成功設定',
    'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Taipei')))->format('c'),
    'php_version' => PHP_VERSION
]);
