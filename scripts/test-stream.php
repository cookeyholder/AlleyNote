<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// 測試 Stream 建立
$stream = new \App\Infrastructure\Http\Stream('test content');

echo "Stream 建立測試：\n";
echo "getMetadata('mode'): " . var_export($stream->getMetadata('mode'), true) . "\n";
echo "isWritable(): " . var_export($stream->isWritable(), true) . "\n";
echo "isReadable(): " . var_export($stream->isReadable(), true) . "\n";

// 測試寫入
try {
    $stream->write('additional content');
    echo "✅ 寫入測試成功\n";
} catch (Exception $e) {
    echo "❌ 寫入測試失敗: " . $e->getMessage() . "\n";
}

// 測試讀取
try {
    $stream->rewind();
    $content = $stream->getContents();
    echo "讀取內容: $content\n";
} catch (Exception $e) {
    echo "❌ 讀取測試失敗: " . $e->getMessage() . "\n";
}
