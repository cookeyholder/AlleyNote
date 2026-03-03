<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Traits;

use App\Infrastructure\Http\Response;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Support\Traits\HttpResponseTestTrait;
use Tests\Support\UnitTestCase;

class HttpResponseTestTraitTest extends UnitTestCase
{
    use HttpResponseTestTrait;

    public function testCreateJsonResponseShouldReturnRealResponse(): void
    {
        $data = ['success' => true, 'id' => 123];
        $response = $this->createJsonResponse($data, 201);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $response->getBody()->rewind();
        $this->assertEquals(json_encode($data), $response->getBody()->getContents());
    }

    public function testAssertJsonResponseMatchesShouldPassWithPartialMatch(): void
    {
        $data = [
            'success' => true,
            'user' => [
                'id' => 1,
                'name' => 'John',
            ],
            'meta' => 'extra',
        ];
        $response = $this->createJsonResponse($data);

        // 僅驗證部分欄位與巢狀結構
        $this->assertJsonResponseMatches($response, [
            'success' => true,
            'user' => [
                'id' => 1,
            ],
        ]);
    }

    public function testAssertJsonResponseMatchesShouldFailWhenValueDiffers(): void
    {
        $response = $this->createJsonResponse(['status' => 'ok']);

        $this->expectException(AssertionFailedError::class);
        $this->assertJsonResponseMatches($response, ['status' => 'error']);
    }
}
