<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Contracts;

use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Refresh Token Repository 介面測試.
 *
 * 驗證RefreshTokenRepositoryInterface的介面定義和契約正確性。
 * 確保所有方法簽名、參數類型、回傳類型正確。
 */
class RefreshTokenRepositoryInterfaceTest extends TestCase
{
    private ReflectionClass $interfaceReflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interfaceReflection = new ReflectionClass(RefreshTokenRepositoryInterface::class);
    }

    public function testInterfaceExists(): void
    {
        $this->assertTrue($this->interfaceReflection->isInterface());
        $this->assertEquals(RefreshTokenRepositoryInterface::class, $this->interfaceReflection->getName());
    }

    public function testCreateMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('create'));

        $method = $this->interfaceReflection->getMethod('create');
        $this->assertTrue($method->isPublic());

        $parameters = $method->getParameters();
        $this->assertCount(6, $parameters);

        $this->assertEquals('jti', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('userId', $parameters[1]->getName());
        $this->assertEquals('int', $parameters[1]->getType()->getName());

        $this->assertEquals('tokenHash', $parameters[2]->getName());
        $this->assertEquals('string', $parameters[2]->getType()->getName());

        $this->assertEquals('expiresAt', $parameters[3]->getName());
        $this->assertEquals(DateTime::class, $parameters[3]->getType()->getName());

        $this->assertEquals('deviceInfo', $parameters[4]->getName());
        $this->assertEquals(DeviceInfo::class, $parameters[4]->getType()->getName());

        $this->assertEquals('parentTokenJti', $parameters[5]->getName());
        $this->assertTrue($parameters[5]->getType()->allowsNull());
        $this->assertTrue($parameters[5]->isDefaultValueAvailable());
        $this->assertNull($parameters[5]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
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
        $this->assertTrue(($returnType instanceof ReflectionNamedType ? $returnType->allowsNull() : false));
        $this->assertEquals('array<mixed>', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testFindByTokenHashMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('findByTokenHash'));

        $method = $this->interfaceReflection->getMethod('findByTokenHash');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('tokenHash', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertTrue(($returnType instanceof ReflectionNamedType ? $returnType->allowsNull() : false));
        $this->assertEquals('array<mixed>', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testFindByUserIdMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('findByUserId'));

        $method = $this->interfaceReflection->getMethod('findByUserId');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('includeExpired', $parameters[1]->getName());
        $this->assertEquals('bool', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertFalse($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array<mixed>', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testFindByUserIdAndDeviceMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('findByUserIdAndDevice'));

        $method = $this->interfaceReflection->getMethod('findByUserIdAndDevice');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('deviceId', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('array<mixed>', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testUpdateLastUsedMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('updateLastUsed'));

        $method = $this->interfaceReflection->getMethod('updateLastUsed');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('jti', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('lastUsedAt', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals(DateTime::class, $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testRevokeMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('revoke'));

        $method = $this->interfaceReflection->getMethod('revoke');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('jti', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('reason', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testRevokeAllByUserIdMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('revokeAllByUserId'));

        $method = $this->interfaceReflection->getMethod('revokeAllByUserId');
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('reason', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());

        $this->assertEquals('excludeJti', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[2]->getType()->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertNull($parameters[2]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testRevokeAllByDeviceMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('revokeAllByDevice'));

        $method = $this->interfaceReflection->getMethod('revokeAllByDevice');
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('deviceId', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());

        $this->assertEquals('reason', $parameters[2]->getName());
        $this->assertEquals('string', $parameters[2]->getType()->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testDeleteMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('delete'));

        $method = $this->interfaceReflection->getMethod('delete');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('jti', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testValidationMethods(): void
    {
        $validationMethods = ['isRevoked', 'isExpired', 'isValid'];

        foreach ($validationMethods as $methodName) {
            $this->assertTrue($this->interfaceReflection->hasMethod($methodName));

            $method = $this->interfaceReflection->getMethod($methodName);
            $parameters = $method->getParameters();
            $this->assertCount(1, $parameters);

            $this->assertEquals('jti', $parameters[0]->getName());
            $this->assertEquals('string', $parameters[0]->getType()->getName());

            $returnType = $method->getReturnType();
            $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
        }
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
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testCleanupRevokedMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('cleanupRevoked'));

        $method = $this->interfaceReflection->getMethod('cleanupRevoked');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('days', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(30, $parameters[0]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testGetUserTokenStatsMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getUserTokenStats'));

        $method = $this->interfaceReflection->getMethod('getUserTokenStats');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('array<mixed>', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testTokenFamilyMethods(): void
    {
        $familyMethods = [
            'getTokenFamily' => ['rootJti' => 'string'],
            'revokeTokenFamily' => ['rootJti' => 'string', 'reason' => 'string'],
        ];

        foreach ($familyMethods as $methodName => $expectedParams) {
            $this->assertTrue($this->interfaceReflection->hasMethod($methodName));

            $method = $this->interfaceReflection->getMethod($methodName);
            $parameters = $method->getParameters();

            $this->assertCount(count($expectedParams), $parameters);

            $paramIndex = 0;
            foreach ($expectedParams as $paramName => $paramType) {
                $this->assertEquals($paramName, $parameters[$paramIndex]->getName());
                $this->assertEquals($paramType, $parameters[$paramIndex]->getType()->getName());
                $paramIndex++;
            }
        }
    }

    public function testBatchMethods(): void
    {
        $batchMethods = ['batchCreate', 'batchRevoke'];

        foreach ($batchMethods as $methodName) {
            $this->assertTrue($this->interfaceReflection->hasMethod($methodName));

            $method = $this->interfaceReflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
        }
    }

    public function testGetTokensNearExpiryMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getTokensNearExpiry'));

        $method = $this->interfaceReflection->getMethod('getTokensNearExpiry');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('thresholdHours', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertEquals(24, $parameters[0]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('array<mixed>', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testGetSystemStatsMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getSystemStats'));

        $method = $this->interfaceReflection->getMethod('getSystemStats');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('array<mixed>', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testAllRequiredMethodsExist(): void
    {
        $expectedMethods = [
            'create',
            'findByJti',
            'findByTokenHash',
            'findByUserId',
            'findByUserIdAndDevice',
            'updateLastUsed',
            'revoke',
            'revokeAllByUserId',
            'revokeAllByDevice',
            'delete',
            'isRevoked',
            'isExpired',
            'isValid',
            'cleanup',
            'cleanupRevoked',
            'getUserTokenStats',
            'getTokenFamily',
            'revokeTokenFamily',
            'batchCreate',
            'batchRevoke',
            'getTokensNearExpiry',
            'getSystemStats',
        ];

        $actualMethods = array_map(
            fn($method) => ($method instanceof ReflectionNamedType ? $method->getName() : (string) $method),
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
        $this->assertStringContainsString('Refresh Token Repository 介面', $docComment);
        $this->assertStringContainsString('定義refresh token的資料存取操作', $docComment);
    }

    public function testInterfaceIsInCorrectNamespace(): void
    {
        $this->assertEquals('App\Domains\Auth\Contracts', $this->interfaceReflection->getNamespaceName());
    }
}
