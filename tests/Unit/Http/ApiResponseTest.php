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

        $this->assertTrue((is_array($response) && isset((is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)))) ? (is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)) : null);
        $this->assertEquals($message, (is_array($response) && isset((is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)))) ? (is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)) : null);
        $this->assertEquals($data, (is_array($response) && isset((is_array($response) ? $response['data'] : (is_object($response) ? $response->data : null)))) ? (is_array($response) ? $response['data'] : (is_object($response) ? $response->data : null)) : null);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertIsString((is_array($response) && isset((is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)))) ? (is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)) : null);
    }

    public function testSuccessResponseWithDefaults(): void
    {
        $response = ApiResponse::success();

        $this->assertTrue((is_array($response) && isset((is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)))) ? (is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)) : null);
        $this->assertEquals('Success', (is_array($response) && isset((is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)))) ? (is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)) : null);
        $this->assertNull((is_array($response) && isset((is_array($response) ? $response['data'] : (is_object($response) ? $response->data : null)))) ? (is_array($response) ? $response['data'] : (is_object($response) ? $response->data : null)) : null);
        $this->assertArrayHasKey('timestamp', $response);
    }

    public function testErrorResponse(): void
    {
        $message = 'Something went wrong';
        $code = 400;
        $errors = ['field' => 'error message'];

        $response = ApiResponse::error($message, $code, $errors);

        $this->assertFalse((is_array($response) && isset((is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)))) ? (is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)) : null);
        $this->assertEquals($message, (is_array($response) && isset((is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)))) ? (is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)) : null);
        $this->assertEquals($code, (is_array($response) && isset((is_array($response) ? $response['error_code'] : (is_object($response) ? $response->error_code : null)))) ? (is_array($response) ? $response['error_code'] : (is_object($response) ? $response->error_code : null)) : null);
        $this->assertEquals($errors, (is_array($response) && isset((is_array($response) ? $response['errors'] : (is_object($response) ? $response->errors : null)))) ? (is_array($response) ? $response['errors'] : (is_object($response) ? $response->errors : null)) : null);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertIsString((is_array($response) && isset((is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)))) ? (is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)) : null);
    }

    public function testErrorResponseWithDefaults(): void
    {
        $message = 'Error occurred';

        $response = ApiResponse::error($message);

        $this->assertFalse((is_array($response) && isset((is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)))) ? (is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)) : null);
        $this->assertEquals($message, (is_array($response) && isset((is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)))) ? (is_array($response) ? $response['message'] : (is_object($response) ? $response->message : null)) : null);
        $this->assertEquals(400, (is_array($response) && isset((is_array($response) ? $response['error_code'] : (is_object($response) ? $response->error_code : null)))) ? (is_array($response) ? $response['error_code'] : (is_object($response) ? $response->error_code : null)) : null);
        $this->assertNull((is_array($response) && isset((is_array($response) ? $response['errors'] : (is_object($response) ? $response->errors : null)))) ? (is_array($response) ? $response['errors'] : (is_object($response) ? $response->errors : null)) : null);
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

        $this->assertTrue((is_array($response) && isset((is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)))) ? (is_array($response) ? $response['success'] : (is_object($response) ? $response->success : null)) : null);
        $this->assertEquals($data, (is_array($response) && isset((is_array($response) ? $response['data'] : (is_object($response) ? $response->data : null)))) ? (is_array($response) ? $response['data'] : (is_object($response) ? $response->data : null)) : null);
        $this->assertArrayHasKey('pagination', $response);

        $pagination = (is_array($response) && isset((is_array($response) ? $response['pagination'] : (is_object($response) ? $response->pagination : null)))) ? (is_array($response) ? $response['pagination'] : (is_object($response) ? $response->pagination : null)) : null;
        $this->assertEquals($total, (is_array($pagination) && isset((is_array($pagination) ? $pagination['total'] : (is_object($pagination) ? $pagination->total : null)))) ? (is_array($pagination) ? $pagination['total'] : (is_object($pagination) ? $pagination->total : null)) : null);
        $this->assertEquals($page, (is_array($pagination) && isset((is_array($pagination) ? $pagination['page'] : (is_object($pagination) ? $pagination->page : null)))) ? (is_array($pagination) ? $pagination['page'] : (is_object($pagination) ? $pagination->page : null)) : null);
        $this->assertEquals($perPage, (is_array($pagination) && isset((is_array($pagination) ? $pagination['per_page'] : (is_object($pagination) ? $pagination->per_page : null)))) ? (is_array($pagination) ? $pagination['per_page'] : (is_object($pagination) ? $pagination->per_page : null)) : null);
        $this->assertEquals(ceil($total / $perPage), (is_array($pagination) && isset((is_array($pagination) ? $pagination['total_pages'] : (is_object($pagination) ? $pagination->total_pages : null)))) ? (is_array($pagination) ? $pagination['total_pages'] : (is_object($pagination) ? $pagination->total_pages : null)) : null);
        $this->assertEquals(10, (is_array($pagination) && isset((is_array($pagination) ? $pagination['total_pages'] : (is_object($pagination) ? $pagination->total_pages : null)))) ? (is_array($pagination) ? $pagination['total_pages'] : (is_object($pagination) ? $pagination->total_pages : null)) : null); // 100 / 10 = 10

        $this->assertArrayHasKey('timestamp', $response);
        $this->assertIsString((is_array($response) && isset((is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)))) ? (is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)) : null);
    }

    public function testTimestampFormat(): void
    {
        $response = ApiResponse::success();

        // 驗證時間戳格式是否為 ISO 8601
        $timestamp = (is_array($response) && isset((is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)))) ? (is_array($response) ? $response['timestamp'] : (is_object($response) ? $response->timestamp : null)) : null;
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $timestamp,
        );

        // 驗證能夠解析為有效的 DateTime
        $dateTime = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339, $timestamp);
        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
    }
}
