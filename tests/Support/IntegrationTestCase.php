<?php

declare(strict_types=1);

namespace Tests\Support;

use Mockery;
use Tests\Support\BaseTestCase;
use Tests\Support\Traits\DatabaseTestTrait;
use Tests\Support\Traits\CacheTestTrait;
use Tests\Support\Traits\HttpResponseTestTrait;

/**
 * 整合測試基底類別
 * 
 * 適用於需要完整系統環境的整合測試
 * 提供資料庫、快取、HTTP 回應等完整功能
 */
abstract class IntegrationTestCase extends BaseTestCase
{
    use DatabaseTestTrait;
    use CacheTestTrait;
    use HttpResponseTestTrait;

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