<?php

declare(strict_types=1);

namespace Tests\Support;

use Mockery;
use Tests\Support\Traits\DatabaseTestTrait;

/**
 * 資料庫測試基底類別.
 *
 * 適用於需要資料庫操作的測試
 */
abstract class DatabaseTestCase extends BaseTestCase
{
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();

        // 清理 Mockery
        if ($container = Mockery::getContainer()) {
            $container->mockery_close();
        }

        parent::tearDown();
    }
}
