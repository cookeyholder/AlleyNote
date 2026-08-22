<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers;

use App\Application\Controllers\HealthController;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * HealthController 單元測試.
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
    public function testCheckReturnsOk(): void
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

        /** @var array<string, mixed> $data */
        $data = json_decode($writtenContent, true);
        $this->assertIsArray($data);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('AlleyNote API', $data['service']);
        $this->assertArrayHasKey('timestamp', $data);
    }
}
