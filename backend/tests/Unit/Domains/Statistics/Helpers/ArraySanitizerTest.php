<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Helpers;

use App\Domains\Statistics\Helpers\ArraySanitizer;
use PHPUnit\Framework\TestCase;

/**
 * ArraySanitizer 單元測試.
 */
final class ArraySanitizerTest extends TestCase
{
    public function testEnsureStringMixedArray(): void
    {
        $this->assertSame([], ArraySanitizer::ensureStringMixedArray(null));
        $this->assertSame([], ArraySanitizer::ensureStringMixedArray('invalid'));
        $this->assertSame(['a' => 1], ArraySanitizer::ensureStringMixedArray(['a' => 1]));
        $this->assertSame(['a' => 1], ArraySanitizer::ensureStringMixedArray(['a' => 1, 0 => 'numeric_key']));
        $this->assertSame([], ArraySanitizer::ensureStringMixedArray([1, 2, 3]));
    }

    public function testEnsureStringIntArray(): void
    {
        $this->assertSame([], ArraySanitizer::ensureStringIntArray(null));
        $this->assertSame([], ArraySanitizer::ensureStringIntArray('invalid'));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringIntArray(['a' => 5]));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringIntArray(['a' => '5']));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringIntArray(['a' => 5, 0 => 10]));
        $this->assertSame([], ArraySanitizer::ensureStringIntArray(['a' => 'not_numeric']));
    }

    public function testEnsureIntArrayStringMixedArray(): void
    {
        $this->assertSame([], ArraySanitizer::ensureIntArrayStringMixedArray(null));
        $this->assertSame([], ArraySanitizer::ensureIntArrayStringMixedArray('invalid'));
        $this->assertSame(
            [['name' => 'test', 'value' => 1]],
            ArraySanitizer::ensureIntArrayStringMixedArray([['name' => 'test', 'value' => 1]]),
        );
        $this->assertSame(
            [['name' => 'test']],
            ArraySanitizer::ensureIntArrayStringMixedArray([['name' => 'test', 0 => 'numeric_key']]),
        );
        $this->assertSame([], ArraySanitizer::ensureIntArrayStringMixedArray(['a' => 1]));
    }

    public function testEnsureStringNumberArray(): void
    {
        $this->assertSame([], ArraySanitizer::ensureStringNumberArray(null));
        $this->assertSame([], ArraySanitizer::ensureStringNumberArray('invalid'));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringNumberArray(['a' => 5]));
        $this->assertSame(['a' => 3.14], ArraySanitizer::ensureStringNumberArray(['a' => 3.14]));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringNumberArray(['a' => 5, 0 => 10]));
        $this->assertSame([], ArraySanitizer::ensureStringNumberArray(['a' => 'string']));
        $this->assertSame(['a' => 1, 'b' => 2.5], ArraySanitizer::ensureStringNumberArray(['a' => 1, 'b' => 2.5]));
    }

    public function testEnsureStringNonNegativeIntArray(): void
    {
        $this->assertSame([], ArraySanitizer::ensureStringNonNegativeIntArray(null));
        $this->assertSame([], ArraySanitizer::ensureStringNonNegativeIntArray('invalid'));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringNonNegativeIntArray(['a' => 5]));
        $this->assertSame(['a' => 0], ArraySanitizer::ensureStringNonNegativeIntArray(['a' => 0]));
        $this->assertSame([], ArraySanitizer::ensureStringNonNegativeIntArray(['a' => -1]));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringNonNegativeIntArray(['a' => 5, 0 => 10]));
        $this->assertSame(['a' => 5], ArraySanitizer::ensureStringNonNegativeIntArray(['a' => '5']));
    }
}
