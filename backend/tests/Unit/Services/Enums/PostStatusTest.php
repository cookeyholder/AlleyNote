<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Enums;

use App\Domains\Post\Enums\PostStatus;
use PHPUnit\Framework\TestCase;

class PostStatusTest extends TestCase
{
    public function testGetLabel(): void
    {
        $this->assertEquals('草稿', PostStatus::DRAFT->getLabel());
        $this->assertEquals('已發布', PostStatus::PUBLISHED->getLabel());
        $this->assertEquals('已封存', PostStatus::ARCHIVED->getLabel());
    }

    public function testCanTransitionTo(): void
    {
        // 草稿可以轉換到任何狀態
        $this->assertTrue(PostStatus::DRAFT->canTransitionTo(PostStatus::PUBLISHED));
        $this->assertTrue(PostStatus::DRAFT->canTransitionTo(PostStatus::ARCHIVED));

        // 已發布只能轉換到封存
        $this->assertFalse(PostStatus::PUBLISHED->canTransitionTo(PostStatus::DRAFT));
        $this->assertTrue(PostStatus::PUBLISHED->canTransitionTo(PostStatus::ARCHIVED));

        // 已封存不能轉換到任何狀態
        $this->assertFalse(PostStatus::ARCHIVED->canTransitionTo(PostStatus::DRAFT));
        $this->assertFalse(PostStatus::ARCHIVED->canTransitionTo(PostStatus::PUBLISHED));
    }

    public function testIsValid(): void
    {
        $this->assertTrue(PostStatus::isValid('draft'));
        $this->assertTrue(PostStatus::isValid('published'));
        $this->assertTrue(PostStatus::isValid('archived'));
        $this->assertFalse(PostStatus::isValid('invalid-status'));
        $this->assertFalse(PostStatus::isValid(''));
    }
}
