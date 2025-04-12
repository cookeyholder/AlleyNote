<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * 初始化測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
