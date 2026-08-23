<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\DTOs;

use App\Domains\Post\DTOs\CreateTagDTO;
use Tests\Support\UnitTestCase;

/**
 * CreateTagDTO 測試.
 */
final class CreateTagDTOTest extends UnitTestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $dto = new CreateTagDTO('PHP', 'php', 'PHP 相關公告', '#3498db');

        $this->assertSame('PHP', $dto->name);
        $this->assertSame('php', $dto->slug);
        $this->assertSame('PHP 相關公告', $dto->description);
        $this->assertSame('#3498db', $dto->color);
    }

    public function test_constructor_uses_null_defaults(): void
    {
        $dto = new CreateTagDTO('技術公告');

        $this->assertSame('技術公告', $dto->name);
        $this->assertNull($dto->slug);
        $this->assertNull($dto->description);
        $this->assertNull($dto->color);
    }

    public function test_from_array_with_full_data(): void
    {
        $dto = CreateTagDTO::fromArray([
            'name'        => '系統維護',
            'slug'        => 'maintenance',
            'description' => '系統維護公告',
            'color'       => '#e74c3c',
        ]);

        $this->assertSame('系統維護', $dto->name);
        $this->assertSame('maintenance', $dto->slug);
        $this->assertSame('系統維護公告', $dto->description);
        $this->assertSame('#e74c3c', $dto->color);
    }

    public function test_from_array_with_empty_data_uses_defaults(): void
    {
        $dto = CreateTagDTO::fromArray([]);

        $this->assertSame('', $dto->name);
        $this->assertNull($dto->slug);
        $this->assertNull($dto->description);
        $this->assertNull($dto->color);
    }

    public function test_from_array_ignores_non_string_values(): void
    {
        $dto = CreateTagDTO::fromArray([
            'name'        => 123,
            'slug'        => ['not-a-string'],
            'description' => true,
            'color'       => 3.14,
        ]);

        $this->assertSame('', $dto->name);
        $this->assertNull($dto->slug);
        $this->assertNull($dto->description);
        $this->assertNull($dto->color);
    }

    public function test_to_array_returns_all_fields(): void
    {
        $dto = new CreateTagDTO('活動', 'events', '活動公告', '#2ecc71');

        $this->assertSame([
            'name'        => '活動',
            'slug'        => 'events',
            'description' => '活動公告',
            'color'       => '#2ecc71',
        ], $dto->toArray());
    }
}
