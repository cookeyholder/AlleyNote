<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Web;

use App\Application\Controllers\Web\SwaggerController;
use App\Infrastructure\Http\Response;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\UnitTestCase;

/**
 * SwaggerController 單元測試.
 */
#[CoversClass(SwaggerController::class)]
class SwaggerControllerTest extends UnitTestCase
{
    private SwaggerController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SwaggerController();
    }

    #[Test]
    public function testDocsSuccess(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = new Response(200);

        $result = $this->controller->docs($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('*', $result->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('openapi', (string) $result->getBody());
    }

    #[Test]
    public function testYamlSuccess(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = new Response(200);

        $result = $this->controller->yaml($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('text/yaml', $result->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('openapi', (string) $result->getBody());
    }

    #[Test]
    public function testUiWithNonce(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')
            ->with('csp_nonce')
            ->andReturn('test-nonce-123');

        $response = new Response(200);

        $result = $this->controller->ui($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('nonce="test-nonce-123"', (string) $result->getBody());
        $this->assertStringContainsString('SwaggerUIBundle', (string) $result->getBody());
    }

    #[Test]
    public function testUiWithoutNonce(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')
            ->with('csp_nonce')
            ->andReturn(null);

        $response = new Response(200);

        $result = $this->controller->ui($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringNotContainsString('nonce=', (string) $result->getBody());
        $this->assertStringContainsString('SwaggerUIBundle', (string) $result->getBody());
    }

    #[Test]
    public function testInfoSuccess(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = new Response(200);

        $result = $this->controller->info($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $data = json_decode((string) $result->getBody(), true);
        $this->assertIsArray($data);
        $this->assertEquals('AlleyNote API', $data['name']);
        $this->assertEquals('1.0.0', $data['version']);
    }
}
