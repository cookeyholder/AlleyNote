<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\AuthorizationResult;
use PHPUnit\Framework\TestCase;

/**
 * 授權結果測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class AuthorizationResultTest extends TestCase
{
    public function testCanCreateAllowedResult(): void
    {
        $result = new AuthorizationResult(
            allowed: true,
            reason: '測試允許',
            code: 'TEST_ALLOW',
            appliedRules: ['test_rule'],
            metadata: ['test' => 'value'],
        );

        $this->assertTrue($result->isAllowed());
        $this->assertFalse($result->isDenied());
        $this->assertSame('測試允許', $result->getReason());
        $this->assertSame('TEST_ALLOW', $result->getCode());
        $this->assertSame(['test_rule'], $result->getAppliedRules());
        $this->assertSame(['test' => 'value'], $result->getMetadata());
        $this->assertSame('value', $result->getMetadataValue('test'));
        $this->assertNull($result->getMetadataValue('nonexistent'));
        $this->assertSame('default', $result->getMetadataValue('nonexistent', 'default'));
    }

    public function testCanCreateDeniedResult(): void
    {
        $result = new AuthorizationResult(
            allowed: false,
            reason: '測試拒絕',
            code: 'TEST_DENY',
        );

        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isDenied());
        $this->assertSame('測試拒絕', $result->getReason());
        $this->assertSame('TEST_DENY', $result->getCode());
        $this->assertEmpty($result->getAppliedRules());
        $this->assertEmpty($result->getMetadata());
    }

    public function testStaticAllowMethod(): void
    {
        $result = AuthorizationResult::allow(
            reason: '靜態允許',
            code: 'STATIC_ALLOW',
            appliedRules: ['static_rule'],
        );

        $this->assertTrue($result->isAllowed());
        $this->assertSame('靜態允許', $result->getReason());
        $this->assertSame('STATIC_ALLOW', $result->getCode());
        $this->assertSame(['static_rule'], $result->getAppliedRules());
    }

    public function testStaticDenyMethod(): void
    {
        $result = AuthorizationResult::deny(
            reason: '靜態拒絕',
            code: 'STATIC_DENY',
            appliedRules: ['static_rule'],
        );

        $this->assertFalse($result->isAllowed());
        $this->assertSame('靜態拒絕', $result->getReason());
        $this->assertSame('STATIC_DENY', $result->getCode());
        $this->assertSame(['static_rule'], $result->getAppliedRules());
    }

    public function testStaticAllowSuperAdminMethod(): void
    {
        $result = AuthorizationResult::allowSuperAdmin();

        $this->assertTrue($result->isAllowed());
        $this->assertSame('超級管理員擁有所有權限', $result->getReason());
        $this->assertSame('SUPER_ADMIN_ACCESS', $result->getCode());
        $this->assertSame(['super_admin'], $result->getAppliedRules());
    }

    public function testStaticDenyInsufficientPermissionsMethod(): void
    {
        $result = AuthorizationResult::denyInsufficientPermissions('posts', 'delete');

        $this->assertFalse($result->isAllowed());
        $this->assertSame('使用者無權限執行操作：delete on posts', $result->getReason());
        $this->assertSame('INSUFFICIENT_PERMISSIONS', $result->getCode());
        $this->assertSame(['permission_check'], $result->getAppliedRules());
    }

    public function testStaticDenyNotAuthenticatedMethod(): void
    {
        $result = AuthorizationResult::denyNotAuthenticated();

        $this->assertFalse($result->isAllowed());
        $this->assertSame('使用者未認證', $result->getReason());
        $this->assertSame('NOT_AUTHENTICATED', $result->getCode());
        $this->assertSame(['authentication_check'], $result->getAppliedRules());
    }

    public function testStaticDenyIpRestrictionMethod(): void
    {
        $result = AuthorizationResult::denyIpRestriction('192.168.1.100');

        $this->assertFalse($result->isAllowed());
        $this->assertSame('IP 位址 192.168.1.100 被限制存取', $result->getReason());
        $this->assertSame('IP_RESTRICTION', $result->getCode());
        $this->assertSame(['ip_restriction'], $result->getAppliedRules());
    }

    public function testStaticDenyTimeRestrictionMethod(): void
    {
        $result = AuthorizationResult::denyTimeRestriction('delete');

        $this->assertFalse($result->isAllowed());
        $this->assertSame('操作 delete 在當前時間不被允許', $result->getReason());
        $this->assertSame('TIME_RESTRICTION', $result->getCode());
        $this->assertSame(['time_restriction'], $result->getAppliedRules());
    }

    public function testHasRuleMethod(): void
    {
        $result = new AuthorizationResult(
            allowed: true,
            reason: '測試',
            code: 'TEST',
            appliedRules: ['rule1', 'rule2', 'rule3'],
        );

        $this->assertTrue($result->hasRule('rule1'));
        $this->assertTrue($result->hasRule('rule2'));
        $this->assertFalse($result->hasRule('nonexistent_rule'));
    }

    public function testToArrayMethod(): void
    {
        $result = new AuthorizationResult(
            allowed: true,
            reason: '測試轉換',
            code: 'TEST_ARRAY',
            appliedRules: ['test_rule'],
            metadata: ['key' => 'value'],
        );

        $expected = [
            'allowed' => true,
            'reason' => '測試轉換',
            'code' => 'TEST_ARRAY',
            'applied_rules' => ['test_rule'],
            'metadata' => ['key' => 'value'],
        ];

        $this->assertSame($expected, $result->toArray());
    }

    public function testJsonSerializableInterface(): void
    {
        $result = new AuthorizationResult(
            allowed: false,
            reason: '測試 JSON',
            code: 'TEST_JSON',
            appliedRules: ['json_rule'],
        );

        $jsonString = json_encode($result);
        $decodedData = json_decode($jsonString, true);

        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('allowed', $decodedData);
        $this->assertArrayHasKey('reason', $decodedData);
        $this->assertArrayHasKey('code', $decodedData);
        $this->assertArrayHasKey('applied_rules', $decodedData);
        $this->assertSame(false, $decodedData['allowed']);
        $this->assertSame('測試 JSON', $decodedData['reason']);
        $this->assertSame('TEST_JSON', $decodedData['code']);
        $this->assertSame(['json_rule'], $decodedData['applied_rules']);
    }

    public function testEqualsMethod(): void
    {
        $result1 = new AuthorizationResult(
            allowed: true,
            reason: '測試相等',
            code: 'TEST_EQUALS',
            appliedRules: ['rule1'],
            metadata: ['key' => 'value'],
        );

        $result2 = new AuthorizationResult(
            allowed: true,
            reason: '測試相等',
            code: 'TEST_EQUALS',
            appliedRules: ['rule1'],
            metadata: ['key' => 'value'],
        );

        $result3 = new AuthorizationResult(
            allowed: false,
            reason: '不同結果',
            code: 'DIFFERENT',
            appliedRules: ['rule2'],
        );

        $this->assertTrue($result1->equals($result2));
        $this->assertFalse($result1->equals($result3));
    }

    public function testToStringMethod(): void
    {
        $result = new AuthorizationResult(
            allowed: true,
            reason: '測試字串',
            code: 'TEST_STRING',
            appliedRules: ['rule1', 'rule2'],
            metadata: ['key1' => 'value1', 'key2' => 'value2'],
        );

        $expectedString = 'AuthorizationResult(ALLOWED, code=TEST_STRING, reason="測試字串", rules=2, metadata=2)';
        $this->assertSame($expectedString, $result->toString());
        $this->assertSame($expectedString, (string) $result);
    }

    public function testToStringMethodForDeniedResult(): void
    {
        $result = new AuthorizationResult(
            allowed: false,
            reason: '測試拒絕字串',
            code: 'TEST_DENIED',
        );

        $expectedString = 'AuthorizationResult(DENIED, code=TEST_DENIED, reason="測試拒絕字串", rules=0, metadata=0)';
        $this->assertSame($expectedString, $result->toString());
    }

    public function testDefaultParametersForStaticMethods(): void
    {
        $allowResult = AuthorizationResult::allow();
        $this->assertTrue($allowResult->isAllowed());
        $this->assertSame('存取被允許', $allowResult->getReason());
        $this->assertSame('ALLOWED', $allowResult->getCode());
        $this->assertEmpty($allowResult->getAppliedRules());

        $denyResult = AuthorizationResult::deny();
        $this->assertFalse($denyResult->isAllowed());
        $this->assertSame('存取被拒絕', $denyResult->getReason());
        $this->assertSame('DENIED', $denyResult->getCode());
        $this->assertEmpty($denyResult->getAppliedRules());
    }
}
