<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\IpService;
use App\Repositories\Contracts\IpRepositoryInterface;
use App\Models\IpList;
use Mockery;

class IpServiceTest extends TestCase
{
    private IpRepositoryInterface $repository;
    private IpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(IpRepositoryInterface::class);
        $this->service = new IpService($this->repository);
    }

    public function testCanCreateIpRule(): void
    {
        $data = [
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

        $this->repository->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($expectedIpList);

        $result = $this->service->createIpRule($data);

        $this->assertSame($expectedIpList, $result);
    }

    public function testCannotCreateInvalidIpRule(): void
    {
        $data = [
            'ip_address' => 'invalid-ip',
            'type' => 1
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的 IP 位址格式');

        $this->service->createIpRule($data);
    }

    public function testCannotCreateWithInvalidType(): void
    {
        $data = [
            'ip_address' => '192.168.1.1',
            'type' => 2
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的名單類型，必須是 0（黑名單）或 1（白名單）');

        $this->service->createIpRule($data);
    }

    public function testCanCheckIpAccess(): void
    {
        $ip = '192.168.1.1';

        // 情境 1：IP 在白名單中
        $this->repository->shouldReceive('isWhitelisted')
            ->once()
            ->with($ip)
            ->andReturn(true);

        $this->repository->shouldReceive('isBlacklisted')
            ->never();

        $this->assertTrue($this->service->isIpAllowed($ip));

        // 情境 2：IP 在黑名單中
        $this->repository->shouldReceive('isWhitelisted')
            ->once()
            ->with($ip)
            ->andReturn(false);

        $this->repository->shouldReceive('isBlacklisted')
            ->once()
            ->with($ip)
            ->andReturn(true);

        $this->assertFalse($this->service->isIpAllowed($ip));

        // 情境 3：IP 不在任何名單中
        $this->repository->shouldReceive('isWhitelisted')
            ->once()
            ->with($ip)
            ->andReturn(false);

        $this->repository->shouldReceive('isBlacklisted')
            ->once()
            ->with($ip)
            ->andReturn(false);

        $this->assertTrue($this->service->isIpAllowed($ip));
    }

    public function testCanValidateCidrRange(): void
    {
        $validRanges = [
            '192.168.1.0/24',
            '10.0.0.0/8',
            '172.16.0.0/12'
        ];

        foreach ($validRanges as $range) {
            $data = [
                'ip_address' => $range,
                'type' => 0
            ];

            $mockIpList = new IpList([
                'ip_address' => $range,
                'type' => 0
            ]);

            $this->repository->shouldReceive('create')
                ->once()
                ->with($data)
                ->andReturn($mockIpList);

            $result = $this->service->createIpRule($data);
            $this->assertEquals($range, $result->getIpAddress());
        }
    }

    public function testCanGetRulesByType(): void
    {
        $type = 1; // 白名單
        $mockRules = [
            new IpList([
                'ip_address' => '192.168.1.1',
                'type' => 1
            ]),
            new IpList([
                'ip_address' => '192.168.1.2',
                'type' => 1
            ])
        ];

        $this->repository->shouldReceive('getByType')
            ->once()
            ->with($type)
            ->andReturn($mockRules);

        $result = $this->service->getRulesByType($type);

        $this->assertCount(2, $result);
        $this->assertEquals('192.168.1.1', $result[0]->getIpAddress());
        $this->assertEquals('192.168.1.2', $result[1]->getIpAddress());
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
