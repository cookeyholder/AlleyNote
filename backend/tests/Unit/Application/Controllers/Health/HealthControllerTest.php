<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Health;

use App\Application\Controllers\Health\HealthController;
use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * Health\HealthController 單元測試.
 */
#[CoversClass(HealthController::class)]
class HealthControllerTest extends UnitTestCase
{
    private HealthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new HealthController();
    }

    #[Test]
    public function testCheckSuccess(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $writtenContent = '';
        $stream->shouldReceive('write')
            ->once()
            ->andReturnUsing(function (string $str) use (&$writtenContent): int {
                $writtenContent = $str;

                return strlen($str);
            });

        $response->shouldReceive('getBody')->once()->andReturn($stream);
        $response->shouldReceive('withHeader')
            ->once()
            ->with('Content-Type', 'application/json')
            ->andReturnSelf();

        $result = $this->controller->check($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);

        /** @var array{success: bool, data: array{status: string, version: string}} $data */
        $data = json_decode($writtenContent, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertEquals('ok', $data['data']['status']);
        $this->assertEquals('1.0.0', $data['data']['version']);
    }

    #[Test]
    public function testCheckExceptionHandling(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        // Simulate write throwing an exception on first call
        $stream->shouldReceive('write')
            ->once()
            ->andThrow(new Exception('Stream write failed'));

        $stream->shouldReceive('write')
            ->once()
            ->andReturn(20);

        $response->shouldReceive('getBody')->andReturn($stream);
        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'application/json')
            ->andReturnSelf();
        $response->shouldReceive('withStatus')
            ->with(500)
            ->andReturnSelf();

        $result = $this->controller->check($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
