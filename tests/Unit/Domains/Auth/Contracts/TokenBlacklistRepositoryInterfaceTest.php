<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Contracts;

use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Token Blacklist Repository 介面測試.
 *
 * 驗證TokenBlacklistRepositoryInterface的介面定義和契約正確性。
 * 確保所有方法簽名、參數類型、回傳類型正確。
 */
class TokenBlacklistRepositoryInterfaceTest extends TestCase
{
    private ReflectionClass $interfaceReflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interfaceReflection = new ReflectionClass(TokenBlacklistRepositoryInterface::class);
    }

    public function testInterfaceExists(): void
    {
        $this->assertTrue($this->interfaceReflection->isInterface());
        $this->assertEquals(TokenBlacklistRepositoryInterface::class, $this->interfaceReflection->getName());
    }

    public function testAddToBlacklistMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('addToBlacklist'));

        $method = $this->interfaceReflection->getMethod('addToBlacklist');
        $this->assertTrue($method->isPublic());

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('entry', $parameters[0]->getName());
        $this->assertEquals(TokenBlacklistEntry::class, $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testIsBlacklistedMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('isBlacklisted'));

        $method = $this->interfaceReflection->getMethod('isBlacklisted');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('jti', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testIsTokenHashBlacklistedMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('isTokenHashBlacklisted'));

        $method = $this->interfaceReflection->getMethod('isTokenHashBlacklisted');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('tokenHash', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testRemoveFromBlacklistMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('removeFromBlacklist'));

        $method = $this->interfaceReflection->getMethod('removeFromBlacklist');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('jti', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testFindByJtiMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('findByJti'));

        $method = $this->interfaceReflection->getMethod('findByJti');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('jti', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals(TokenBlacklistEntry::class, $returnType->getName());
    }

    public function testFindByUserIdMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('findByUserId'));

        $method = $this->interfaceReflection->getMethod('findByUserId');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('limit', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testFindByDeviceIdMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('findByDeviceId'));

        $method = $this->interfaceReflection->getMethod('findByDeviceId');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('deviceId', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('limit', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testFindByReasonMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('findByReason'));

        $method = $this->interfaceReflection->getMethod('findByReason');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('reason', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('limit', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testBatchAddToBlacklistMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('batchAddToBlacklist'));

        $method = $this->interfaceReflection->getMethod('batchAddToBlacklist');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('entries', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testBatchIsBlacklistedMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('batchIsBlacklisted'));

        $method = $this->interfaceReflection->getMethod('batchIsBlacklisted');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('jtis', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testBatchRemoveFromBlacklistMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('batchRemoveFromBlacklist'));

        $method = $this->interfaceReflection->getMethod('batchRemoveFromBlacklist');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('jtis', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testBlacklistAllUserTokensMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('blacklistAllUserTokens'));

        $method = $this->interfaceReflection->getMethod('blacklistAllUserTokens');
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('reason', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());

        $this->assertEquals('excludeJti', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[2]->getType()->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertNull($parameters[2]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testBlacklistAllDeviceTokensMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('blacklistAllDeviceTokens'));

        $method = $this->interfaceReflection->getMethod('blacklistAllDeviceTokens');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('deviceId', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('reason', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testCleanupMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('cleanup'));

        $method = $this->interfaceReflection->getMethod('cleanup');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('beforeDate', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->getType()->allowsNull());
        $this->assertEquals(DateTime::class, $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertNull($parameters[0]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testCleanupExpiredEntriesMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('cleanupExpiredEntries'));

        $method = $this->interfaceReflection->getMethod('cleanupExpiredEntries');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testCleanupOldEntriesMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('cleanupOldEntries'));

        $method = $this->interfaceReflection->getMethod('cleanupOldEntries');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('days', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(90, $parameters[0]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testGetBlacklistStatsMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getBlacklistStats'));

        $method = $this->interfaceReflection->getMethod('getBlacklistStats');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testGetUserBlacklistStatsMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getUserBlacklistStats'));

        $method = $this->interfaceReflection->getMethod('getUserBlacklistStats');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testGetRecentBlacklistEntriesMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getRecentBlacklistEntries'));

        $method = $this->interfaceReflection->getMethod('getRecentBlacklistEntries');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('limit', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(100, $parameters[0]->getDefaultValue());

        $this->assertEquals('since', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals(DateTime::class, $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testGetHighPriorityEntriesMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getHighPriorityEntries'));

        $method = $this->interfaceReflection->getMethod('getHighPriorityEntries');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('limit', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(50, $parameters[0]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testSearchMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('search'));

        $method = $this->interfaceReflection->getMethod('search');
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('criteria', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());

        $this->assertEquals('limit', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());

        $this->assertEquals('offset', $parameters[2]->getName());
        $this->assertEquals('int', $parameters[2]->getType()->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertEquals(0, $parameters[2]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testCountSearchMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('countSearch'));

        $method = $this->interfaceReflection->getMethod('countSearch');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('criteria', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', $returnType->getName());
    }

    public function testIsSizeExceededMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('isSizeExceeded'));

        $method = $this->interfaceReflection->getMethod('isSizeExceeded');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('maxSize', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(100000, $parameters[0]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testGetSizeInfoMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getSizeInfo'));

        $method = $this->interfaceReflection->getMethod('getSizeInfo');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testOptimizeMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('optimize'));

        $method = $this->interfaceReflection->getMethod('optimize');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testAllRequiredMethodsExist(): void
    {
        $expectedMethods = [
            'addToBlacklist', 'isBlacklisted', 'isTokenHashBlacklisted', 'removeFromBlacklist',
            'findByJti', 'findByUserId', 'findByDeviceId', 'findByReason', 'batchAddToBlacklist',
            'batchIsBlacklisted', 'batchRemoveFromBlacklist', 'blacklistAllUserTokens', 'blacklistAllDeviceTokens',
            'cleanup', 'cleanupExpiredEntries', 'cleanupOldEntries', 'getBlacklistStats', 'getUserBlacklistStats',
            'getRecentBlacklistEntries', 'getHighPriorityEntries', 'search', 'countSearch',
            'isSizeExceeded', 'getSizeInfo', 'optimize',
        ];

        $actualMethods = array_map(
            fn($method) => $method->getName(),
            $this->interfaceReflection->getMethods(),
        );

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $actualMethods, "Method {$expectedMethod} is missing from interface");
        }
    }

    public function testInterfaceHasCorrectDocumentation(): void
    {
        $docComment = $this->interfaceReflection->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Token 黑名單 Repository 介面', $docComment);
        $this->assertStringContainsString('定義token黑名單的資料存取操作', $docComment);
    }

    public function testInterfaceIsInCorrectNamespace(): void
    {
        $this->assertEquals('App\Domains\Auth\Contracts', $this->interfaceReflection->getNamespaceName());
    }

    public function testMethodsArePublic(): void
    {
        $methods = $this->interfaceReflection->getMethods();

        foreach ($methods as $method) {
            $this->assertTrue($method->isPublic(), "Method {$method->getName()} should be public");
            $this->assertFalse($method->isStatic(), "Method {$method->getName()} should not be static");
        }
    }

    public function testInterfaceExtendsNoOtherInterface(): void
    {
        $interfaces = $this->interfaceReflection->getInterfaces();
        $this->assertEmpty($interfaces, 'TokenBlacklistRepositoryInterface should not extend any other interfaces');
    }

    public function testInterfaceHasNoConstants(): void
    {
        $constants = $this->interfaceReflection->getConstants();
        $this->assertEmpty($constants, 'TokenBlacklistRepositoryInterface should not define any constants');
    }

    public function testInterfaceHasNoProperties(): void
    {
        $properties = $this->interfaceReflection->getProperties();
        $this->assertEmpty($properties, 'TokenBlacklistRepositoryInterface should not define any properties');
    }
}
