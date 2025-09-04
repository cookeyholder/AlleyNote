<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\ValueObjects;

use App\Shared\Cache\ValueObjects\CacheTag;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * CacheTag 值物件測試（修正版）.
 */
class CacheTagTest extends TestCase
{
    public function testCreateUserTag(): void
    {
        $tag = CacheTag::user(123);

        $this->assertEquals('user_123', $tag->getName());
        $this->assertTrue($tag->isUserTag());
        $this->assertFalse($tag->isModuleTag());
    }

    public function testCreateModuleTag(): void
    {
        $tag = CacheTag::module('posts');

        $this->assertEquals('module_posts', $tag->getName());
        $this->assertTrue($tag->isModuleTag());
        $this->assertFalse($tag->isUserTag());
    }

    public function testCreateTemporalTag(): void
    {
        $tag = CacheTag::temporal('daily');

        $this->assertEquals('time_daily', $tag->getName());
        $this->assertTrue($tag->isTemporalTag());
        $this->assertFalse($tag->isUserTag());
    }

    public function testCreateGroupTag(): void
    {
        $tag = CacheTag::group('user_cache');

        $this->assertEquals('group_user_cache', $tag->getName());
        $this->assertTrue($tag->isGroupTag());
        $this->assertFalse($tag->isModuleTag());
    }

    public function testCreateCustomTag(): void
    {
        $tag = new CacheTag('custom_special_tag');

        $this->assertEquals('custom_special_tag', $tag->getName());
        $this->assertFalse($tag->isUserTag());
        $this->assertFalse($tag->isModuleTag());
        $this->assertFalse($tag->isGroupTag());
    }

    public function testTagNormalization(): void
    {
        $tag = new CacheTag('Test  Tag__With--Special  Characters!!!');

        $this->assertEquals('test_tag_with--special_characters', $tag->getName());
    }

    public function testTagValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CacheTag('');
    }

    public function testTagValidationTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CacheTag(str_repeat('a', 51));
    }

    public function testTagValidationReserved(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CacheTag('system');
    }

    public function testToString(): void
    {
        $tag = new CacheTag('test_tag');
        $this->assertEquals('test_tag', (string) $tag);
    }

    public function testEquals(): void
    {
        $tag1 = new CacheTag('test_tag');
        $tag2 = new CacheTag('test_tag');
        $tag3 = new CacheTag('different_tag');

        $this->assertTrue($tag1->equals($tag2));
        $this->assertFalse($tag1->equals($tag3));
    }

    public function testFromArray(): void
    {
        $names = ['tag1', 'tag2', 'tag3'];
        $tags = CacheTag::fromArray($names);

        $this->assertCount(3, $tags);
        $this->assertContainsOnlyInstancesOf(CacheTag::class, $tags);
        $this->assertEquals('tag1', $tags[0]->getName());
        $this->assertEquals('tag2', $tags[1]->getName());
        $this->assertEquals('tag3', $tags[2]->getName());
    }

    public function testToArrayStatic(): void
    {
        $tags = [
            new CacheTag('tag1'),
            new CacheTag('tag2'),
            new CacheTag('tag3'),
        ];

        $names = CacheTag::toArray($tags);

        $this->assertEquals(['tag1', 'tag2', 'tag3'], $names);
    }

    public function testIsValidName(): void
    {
        $this->assertTrue(CacheTag::isValidName('valid_tag'));
        $this->assertTrue(CacheTag::isValidName('user-123'));
        $this->assertTrue(CacheTag::isValidName('module.posts'));

        $this->assertFalse(CacheTag::isValidName(''));
        $this->assertFalse(CacheTag::isValidName('system'));
        $this->assertFalse(CacheTag::isValidName(str_repeat('a', 51)));
    }

    public function testFactoryMethods(): void
    {
        $userTag = CacheTag::user(456);
        $moduleTag = CacheTag::module('comments');
        $groupTag = CacheTag::group('temp_cache');
        $temporalTag = CacheTag::temporal('weekly');

        $this->assertEquals('user_456', $userTag->getName());
        $this->assertEquals('module_comments', $moduleTag->getName());
        $this->assertEquals('group_temp_cache', $groupTag->getName());
        $this->assertEquals('time_weekly', $temporalTag->getName());
    }

    public function testTagTypeDetection(): void
    {
        $userTag = CacheTag::user(789);
        $moduleTag = CacheTag::module('auth');
        $groupTag = CacheTag::group('session');
        $temporalTag = CacheTag::temporal('monthly');
        $customTag = new CacheTag('random_tag');

        // 用戶標籤
        $this->assertTrue($userTag->isUserTag());
        $this->assertFalse($userTag->isModuleTag());
        $this->assertFalse($userTag->isGroupTag());
        $this->assertFalse($userTag->isTemporalTag());

        // 模組標籤
        $this->assertFalse($moduleTag->isUserTag());
        $this->assertTrue($moduleTag->isModuleTag());
        $this->assertFalse($moduleTag->isGroupTag());
        $this->assertFalse($moduleTag->isTemporalTag());

        // 群組標籤
        $this->assertFalse($groupTag->isUserTag());
        $this->assertFalse($groupTag->isModuleTag());
        $this->assertTrue($groupTag->isGroupTag());
        $this->assertFalse($groupTag->isTemporalTag());

        // 時間標籤
        $this->assertFalse($temporalTag->isUserTag());
        $this->assertFalse($temporalTag->isModuleTag());
        $this->assertFalse($temporalTag->isGroupTag());
        $this->assertTrue($temporalTag->isTemporalTag());

        // 自訂標籤
        $this->assertFalse($customTag->isUserTag());
        $this->assertFalse($customTag->isModuleTag());
        $this->assertFalse($customTag->isGroupTag());
        $this->assertFalse($customTag->isTemporalTag());
    }

    public function testNormalizationEdgeCases(): void
    {
        // 測試各種需要正規化的輸入
        $testCases = [
            'Simple Tag' => 'simple_tag',
            'UPPER_CASE' => 'upper_case',
            'mixed-Case_With.Dots' => 'mixed-case_with.dots',
            '  leading_trailing  ' => 'leading_trailing',
            'multiple___underscores' => 'multiple_underscores',
            'special@chars#here%' => 'special_chars_here',
        ];

        foreach ($testCases as $input => $expected) {
            $tag = new CacheTag($input);
            $this->assertEquals($expected, $tag->getName(), "Input: '$input'");
        }
    }

    public function testValidationErrorMessages(): void
    {
        // 測試不同的驗證錯誤
        try {
            new CacheTag('');
            $this->fail('應該拋出異常');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('不能為空', $e->getMessage());
        }

        try {
            new CacheTag(str_repeat('x', 51));
            $this->fail('應該拋出異常');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('不能超過 50 個字符', $e->getMessage());
        }

        try {
            new CacheTag('admin');
            $this->fail('應該拋出異常');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('系統保留字', $e->getMessage());
        }
    }
}
