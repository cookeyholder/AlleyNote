<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\IntegrationTestCase;

// 在命名空間之外定義測試用的全域 Helper
if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        // 優先讀取全域變數（供測試動態修改）
        global $tempLogsDir;
        $base = is_string($tempLogsDir) ? $tempLogsDir : sys_get_temp_dir() . '/alleynote_storage';

        return $base . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

/**
 * 舊版 TestCase 類別，保持向後兼容性.
 *
 * @deprecated 建議使用 Tests\Support\IntegrationTestCase 或其他特定的測試基底類別
 */
abstract class TestCase extends IntegrationTestCase
{
    // 為了向後兼容，保持原有的介面
    // 實際功能已經移至 IntegrationTestCase 和相關 Traits
}
