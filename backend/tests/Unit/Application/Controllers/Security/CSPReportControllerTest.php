<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Security;

use App\Application\Controllers\Security\CSPReportController;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * CSPReportController 單元測試.
 */
#[CoversClass(CSPReportController::class)]
class CSPReportControllerTest extends UnitTestCase
{
    private CSPReportController $controller;

    private LoggingSecurityServiceInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $this->controller = new CSPReportController(
            $this->logger,
        );
    }

    #[Test]
    public function testHandleReportMethodNotAllowed(): void
    {
        $request = $this->createMockRequest('GET');
        $response = $this->createMockResponse(405);

        $result = $this->controller->handleReport($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(405, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportInvalidContentType(): void
    {
        $request = $this->createMockRequest('POST', 'text/plain');
        $response = $this->createMockResponse(400);

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportInvalidJsonBody(): void
    {
        $request = $this->createMockRequest('POST', 'application/json', 'invalid json string');
        $response = $this->createMockResponse(400);

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportMissingCspReportKey(): void
    {
        $request = $this->createMockRequest('POST', 'application/csp-report', json_encode(['other' => 'data']) ?: '');
        $response = $this->createMockResponse(400);

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportMissingRequiredFields(): void
    {
        $payload = [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                // missing document-uri and violated-directive
            ],
        ];
        $request = $this->createMockRequest('POST', 'application/csp-report', json_encode($payload) ?: '');
        $response = $this->createMockResponse(400);

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportHighSeverityViolation(): void
    {
        $payload = [
            'csp-report' => [
                'blocked-uri'        => 'eval',
                'document-uri'       => 'https://example.com/admin',
                'violated-directive' => 'script-src \'self\'',
            ],
        ];
        $request = $this->createMockRequest('POST', 'application/csp-report', json_encode($payload) ?: '');
        $request->shouldReceive('getServerParams')->andReturn(['HTTP_CF_CONNECTING_IP' => '1.2.3.4']);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Mozilla/5.0');
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('https://example.com');
        $response = $this->createMockResponse(204);

        $this->logger->shouldReceive('logCriticalSecurityEvent')
            ->once()
            ->with('CSP Violation (High Severity)', Mockery::type('array'));

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(204, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportHighSeverityDomainViolation(): void
    {
        $payload = [
            'csp-report' => [
                'blocked-uri'        => 'http://malicious-site.tk/bad.js',
                'document-uri'       => 'https://example.com/admin',
                'violated-directive' => 'script-src',
            ],
        ];
        $request = $this->createMockRequest('POST', 'application/json', json_encode($payload) ?: '');
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '192.168.1.1']);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Mozilla/5.0');
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('');
        $response = $this->createMockResponse(204);

        $this->logger->shouldReceive('logCriticalSecurityEvent')
            ->once()
            ->with('CSP Violation (High Severity)', Mockery::type('array'));

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(204, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportMediumSeverityViolation(): void
    {
        $payload = [
            'csp-report' => [
                'blocked-uri'        => 'https://other.com/embed',
                'document-uri'       => 'https://example.com/dashboard',
                'violated-directive' => 'frame-ancestors \'none\'',
            ],
        ];
        $request = $this->createMockRequest('POST', 'application/csp-report', json_encode($payload) ?: '');
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Agent');
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('');
        $response = $this->createMockResponse(204);

        $this->logger->shouldReceive('logSecurityEvent')
            ->once()
            ->with('CSP Violation', Mockery::type('array'));

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(204, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportLowSeverityViolation(): void
    {
        $payload = [
            'csp-report' => [
                'blocked-uri'        => 'https://fonts.googleapis.com/css',
                'document-uri'       => 'https://example.com',
                'violated-directive' => 'style-src \'self\'',
            ],
        ];
        $request = $this->createMockRequest('POST', 'application/csp-report', json_encode($payload) ?: '');
        $request->shouldReceive('getServerParams')->andReturn([]);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('');
        $request->shouldReceive('getHeaderLine')->with('Referer')->andReturn('');
        $response = $this->createMockResponse(204);

        $this->logger->shouldReceive('logSecurityEvent')
            ->once()
            ->with('CSP Violation', Mockery::type('array'));

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(204, $result->getStatusCode());
    }

    #[Test]
    public function testHandleReportExceptionHandling(): void
    {
        $request = $this->createMockRequest('POST', 'application/json');
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andThrow(new Exception('Stream read failed'));
        $request->shouldReceive('getBody')->andReturn($stream);
        $response = $this->createMockResponse(500);

        $this->logger->shouldReceive('error')
            ->once()
            ->with('CSP Report handling error', Mockery::type('array'));

        $result = $this->controller->handleReport($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    private function createMockRequest(
        string $method = 'POST',
        string $contentType = 'application/json',
        string $body = '{}',
    ): ServerRequestInterface&MockInterface {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getMethod')->andReturn($method);
        $request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturn($contentType);

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn($body);
        $request->shouldReceive('getBody')->andReturn($stream);

        return $request;
    }

    private function createMockResponse(int $statusCode = 200): ResponseInterface&MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $response->shouldReceive('getBody')
            ->andReturn($stream);

        $stream->shouldReceive('write')
            ->andReturnUsing(fn(string $string): int => strlen($string));

        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        $response->shouldReceive('withStatus')
            ->andReturnUsing(function ($code) use ($response, &$statusCode) {
                $statusCode = $code;

                return $response;
            });

        $response->shouldReceive('getStatusCode')
            ->andReturnUsing(fn() => $statusCode);

        return $response;
    }
}
