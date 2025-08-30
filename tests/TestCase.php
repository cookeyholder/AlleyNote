<?php

declare(strict_types=1);

namespace Tests;

use Tests\Support\IntegrationTestCase;

/**
 * 舊版 TestCase 類別，保持向後兼容性
 * 
 * @deprecated 建議使用 Tests\Support\IntegrationTestCase 或其他特定的測試基底類別
 */
abstract class TestCase extends IntegrationTestCase
{
    // 為了向後兼容，保持原有的介面
    // 實際功能已經移至 IntegrationTestCase 和相關 Traits
}
