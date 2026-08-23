<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\DTOs;

use App\Domains\Post\DTOs\UpdateTagDTO;
use stdClass;
use Tests\Support\UnitTestCase;

/**
 * UpdateTagDTO 測試.
 */
final class UpdateTagDTOTest extends UnitTestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $dto = new UpdateTagDTO(5, '新名稱', 'new-slug', '新描述', '#9b59b6');

        $this->assertSame(5, $dto->id);
        $this->assertSame('新名稱', $dto->name);
        $this->assertSame('new-slug', $dto->slug);
        $this->assertSame('新描述', $dto->description);
        $this->assertSame('#9b59b6', $dto->color);
    }

    public function test_constructor_uses_null_defaults(): void
    {
        $dto = new UpdateTagDTO(3);

        $this->assertSame(3, $dto->id);
        $this->assertNull($dto->name);
        $this->assertNull($dto->slug);
        $this->assertNull($dto->description);
        $this->assertNull($dto->color);
    }

    public function test_from_array_with_full_data(): void
    {
        $dto = UpdateTagDTO::fromArray([
            'id'          => '12',
            'name'        => '更新標籤',
            'slug'        => 'updated-tag',
            'description' => '更新後的描述',
            'color'       => '#1abc9c',
        ]);

        $this->assertSame(12, $dto->id);
        $this->assertSame('更新標籤', $dto->name);
        $this->assertSame('updated-tag', $dto->slug);
        $this->assertSame('更新後的描述', $dto->description);
        $this->assertSame('#1abc9c', $dto->color);
    }

    public function test_from_array_with_empty_data_defaults_id_to_zero(): void
    {
        $dto = UpdateTagDTO::fromArray([]);

        $this->assertSame(0, $dto->id);
        $this->assertNull($dto->name);
        $this->assertNull($dto->slug);
        $this->assertNull($dto->description);
        $this->assertNull($dto->color);
    }

    public function test_from_array_ignores_invalid_types(): void
    {
        $dto = UpdateTagDTO::fromArray([
            'id'          => 'abc',
            'name'        => 999,
            'slug'        => [],
            'description' => false,
            'color'       => new stdClass(),
        ]);

        $this->assertSame(0, $dto->id);
        $this->assertNull($dto->name);
        $this->assertNull($dto->slug);
        $this->assertNull($dto->description);
        $this->assertNull($dto->color);
    }

    public function test_to_array_only_includes_non_null_fields(): void
    {
        $dto = new UpdateTagDTO(7, '僅改名');

        $this->assertSame([
            'id'   => 7,
            'name' => '僅改名',
        ], $dto->toArray());
    }

    public function test_to_array_with_all_fields(): void
    {
        $dto = new UpdateTagDTO(8, '完整', 'full', '完整更新', '#f1c40f');

        $this->assertSame([
            'id'          => 8,
            'name'        => '完整',
            'slug'        => 'full',
            'description' => '完整更新',
            'color'       => '#f1c40f',
        ], $dto->toArray());
    }
}
