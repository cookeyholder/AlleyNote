<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\CategoryDataPoint;
use Tests\Support\UnitTestCase;

/**
 * 分類資料點值物件測試.
 */
final class CategoryDataPointTest extends UnitTestCase
{
    public function testConstructAndSerializationWithoutLabel(): void
    {
        $point = new CategoryDataPoint(
            category: 'Tech',
            value: 45.0,
            color: '#10B981',
        );

        $this->assertSame('Tech', $point->category);
        $this->assertSame(45.0, $point->value);
        $this->assertSame('#10B981', $point->color);
        $this->assertNull($point->label);

        $json = $point->jsonSerialize();
        $this->assertSame([
            'category' => 'Tech',
            'value'    => 45.0,
            'color'    => '#10B981',
        ], $json);
    }

    public function testConstructAndSerializationWithLabel(): void
    {
        $point = new CategoryDataPoint(
            category: 'News',
            value: 80.0,
            color: '#EF4444',
            label: 'News Label',
        );

        $this->assertSame('News Label', $point->label);
        $json = $point->jsonSerialize();
        $this->assertSame([
            'category' => 'News',
            'value'    => 80.0,
            'color'    => '#EF4444',
            'label'    => 'News Label',
        ], $json);
    }

    public function testWithPercentage(): void
    {
        // 正常百分比
        $point = CategoryDataPoint::withPercentage('Mobile', 25.0, 100.0, '#3B82F6');
        $this->assertSame('Mobile', $point->category);
        $this->assertSame(25.0, $point->value);
        $this->assertSame('Mobile (25.0%)', $point->label);

        // 指定自訂 label
        $customPoint = CategoryDataPoint::withPercentage('Mobile', 25.0, 100.0, '#3B82F6', 'Custom Label');
        $this->assertSame('Custom Label', $customPoint->label);

        // total 為 0
        $zeroPoint = CategoryDataPoint::withPercentage('Mobile', 25.0, 0.0);
        $this->assertSame(0.0, $zeroPoint->value);
        $this->assertSame('Mobile (0.0%)', $zeroPoint->label);
    }

    public function testWithCount(): void
    {
        $point = CategoryDataPoint::withCount('Desktop', 150, '#8B5CF6');
        $this->assertSame('Desktop', $point->category);
        $this->assertSame(150.0, $point->value);
        $this->assertSame('#8B5CF6', $point->color);
        $this->assertSame('Desktop (150)', $point->label);

        // 指定自訂 label
        $customPoint = CategoryDataPoint::withCount('Desktop', 150, '#8B5CF6', 'Custom Count Label');
        $this->assertSame('Custom Count Label', $customPoint->label);
    }
}
