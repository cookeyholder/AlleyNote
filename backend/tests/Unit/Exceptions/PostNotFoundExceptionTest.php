<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Shared\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;

class PostNotFoundExceptionTest extends TestCase
{
    public function testConstructorWithPostId(): void
    {
        $postId = 123;
        $exception = new PostNotFoundException($postId);

        $this->assertEquals("找不到 ID 為 {$postId} 的貼文", $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $postId = 456;
        $customMessage = '自定義錯誤訊息';
        $exception = new PostNotFoundException($postId, $customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testByIdStaticMethod(): void
    {
        $postId = 789;
        $exception = PostNotFoundException::byId($postId);

        $this->assertInstanceOf(PostNotFoundException::class, $exception);
        $this->assertEquals("找不到 ID 為 {$postId} 的貼文", $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testByUuidStaticMethod(): void
    {
        $uuid = 'abc-123-def-456';
        $exception = PostNotFoundException::byUuid($uuid);

        $this->assertInstanceOf(PostNotFoundException::class, $exception);
        $this->assertEquals("找不到 UUID 為 {$uuid} 的貼文", $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testInheritsFromNotFoundException(): void
    {
        $exception = new PostNotFoundException(1);

        $this->assertInstanceOf(NotFoundException::class, $exception);
    }
}
