<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\SettingController;
use App\Domains\Setting\Services\SettingManagementService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(SettingController::class)]
class SettingControllerTest extends TestCase
{
    private SettingController $controller;

    private SettingManagementService&MockInterface $settingManagementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingManagementService = Mockery::mock(SettingManagementService::class);

        $this->controller = new SettingController(
            $this->settingManagementService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testGetTimezoneInfoSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $result = $this->controller->getTimezoneInfo($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testIndexSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->settingManagementService
            ->shouldReceive('getAllSettings')
            ->once()
            ->andReturn([]);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testShowSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')
            ->with('key')
            ->andReturn('site_name');

        $response = $this->createMockResponse();

        $this->settingManagementService
            ->shouldReceive('getSetting')
            ->once()
            ->with('site_name')
            ->andReturn(['key' => 'site_name', 'value' => 'Test Site']);

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    private function createMockRequest(): ServerRequestInterface&MockInterface
    {
        $request = Mockery::mock(ServerRequestInterface::class);

        $request->shouldReceive('getQueryParams')
            ->andReturn([]);

        return $request;
    }

    private function createMockResponse(): ResponseInterface&MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $response->shouldReceive('getBody')
            ->andReturn($stream);

        $stream->shouldReceive('write')
            ->andReturnSelf();

        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        $response->shouldReceive('withStatus')
            ->andReturnSelf();

        return $response;
    }
}
