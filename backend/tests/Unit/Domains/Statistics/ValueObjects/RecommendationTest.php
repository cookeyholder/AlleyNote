<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\Recommendation;
use PHPUnit\Framework\TestCase;

/**
 * Recommendation 值物件單元測試.
 */
final class RecommendationTest extends TestCase
{
    public function testConstruction(): void
    {
        $rec = new Recommendation(
            title: '測試建議',
            description: '這是一條測試建議',
            priority: 'high',
            category: 'seo',
        );

        $this->assertSame('測試建議', $rec->getTitle());
        $this->assertSame('這是一條測試建議', $rec->getDescription());
        $this->assertSame('high', $rec->getPriority());
        $this->assertSame('seo', $rec->getCategory());
    }

    public function testDefaultValues(): void
    {
        $rec = new Recommendation(
            title: '預設建議',
            description: '使用預設值',
        );

        $this->assertSame('medium', $rec->getPriority());
        $this->assertSame('general', $rec->getCategory());
    }

    public function testToArray(): void
    {
        $rec = new Recommendation(
            title: '測試建議',
            description: '這是一條測試建議',
            priority: 'low',
            category: 'content',
        );

        $array = $rec->toArray();

        $this->assertSame('測試建議', $array['title']);
        $this->assertSame('這是一條測試建議', $array['description']);
        $this->assertSame('low', $array['priority']);
        $this->assertSame('content', $array['category']);
    }

    public function testJsonSerialize(): void
    {
        $rec = new Recommendation(
            title: 'JSON 測試',
            description: 'JSON 序列化測試',
        );

        $json = json_encode($rec, JSON_THROW_ON_ERROR);
        /** @var array{title: string, description: string, priority: string, category: string} $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('JSON 測試', $decoded['title']);
        $this->assertSame('JSON 序列化測試', $decoded['description']);
        $this->assertSame('medium', $decoded['priority']);
        $this->assertSame('general', $decoded['category']);
    }
}
