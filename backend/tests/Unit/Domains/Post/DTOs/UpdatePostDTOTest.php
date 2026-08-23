<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\DTOs;

use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Enums\PostStatus;
use App\Shared\Validation\Validator;
use Tests\Support\UnitTestCase;

/**
 * UpdatePostDTO 測試（涵蓋部分更新與查詢輔助方法）.
 */
final class UpdatePostDTOTest extends UnitTestCase
{
    public function test_can_create_with_full_update(): void
    {
        $dto = new UpdatePostDTO(new Validator(), [
            'title'        => '更新後標題',
            'content'      => '更新後的內容。',
            'is_pinned'    => true,
            'status'       => 'published',
            'publish_date' => '2026-06-15T08:30:00Z',
        ]);

        $this->assertSame('更新後標題', $dto->title);
        $this->assertSame('更新後的內容。', $dto->content);
        $this->assertTrue($dto->isPinned);
        $this->assertSame(PostStatus::PUBLISHED, $dto->status);
        $this->assertSame('2026-06-15T08:30:00Z', $dto->publishDate);
    }

    public function test_partial_update_only_sets_given_fields(): void
    {
        $dto = new UpdatePostDTO(new Validator(), ['title' => '僅更新標題']);

        $this->assertSame('僅更新標題', $dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
    }

    public function test_empty_data_creates_dto_without_changes(): void
    {
        $dto = new UpdatePostDTO(new Validator(), []);

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertFalse($dto->hasChanges());
    }

    public function test_null_and_empty_string_values_are_filtered_out(): void
    {
        $dto = new UpdatePostDTO(new Validator(), [
            'title'   => null,
            'content' => '',
        ]);

        $this->assertFalse($dto->hasChanges());
        $this->assertSame([], $dto->getUpdatedFields());
    }

    public function test_false_and_zero_are_preserved_for_is_pinned(): void
    {
        $dto = new UpdatePostDTO(new Validator(), ['is_pinned' => false]);

        $this->assertFalse($dto->isPinned);
        $this->assertTrue($dto->hasUpdatedField('is_pinned'));
    }

    public function test_string_zero_is_converted_to_false(): void
    {
        $dto = new UpdatePostDTO(new Validator(), ['is_pinned' => '0']);

        $this->assertFalse($dto->isPinned);
    }

    public function test_has_changes_and_updated_fields_helpers(): void
    {
        $dto = new UpdatePostDTO(new Validator(), [
            'title'  => '新標題',
            'status' => 'draft',
        ]);

        $this->assertTrue($dto->hasChanges());
        $this->assertSame(['title', 'status'], $dto->getUpdatedFields());
        $this->assertTrue($dto->hasUpdatedField('title'));
        $this->assertFalse($dto->hasUpdatedField('content'));
    }

    public function test_unknown_fields_are_ignored_by_validation(): void
    {
        $dto = new UpdatePostDTO(new Validator(), [
            'unknown_field' => 'value',
        ]);

        // 僅未知欄位時，過濾後仍視為有資料但不影響任何屬性
        $this->assertFalse($dto->hasChanges());
    }

    public function test_to_array_only_contains_non_null_fields(): void
    {
        $dto = new UpdatePostDTO(new Validator(), [
            'content' => '只更新內容。',
            'status'  => 'archived',
        ]);

        $this->assertSame([
            'content' => '只更新內容。',
            'status'  => 'archived',
        ], $dto->toArray());
    }

    public function test_to_array_returns_empty_for_no_changes(): void
    {
        $dto = new UpdatePostDTO(new Validator(), []);

        $this->assertSame([], $dto->toArray());
    }
}
