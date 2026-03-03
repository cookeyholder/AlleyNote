<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\Stream;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP 回應測試功能 Trait.
 */
trait HttpResponseTestTrait
{
    /**
     * 建立 JSON 回應實體.
     */
    protected function createJsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($json ?: '');

        return new Response(
            statusCode: $statusCode,
            headers: ['Content-Type' => 'application/json'],
            body: $stream,
        );
    }

    /**
     * 模擬回應實體.
     */
    protected function createResponseMock(): ResponseInterface|MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->andReturnSelf();
        $response->shouldReceive('withStatus')->andReturnSelf();
        $response->shouldReceive('getStatusCode')->andReturn(200)->byDefault();

        return $response;
    }

    /**
     * 斷言 JSON 回應符合預期.
     */
    protected function assertJsonResponseMatches(ResponseInterface $response, array $expected): void
    {
        $body = (string) $response->getBody();
        $actual = json_decode($body, true);
        $this->assertIsArray($actual, "無法解析回應為 JSON: {$body}");

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            if (is_array($value)) {
                $this->assertArraySubsetRecursive($value, $actual[$key]);
            } else {
                $this->assertEquals($value, $actual[$key]);
            }
        }
    }

    /**
     * 斷言狀態碼.
     */
    protected function assertResponseStatus(ResponseInterface $response, int $expected): void
    {
        $this->assertEquals($expected, $response->getStatusCode());
    }

    /**
     * 遞迴斷言陣列子集.
     */
    protected function assertArraySubsetRecursive(array $subset, array $parent, string $message = ''): void
    {
        foreach ($subset as $key => $value) {
            $this->assertArrayHasKey($key, $parent, $message);
            if (is_array($value)) {
                $this->assertArraySubsetRecursive($value, $parent[$key], $message);
            } else {
                $this->assertEquals($value, $parent[$key], $message);
            }
        }
    }
}
