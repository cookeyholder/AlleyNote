<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Post;

use App\DTOs\Post\CreatePostDTO;
use App\Services\Enums\PostStatus;
use PHPUnit\Framework\TestCase;

class CreatePostDTOTest extends TestCase
{
    public function testCanCreateDTOWithValidData(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '這是測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => false,
            'status' => 'draft'
        ];

        $dto = new CreatePostDTO($data);

        $this->assertEquals('測試文章', $dto->title);
        $this->assertEquals('這是測試內容', $dto->content);
        $this->assertEquals(1, $dto->userId);
        $this->assertEquals('127.0.0.1', $dto->userIp);
        $this->assertFalse($dto->isPinned);
        $this->assertEquals(PostStatus::DRAFT, $dto->status);
    }

    public function testShouldThrowExceptionForMissingRequiredField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('缺少必填欄位: title');

        new CreatePostDTO([
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1'
        ]);
    }

    public function testShouldThrowExceptionForInvalidIP(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的 IP 位址格式');

        new CreatePostDTO([
            'title' => '標題',
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => 'invalid-ip'
        ]);
    }

    public function testShouldThrowExceptionForTitleTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('標題長度必須在 1-255 字元之間');

        new CreatePostDTO([
            'title' => str_repeat('a', 256),
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1'
        ]);
    }

    public function testToArrayReturnsCorrectFormat(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => true,
            'status' => 'published'
        ];

        $dto = new CreatePostDTO($data);
        $array = $dto->toArray();

        $expected = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => true,
            'status' => 'published',
            'publish_date' => null,
        ];

        $this->assertEquals($expected, $array);
    }
}
