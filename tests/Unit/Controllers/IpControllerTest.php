<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Application\Controllers\Api\V1\IpController;
use App\Domains\Security\Models\IpList;
use App\Domains\Security\Services\IpService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class IpControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private IpService|MockInterface $service;

    private ValidatorInterface|MockInterface $validator;

    private OutputSanitizerInterface|MockInterface $sanitizer;

    private IpController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化mock對象
        $this->service = Mockery::mock(IpService::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class);

        // 設定驗證器的通用模擬
        $this->validator->shouldReceive('addRule')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $this->validator->shouldReceive('addMessage')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        // 不在這裡預設 validateOrFail 的行為，讓個別測試自行設定

        $this->controller = new IpController($this->service, $this->validator, $this->sanitizer);
    }

    public function testCanCreateIpRule(): void
    {
        $request = [
            'ip_address' => '192.168.1.1',
            'action' => 'allow',
            'reason' => '測試白名單',
            'created_by' => 1,
        ];

        $expectedIpList = new IpList([
            'id' => 1,
            'uuid' => 'test-uuid',
            'ip_address' => '192.168.1.1',
            'type' => 1,
            'description' => '測試白名單',
        ]);

        // 設定驗證器成功驗證
        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andReturnUsing(fn($data, $rules) => $data);

        // 設定 service 模擬 - 使用 any() 參數匹配
        $this->service->shouldReceive('createIpRule')
            ->once()
            ->with(Mockery::any())
            ->andReturn($expectedIpList);

        // 設定 sanitizer 模擬
        $this->sanitizer->shouldReceive('sanitize')
            ->andReturnUsing(fn($value) => $value);
        $this->sanitizer->shouldReceive('sanitizeHtml')
            ->andReturnUsing(fn($value) => $value);

        $response = $this->controller->create($request);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testCannotCreateWithInvalidData(): void
    {
        $request = [
            'ip_address' => 'invalid-ip',
            'action' => 'invalid_action',
            'created_by' => 1,
        ];

        // 覆蓋 setUp 中的 validateOrFail mock，讓它拋出異常
        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andThrow(new ValidationException(
                new ValidationResult(false, ['ip_address' => ['無效的 IP 位址']], [], []),
            ));

        $response = $this->controller->create($request);

        $this->assertEquals(400, $response['status']);
        $this->assertStringContainsString('無效的 IP 位址', $response['error']);
    }

    public function testCanListRulesByType(): void
    {
        $type = 1;
        $mockRules = [
            new IpList([
                'id' => 1,
                'uuid' => 'test-uuid-1',
                'ip_address' => '192.168.1.1',
                'type' => 1,
            ]),
            new IpList([
                'id' => 2,
                'uuid' => 'test-uuid-2',
                'ip_address' => '192.168.1.2',
                'type' => 1,
            ]),
        ];

        $this->service->shouldReceive('getRulesByType')
            ->once()
            ->with($type)
            ->andReturn($mockRules);

        $response = $this->controller->getByType(['type' => $type]);

        $this->assertEquals(200, $response['status']);
        $this->assertCount(2, $response['data']);
        $this->assertEquals('192.168.1.1', $response['data'][0]['ip_address']);
    }

    public function testCanCheckIpAccess(): void
    {
        $ip = '192.168.1.1';

        $this->service->shouldReceive('isIpAllowed')
            ->once()
            ->with($ip)
            ->andReturn(true);

        $response = $this->controller->checkAccess(['ip' => $ip]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['data']['allowed']);
    }

    public function testCannotCheckInvalidIp(): void
    {
        $ip = 'invalid-ip';

        $this->service->shouldReceive('isIpAllowed')
            ->once()
            ->with($ip)
            ->andThrow(new InvalidArgumentException('無效的 IP 位址格式'));

        $response = $this->controller->checkAccess(['ip' => $ip]);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('無效的 IP 位址格式', $response['error']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
