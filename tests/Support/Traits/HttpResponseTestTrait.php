<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP 回應測試功能 Trait.
 *
 * 提供 HTTP 回應相關的模擬物件和測試輔助方法
 */
trait HttpResponseTestTrait
{
    /**
     * 建立 HTTP 回應的模擬物件.
     */
    protected function createResponseMock(): ResponseInterface|MockInterface
    {
        /** @var ResponseInterface|MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('withJson')
            ->andReturnUsing(function ($data) use ($response) {
                return $response;
            });

        $response->shouldReceive('withStatus')
            ->andReturnSelf();

        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        $response->shouldReceive('getStatusCode')
            ->andReturn(200);

        $response->shouldReceive('getHeaders')
            ->andReturn([]);

        $response->shouldReceive('getHeader')
            ->andReturn([]);

        $response->shouldReceive('hasHeader')
            ->andReturn(false);

        return $response;
    }

    /**
     * 建立帶有指定狀態碼的回應模擬物件.
     */
    protected function createResponseMockWithStatus(int $statusCode): ResponseInterface|MockInterface
    {
        /** @var ResponseInterface|MockInterface $response */
        $response = $this->createResponseMock();

        $response->shouldReceive('getStatusCode')
            ->andReturn($statusCode);

        return $response;
    }

    /**
     * 建立帶有 JSON 內容的回應模擬物件.
     */
    protected function createJsonResponseMock(array<mixed> $data, int $statusCode = 200): ResponseInterface|MockInterface
    {
        /** @var ResponseInterface|MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        // 設定狀態碼
        $response->shouldReceive('getStatusCode')
            ->andReturn($statusCode);

        // 建立 Stream 模擬物件
        /** @var StreamInterface|MockInterface $stream */
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->andReturn(json_encode($data));
        $stream->shouldReceive('__toString')
            ->andReturn(json_encode($data));

        $response->shouldReceive('getBody')
            ->andReturn($stream);

        // 設定標頭
        $response->shouldReceive('getHeader')
            ->with('Content-Type')
            ->andReturn(['application/json']);

        $response->shouldReceive('getHeaders')
            ->andReturn(['Content-Type' => ['application/json']]);

        $response->shouldReceive('hasHeader')
            ->with('Content-Type')
            ->andReturn(true);

        // 設定其他常用方法
        $response->shouldReceive('withJson')
            ->andReturnSelf();
        $response->shouldReceive('withStatus')
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        return $response;
    }

    /**
     * 斷言回應狀態碼
     */
    protected function assertResponseStatus(ResponseInterface $response, int $expectedStatus): void
    {
        $this->assertEquals(
            $expectedStatus,
            $response->getStatusCode(),
            "回應狀態碼不符合預期。預期: {$expectedStatus}，實際: {$response->getStatusCode()}",
        );
    }

    /**
     * 斷言回應包含指定標頭.
     */
    protected function assertResponseHasHeader(ResponseInterface $response, string $headerName): void
    {
        $this->assertTrue(
            $response->hasHeader($headerName),
            "回應應該包含標頭: {$headerName}",
        );
    }

    /**
     * 斷言回應標頭值
     */
    protected function assertResponseHeaderEquals(ResponseInterface $response, string $headerName, string $expectedValue): void
    {
        $this->assertResponseHasHeader($response, $headerName);
        $headerValues = $response->getHeader($headerName);
        $this->assertContains($expectedValue, $headerValues, "標頭 {$headerName} 的值不符合預期");
    }

    /**
     * 斷言回應內容包含指定文字.
     */
    protected function assertResponseContains(ResponseInterface $response, string $expectedText): void
    {
        $body = $response->getBody()->getContents();
        $this->assertStringContainsString(
            $expectedText,
            $body,
            "回應內容應該包含文字: {$expectedText}",
        );
    }

    /**
     * 斷言回應為有效的 JSON.
     */
    protected function assertResponseIsJson(ResponseInterface $response): void
    {
        $body = $response->getBody()->getContents();
        $decoded = json_decode($body, true);

        $this->assertNotNull($decoded, '回應內容應該是有效的 JSON');
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'JSON 解析不應該有錯誤');
    }

    /**
     * 斷言 JSON 回應包含指定鍵.
     */
    protected function assertJsonResponseHasKey(ResponseInterface $response, string $key): void
    {
        $this->assertResponseIsJson($response);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $this->assertArrayHasKey($key, $data, "JSON 回應應該包含鍵: {$key}");
    }

    /**
     * 斷言 JSON 回應的值
     */
    protected function assertJsonResponseValue(ResponseInterface $response, string $key, mixed $expectedValue): void
    {
        $this->assertJsonResponseHasKey($response, $key);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $this->assertEquals($expectedValue, $data[$key], "JSON 回應中鍵 {$key} 的值不符合預期");
    }
}
