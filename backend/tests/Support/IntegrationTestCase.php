<?php

declare(strict_types=1);

namespace Tests\Support;

use Mockery;
use Tests\SecureDDDTestCase;
use Tests\Support\Traits\CacheTestTrait;
use Tests\Support\Traits\DatabaseSnapshotTrait;
use Tests\Support\Traits\DatabaseTestTrait;

/**
 * 整合測試基底類別.
 *
 * 適用於需要完整系統環境的整合測試
 * 提供資料庫、快取、HTTP 回應等完整功能
 */
abstract class IntegrationTestCase extends SecureDDDTestCase
{
    use DatabaseTestTrait;
    use CacheTestTrait;
    // Note: DatabaseSnapshotTrait may not exist in this branch yet, 
    // but we'll try to use it if available or just skip if it fails.
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpCache();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        $this->tearDownCache();

        // 清理 Mockery
        if ($container = Mockery::getContainer()) {
            $container->mockery_close();
        }

        parent::tearDown();
    }
}
