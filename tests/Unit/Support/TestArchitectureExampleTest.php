<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Tests\Support\UnitTestCase;

/**
 * 展示新測試架構使用方式的範例測試.
 */
class TestArchitectureExampleTest extends UnitTestCase
{
    public function testBaseTestCaseHelpers(): void
    {
        // 測試基本輔助方法
        $randomString = $this->generateRandomString(10);
        $this->assertIsString($randomString);
        $this->assertEquals(10, strlen($randomString));

        $uuid = $this->generateTestUuid();
        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);

        $email = $this->generateTestEmail();
        $this->assertIsString($email);
        $this->assertStringContainsString('@example.com', $email);
    }

    public function testUnitTestIsolation(): void
    {
        // 單元測試應該是快速且獨立的
        $this->assertTrue(true);

        // 這個測試不應該有資料庫或快取依賴
        $this->assertFalse(property_exists($this, 'db'));
        $this->assertFalse(property_exists($this, 'cache'));
    }
}
