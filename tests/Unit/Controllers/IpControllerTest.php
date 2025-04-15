<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\IpController;
use App\Services\IpService;
use App\Models\IpList;
use Mockery;

class IpControllerTest extends TestCase
{
    private IpService $service;
    private IpController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Mockery::mock(IpService::class);
        $this->controller = new IpController($this->service);
    }

    public function testCanCreateIpRule(): void
    {
        $request = [
            'ip_address' => '192.168.1.1',
            'type' => 1,
            'description' => '測試白名單'
        ];

        $expectedIpList = new IpList([
            'id' => 1,
            'uuid' => 'test-uuid',
            'ip_address' => '192.168.1.1',
            'type' => 1,
            'description' => '測試白名單'
        ]);

        $this->service->shouldReceive('createIpRule')
            ->once()
            ->with($request)
            ->andReturn($expectedIpList);

        $response = $this->controller->create($request);

        $this->assertEquals(201, $response['status']);
        $this->assertEquals($expectedIpList->toArray(), $response['data']);
    }

    public function testCannotCreateWithInvalidData(): void
    {
        $request = [
            'ip_address' => 'invalid-ip',
            'type' => 1
        ];

        $this->service->shouldReceive('createIpRule')
            ->once()
            ->with($request)
            ->andThrow(new \InvalidArgumentException('無效的 IP 位址格式'));

        $response = $this->controller->create($request);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('無效的 IP 位址格式', $response['error']);
    }

    public function testCanListRulesByType(): void
    {
        $type = 1;
        $mockRules = [
            new IpList([
                'id' => 1,
                'uuid' => 'test-uuid-1',
                'ip_address' => '192.168.1.1',
                'type' => 1
            ]),
            new IpList([
                'id' => 2,
                'uuid' => 'test-uuid-2',
                'ip_address' => '192.168.1.2',
                'type' => 1
            ])
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
            ->andThrow(new \InvalidArgumentException('無效的 IP 位址格式'));

        $response = $this->controller->checkAccess(['ip' => $ip]);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals('無效的 IP 位址格式', $response['error']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
