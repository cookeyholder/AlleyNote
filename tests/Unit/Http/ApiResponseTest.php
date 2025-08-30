<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Shared\Http\ApiResponse;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $data = ['id' => 1, 'title' => 'Test Post'];
        $message = 'Operation successful';

        $response = ApiResponse::success($data, $message);

        $this->assertTrue($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals($data, $response['data']);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertIsString($response['timestamp']);
    }

    public function testSuccessResponseWithDefaults(): void
    {
        $response = ApiResponse::success();

        $this->assertTrue($response['success']);
        $this->assertEquals('Success', $response['message']);
        $this->assertNull($response['data']);
        $this->assertArrayHasKey('timestamp', $response);
    }

    public function testErrorResponse(): void
    {
        $message = 'Something went wrong';
        $code = 400;
        $errors = ['field' => 'error message'];

        $response = ApiResponse::error($message, $code, $errors);

        $this->assertFalse($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals($code, $response['error_code']);
        $this->assertEquals($errors, $response['errors']);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertIsString($response['timestamp']);
    }

    public function testErrorResponseWithDefaults(): void
    {
        $message = 'Error occurred';

        $response = ApiResponse::error($message);

        $this->assertFalse($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals(400, $response['error_code']);
        $this->assertNull($response['errors']);
        $this->assertArrayHasKey('timestamp', $response);
    }

    public function testPaginatedResponse(): void
    {
        $data = [
            ['id' => 1, 'title' => 'Post 1'],
            ['id' => 2, 'title' => 'Post 2'],
        ];
        $total = 100;
        $page = 2;
        $perPage = 10;

        $response = ApiResponse::paginated($data, $total, $page, $perPage);

        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
        $this->assertArrayHasKey('pagination', $response);

        $pagination = $response['pagination'];
        $this->assertEquals($total, $pagination['total']);
        $this->assertEquals($page, $pagination['page']);
        $this->assertEquals($perPage, $pagination['per_page']);
        $this->assertEquals(ceil($total / $perPage), $pagination['total_pages']);
        $this->assertEquals(10, $pagination['total_pages']); // 100 / 10 = 10

        $this->assertArrayHasKey('timestamp', $response);
        $this->assertIsString($response['timestamp']);
    }

    public function testTimestampFormat(): void
    {
        $response = ApiResponse::success();

        // 驗證時間戳格式是否為 ISO 8601
        $timestamp = $response['timestamp'];
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $timestamp,
        );

        // 驗證能夠解析為有效的 DateTime
        $dateTime = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339, $timestamp);
        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
    }
}
