<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\DTOs;

use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\Enums\PostStatus;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\Validator;
use Tests\Support\UnitTestCase;

/**
 * CreatePostDTO 測試（涵蓋建構、驗證規則與輸出轉換）.
 */
final class CreatePostDTOTest extends UnitTestCase
{
    public function test_can_create_with_valid_data(): void
    {
        $dto = new CreatePostDTO(new Validator(), [
            'title'     => '系統升級公告',
            'content'   => '系統將於週末進行升級作業。',
            'user_id'   => 1,
            'user_ip'   => '192.168.1.10',
            'is_pinned' => true,
            'status'    => 'published',
        ]);

        $this->assertSame('系統升級公告', $dto->title);
        $this->assertSame('系統將於週末進行升級作業。', $dto->content);
        $this->assertSame(1, $dto->userId);
        $this->assertSame('192.168.1.10', $dto->userIp);
        $this->assertTrue($dto->isPinned);
        $this->assertSame(PostStatus::PUBLISHED, $dto->status);
        $this->assertNull($dto->publishDate);
    }

    public function test_defaults_apply_when_optional_fields_missing(): void
    {
        $dto = new CreatePostDTO(new Validator(), [
            'title'   => '預設狀態',
            'content' => '未指定狀態時應為草稿。',
            'user_id' => '7',
            'user_ip' => '::1',
        ]);

        $this->assertFalse($dto->isPinned);
        $this->assertSame(PostStatus::DRAFT, $dto->status);
        $this->assertSame(7, $dto->userId);
    }

    public function test_publish_date_is_kept_when_provided(): void
    {
        $dto = new CreatePostDTO(new Validator(), [
            'title'        => '排程發布',
            'content'      => '此公告將排程發布。',
            'user_id'      => 1,
            'user_ip'      => '10.0.0.1',
            'publish_date' => '2026-12-01T09:00:00+00:00',
        ]);

        $this->assertSame('2026-12-01T09:00:00+00:00', $dto->publishDate);
    }

    public function test_empty_publish_date_becomes_null(): void
    {
        $dto = new CreatePostDTO(new Validator(), [
            'title'        => '空發布日',
            'content'      => '空的發布日期應轉為 null。',
            'user_id'      => 1,
            'user_ip'      => '10.0.0.2',
            'publish_date' => '',
        ]);

        $this->assertNull($dto->publishDate);
    }

    public function test_invalid_status_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        new CreatePostDTO(new Validator(), [
            'title'   => '無效狀態',
            'content' => '狀態值不合法時應驗證失敗。',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'status'  => 'invalid-status',
        ]);
    }

    public function test_to_array_returns_all_fields(): void
    {
        $dto = new CreatePostDTO(new Validator(), [
            'title'     => '陣列轉換',
            'content'   => 'toArray 應回傳全部欄位。',
            'user_id'   => 3,
            'user_ip'   => '172.16.0.1',
            'is_pinned' => true,
            'status'    => 'archived',
        ]);

        $this->assertSame([
            'title'        => '陣列轉換',
            'content'      => 'toArray 應回傳全部欄位。',
            'user_id'      => 3,
            'user_ip'      => '172.16.0.1',
            'is_pinned'    => true,
            'status'       => 'archived',
            'publish_date' => null,
        ], $dto->toArray());
    }
}
