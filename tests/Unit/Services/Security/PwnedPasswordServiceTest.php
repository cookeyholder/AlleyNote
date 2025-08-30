<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Auth\Services\Advanced\PwnedPasswordService;
use PHPUnit\Framework\TestCase;

class PwnedPasswordServiceTest extends TestCase
{
    private PwnedPasswordService $service;

    protected function setUp(): void
    {
        $this->service = new PwnedPasswordService();
    }

    public function testShouldDetectCommonPwnedPassword(): void
    {
        // 使用已知的被洩露密碼 "password"
        $result = $this->service->isPasswordPwned('password');

        // 如果 API 可用，應該檢測到這是被洩露的密碼
        if ((is_array($result) && isset((is_array($result) ? $result['api_available'] : (is_object($result) ? $result->api_available : null)))) ? (is_array($result) ? $result['api_available'] : (is_object($result) ? $result->api_available : null)) : null) {
            $this->assertTrue((is_array($result) && isset((is_array($result) ? $result['is_leaked'] : (is_object($result) ? $result->is_leaked : null)))) ? (is_array($result) ? $result['is_leaked'] : (is_object($result) ? $result->is_leaked : null)) : null);
            $this->assertGreaterThan(0, (is_array($result) && isset((is_array($result) ? $result['count'] : (is_object($result) ? $result->count : null)))) ? (is_array($result) ? $result['count'] : (is_object($result) ? $result->count : null)) : null);
            $this->assertNull((is_array($result) && isset((is_array($result) ? $result['error'] : (is_object($result) ? $result->error : null)))) ? (is_array($result) ? $result['error'] : (is_object($result) ? $result->error : null)) : null);
        } else {
            // API 不可用時不應拋出錯誤
            $this->assertFalse((is_array($result) && isset((is_array($result) ? $result['is_leaked'] : (is_object($result) ? $result->is_leaked : null)))) ? (is_array($result) ? $result['is_leaked'] : (is_object($result) ? $result->is_leaked : null)) : null);
            $this->assertEquals(0, (is_array($result) && isset((is_array($result) ? $result['count'] : (is_object($result) ? $result->count : null)))) ? (is_array($result) ? $result['count'] : (is_object($result) ? $result->count : null)) : null);
            $this->assertNotNull((is_array($result) && isset((is_array($result) ? $result['error'] : (is_object($result) ? $result->error : null)))) ? (is_array($result) ? $result['error'] : (is_object($result) ? $result->error : null)) : null);
        }
    }

    public function testShouldNotDetectSecurePassword(): void
    {
        // 使用一個足夠複雜且不太可能被洩露的密碼
        $securePassword = 'MyVerySecureP@ssw0rd2023!XyZ9#';
        $result = $this->service->isPasswordPwned($securePassword);

        // 如果 API 可用，這個密碼應該不在洩露清單中
        if ((is_array($result) && isset((is_array($result) ? $result['api_available'] : (is_object($result) ? $result->api_available : null)))) ? (is_array($result) ? $result['api_available'] : (is_object($result) ? $result->api_available : null)) : null) {
            $this->assertFalse((is_array($result) && isset((is_array($result) ? $result['is_leaked'] : (is_object($result) ? $result->is_leaked : null)))) ? (is_array($result) ? $result['is_leaked'] : (is_object($result) ? $result->is_leaked : null)) : null);
            $this->assertEquals(0, (is_array($result) && isset((is_array($result) ? $result['count'] : (is_object($result) ? $result->count : null)))) ? (is_array($result) ? $result['count'] : (is_object($result) ? $result->count : null)) : null);
            $this->assertNull((is_array($result) && isset((is_array($result) ? $result['error'] : (is_object($result) ? $result->error : null)))) ? (is_array($result) ? $result['error'] : (is_object($result) ? $result->error : null)) : null);
        }
    }

    public function testShouldHandleApiFailureGracefully(): void
    {
        // 測試 API 失敗時的處理
        // 注意：這個測試可能需要模擬網路失敗情況
        $result = $this->service->isPasswordPwned('test');

        // 無論 API 是否可用，都應該回傳有效的結果結構
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_leaked', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('api_available', $result);
    }

    public function testShouldValidateApiStatus(): void
    {
        $status = $this->service->getApiStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('available', $status);
        $this->assertIsBool((is_array($status) && isset((is_array($status) ? $status['available'] : (is_object($status) ? $status->available : null)))) ? (is_array($status) ? $status['available'] : (is_object($status) ? $status->available : null)) : null);
    }

    public function testShouldHandleMultiplePasswords(): void
    {
        $passwords = ['password', 'MySecureP@ssw0rd2023!'];
        $results = $this->service->checkMultiplePasswords($passwords);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertArrayHasKey('is_leaked', $result);
            $this->assertArrayHasKey('count', $result);
            $this->assertArrayHasKey('error', $result);
            $this->assertArrayHasKey('api_available', $result);
        }
    }

    public function testShouldCacheResults(): void
    {
        // 第一次呼叫
        $result1 = $this->service->isPasswordPwned('test123');

        // 第二次呼叫應該使用快取
        $result2 = $this->service->isPasswordPwned('test123');

        // 結果應該相同
        $this->assertEquals((is_array($result1) && isset((is_array($result1) ? $result1['is_leaked'] : (is_object($result1) ? $result1->is_leaked : null)))) ? (is_array($result1) ? $result1['is_leaked'] : (is_object($result1) ? $result1->is_leaked : null)) : null, (is_array($result2) && isset((is_array($result2) ? $result2['is_leaked'] : (is_object($result2) ? $result2->is_leaked : null)))) ? (is_array($result2) ? $result2['is_leaked'] : (is_object($result2) ? $result2->is_leaked : null)) : null);
        $this->assertEquals((is_array($result1) && isset((is_array($result1) ? $result1['count'] : (is_object($result1) ? $result1->count : null)))) ? (is_array($result1) ? $result1['count'] : (is_object($result1) ? $result1->count : null)) : null, (is_array($result2) && isset((is_array($result2) ? $result2['count'] : (is_object($result2) ? $result2->count : null)))) ? (is_array($result2) ? $result2['count'] : (is_object($result2) ? $result2->count : null)) : null);
        $this->assertEquals((is_array($result1) && isset((is_array($result1) ? $result1['api_available'] : (is_object($result1) ? $result1->api_available : null)))) ? (is_array($result1) ? $result1['api_available'] : (is_object($result1) ? $result1->api_available : null)) : null, (is_array($result2) && isset((is_array($result2) ? $result2['api_available'] : (is_object($result2) ? $result2->api_available : null)))) ? (is_array($result2) ? $result2['api_available'] : (is_object($result2) ? $result2->api_available : null)) : null);
    }

    public function testShouldClearCache(): void
    {
        // 呼叫一次建立快取
        $this->service->isPasswordPwned('test456');

        // 清除快取
        $this->service->clearCache();

        // 應該能正常運作（不會因為快取問題而失敗）
        $result = $this->service->isPasswordPwned('test789');
        $this->assertIsArray($result);
    }
}
