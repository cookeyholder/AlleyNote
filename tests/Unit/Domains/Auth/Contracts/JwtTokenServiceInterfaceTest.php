<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Contracts;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Domains\Auth\ValueObjects\TokenPair;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * JWT Token Service 介面測試.
 *
 * 驗證JwtTokenServiceInterface的介面定義和契約正確性。
 * 確保所有方法簽名、參數類型、回傳類型和例外定義正確。
 */
class JwtTokenServiceInterfaceTest extends TestCase
{
    private ReflectionClass $interfaceReflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interfaceReflection = new ReflectionClass(JwtTokenServiceInterface::class);
    }

    public function testInterfaceExists(): void
    {
        $this->assertTrue($this->interfaceReflection->isInterface());
        $this->assertEquals(JwtTokenServiceInterface::class, $this->interfaceReflection->getName());
    }

    public function testGenerateTokenPairMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('generateTokenPair'));

        $method = $this->interfaceReflection->getMethod('generateTokenPair');
        $this->assertTrue($method->isPublic());
        $this->assertEquals('generateTokenPair', ($method instanceof ReflectionNamedType ? $method->getName() : (string) $method));

        // 檢查參數
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('deviceInfo', $parameters[1]->getName());
        $this->assertEquals(DeviceInfo::class, $parameters[1]->getType()->getName());

        $this->assertEquals('customClaims', $parameters[2]->getName());
        $this->assertEquals('array<mixed>', $parameters[2]->getType()->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());

        // 檢查回傳類型
        $returnType = $method->getReturnType();
        $this->assertEquals(TokenPair::class, ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testValidateAccessTokenMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('validateAccessToken'));

        $method = $this->interfaceReflection->getMethod('validateAccessToken');
        $this->assertTrue($method->isPublic());

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('checkBlacklist', $parameters[1]->getName());
        $this->assertEquals('bool', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertTrue($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals(JwtPayload::class, ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testValidateRefreshTokenMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('validateRefreshToken'));

        $method = $this->interfaceReflection->getMethod('validateRefreshToken');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('checkBlacklist', $parameters[1]->getName());
        $this->assertEquals('bool', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals(JwtPayload::class, ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testExtractPayloadMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('extractPayload'));

        $method = $this->interfaceReflection->getMethod('extractPayload');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals(JwtPayload::class, ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testRefreshTokensMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('refreshTokens'));

        $method = $this->interfaceReflection->getMethod('refreshTokens');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('refreshToken', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('deviceInfo', $parameters[1]->getName());
        $this->assertEquals(DeviceInfo::class, $parameters[1]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals(TokenPair::class, ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testRevokeTokenMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('revokeToken'));

        $method = $this->interfaceReflection->getMethod('revokeToken');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('reason', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testRevokeAllUserTokensMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('revokeAllUserTokens'));

        $method = $this->interfaceReflection->getMethod('revokeAllUserTokens');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        $this->assertEquals('reason', $parameters[1]->getName());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testIsTokenRevokedMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('isTokenRevoked'));

        $method = $this->interfaceReflection->getMethod('isTokenRevoked');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testGetTokenRemainingTimeMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getTokenRemainingTime'));

        $method = $this->interfaceReflection->getMethod('getTokenRemainingTime');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testIsTokenNearExpiryMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('isTokenNearExpiry'));

        $method = $this->interfaceReflection->getMethod('isTokenNearExpiry');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('thresholdSeconds', $parameters[1]->getName());
        $this->assertEquals('int', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals(300, $parameters[1]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testIsTokenOwnedByMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('isTokenOwnedBy'));

        $method = $this->interfaceReflection->getMethod('isTokenOwnedBy');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('userId', $parameters[1]->getName());
        $this->assertEquals('int', $parameters[1]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testIsTokenFromDeviceMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('isTokenFromDevice'));

        $method = $this->interfaceReflection->getMethod('isTokenFromDevice');
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('deviceInfo', $parameters[1]->getName());
        $this->assertEquals(DeviceInfo::class, $parameters[1]->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('bool', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testGetAlgorithmMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getAlgorithm'));

        $method = $this->interfaceReflection->getMethod('getAlgorithm');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('string', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testGetAccessTokenTtlMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getAccessTokenTtl'));

        $method = $this->interfaceReflection->getMethod('getAccessTokenTtl');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testGetRefreshTokenTtlMethodSignature(): void
    {
        $this->assertTrue($this->interfaceReflection->hasMethod('getRefreshTokenTtl'));

        $method = $this->interfaceReflection->getMethod('getRefreshTokenTtl');
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);

        $returnType = $method->getReturnType();
        $this->assertEquals('int', ($returnType instanceof ReflectionNamedType ? $returnType->getName() : (string) $returnType));
    }

    public function testAllRequiredMethodsExist(): void
    {
        $expectedMethods = [
            'generateTokenPair',
            'validateAccessToken',
            'validateRefreshToken',
            'extractPayload',
            'refreshTokens',
            'revokeToken',
            'revokeAllUserTokens',
            'isTokenRevoked',
            'getTokenRemainingTime',
            'isTokenNearExpiry',
            'isTokenOwnedBy',
            'isTokenFromDevice',
            'getAlgorithm',
            'getAccessTokenTtl',
            'getRefreshTokenTtl',
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
        $this->assertStringContainsString('JWT Token 服務介面', $docComment);
        $this->assertStringContainsString('定義JWT token的核心操作方法', $docComment);
    }

    public function testInterfaceIsInCorrectNamespace(): void
    {
        $this->assertEquals('App\Domains\Auth\Contracts', $this->interfaceReflection->getNamespaceName());
    }
}
