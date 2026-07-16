<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\CsrfTokenController;
use App\Shared\Config\EnvironmentConfig;
use Tests\Support\UnitTestCase;

/**
 * CSRF Token 控制器單元測試.
 */
final class CsrfTokenControllerTest extends UnitTestCase
{
    private CsrfTokenController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new CsrfTokenController(
            new EnvironmentConfig(),
        );
    }

    public function testGetTokenReturnsJsonWithToken(): void
    {
        $request = $this->createRequest('GET', '/api/csrf-token');
        $response = new \App\Infrastructure\Http\Response();

        $result = $this->controller->getToken($request, $response);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('application/json', $result->getHeaderLine('Content-Type'));

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertIsString($data['token']);
        $this->assertNotEmpty($data['token']);
    }

    public function testGetTokenSetsCsrfCookie(): void
    {
        $request = $this->createRequest('GET', '/api/csrf-token');
        $response = new \App\Infrastructure\Http\Response();

        $result = $this->controller->getToken($request, $response);

        $setCookieHeaders = $result->getHeader('Set-Cookie');
        $this->assertNotEmpty($setCookieHeaders);

        $hasCsrfCookie = false;
        foreach ($setCookieHeaders as $cookie) {
            if (str_starts_with($cookie, 'csrf_token=')) {
                $hasCsrfCookie = true;
                $this->assertStringContainsString('SameSite=Strict', $cookie);
                $this->assertStringContainsString('Path=/', $cookie);
                break;
            }
        }
        $this->assertTrue($hasCsrfCookie, 'Response should contain csrf_token cookie');
    }

    public function testGetTokenReturnsUniqueTokenEachCall(): void
    {
        $request = $this->createRequest('GET', '/api/csrf-token');

        $response1 = new \App\Infrastructure\Http\Response();
        $result1 = $this->controller->getToken($request, $response1);
        $data1 = json_decode((string) $result1->getBody(), true);

        $response2 = new \App\Infrastructure\Http\Response();
        $result2 = $this->controller->getToken($request, $response2);
        $data2 = json_decode((string) $result2->getBody(), true);

        $this->assertNotSame($data1['token'], $data2['token'], 'Each call should generate a unique token');
    }
}
