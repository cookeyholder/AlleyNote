<?php

declare(strict_types=1);

namespace Tests\Support;

use Mockery;
use Tests\Support\BaseTestCase;

/**
 * 單元測試基底類別
 * 
 * 適用於純單元測試，不需要資料庫和外部依賴
 */
abstract class UnitTestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // 清理 Mockery 
        if ($container = Mockery::getContainer()) {
            $container->mockery_close();
        }
        
        parent::tearDown();
    }
}