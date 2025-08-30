<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * 基礎測試類別，所有測試類別的共同祖先
 * 
 * 只提供最基本的測試功能，具體功能由特定的 trait 或子類別提供
 */
abstract class BaseTestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 設定測試環境變數
        putenv('APP_ENV=testing');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * 產生隨機字串用於測試
     */
    protected function generateRandomString(int $length = 10): string
    {
        $bytes = max(1, (int) ceil($length / 2));
        return bin2hex(random_bytes($bytes));
    }

    /**
     * 產生測試用的 UUID
     */
    protected function generateTestUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * 產生測試用的電子郵件地址
     */
    protected function generateTestEmail(): string
    {
        return 'test_' . $this->generateRandomString(8) . '@example.com';
    }
}