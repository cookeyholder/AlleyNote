<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Enums;

use App\Domains\Security\Enums\ActivityStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Domains\Security\Enums\ActivityStatus
 */
class ActivityStatusTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('success', ActivityStatus::SUCCESS->value);
        $this->assertEquals('failed', ActivityStatus::FAILED->value);
        $this->assertEquals('error', ActivityStatus::ERROR->value);
        $this->assertEquals('blocked', ActivityStatus::BLOCKED->value);
        $this->assertEquals('pending', ActivityStatus::PENDING->value);
    }

    public function testGetDisplayName(): void
    {
        $this->assertEquals('成功', ActivityStatus::SUCCESS->getDisplayName());
        $this->assertEquals('失敗', ActivityStatus::FAILED->getDisplayName());
        $this->assertEquals('錯誤', ActivityStatus::ERROR->getDisplayName());
        $this->assertEquals('被封鎖', ActivityStatus::BLOCKED->getDisplayName());
        $this->assertEquals('待處理', ActivityStatus::PENDING->getDisplayName());
    }

    public function testIsSuccess(): void
    {
        $this->assertTrue(ActivityStatus::SUCCESS->isSuccess());
        $this->assertFalse(ActivityStatus::FAILED->isSuccess());
        $this->assertFalse(ActivityStatus::ERROR->isSuccess());
        $this->assertFalse(ActivityStatus::BLOCKED->isSuccess());
        $this->assertFalse(ActivityStatus::PENDING->isSuccess());
    }

    public function testIsFailure(): void
    {
        $this->assertFalse(ActivityStatus::SUCCESS->isFailure());
        $this->assertTrue(ActivityStatus::FAILED->isFailure());
        $this->assertTrue(ActivityStatus::ERROR->isFailure());
        $this->assertTrue(ActivityStatus::BLOCKED->isFailure());
        $this->assertFalse(ActivityStatus::PENDING->isFailure());
    }

    public function testIsPending(): void
    {
        $this->assertFalse(ActivityStatus::SUCCESS->isPending());
        $this->assertFalse(ActivityStatus::FAILED->isPending());
        $this->assertFalse(ActivityStatus::ERROR->isPending());
        $this->assertFalse(ActivityStatus::BLOCKED->isPending());
        $this->assertTrue(ActivityStatus::PENDING->isPending());
    }

    public function testIsFinal(): void
    {
        $this->assertTrue(ActivityStatus::SUCCESS->isFinal());
        $this->assertTrue(ActivityStatus::FAILED->isFinal());
        $this->assertTrue(ActivityStatus::ERROR->isFinal());
        $this->assertTrue(ActivityStatus::BLOCKED->isFinal());
        $this->assertFalse(ActivityStatus::PENDING->isFinal());
    }

    public function testGetAvailableStatuses(): void
    {
        $statuses = ActivityStatus::getAvailableStatuses();
        
        $this->assertIsArray($statuses);
        $this->assertCount(5, $statuses);
        $this->assertContains('success', $statuses);
        $this->assertContains('failed', $statuses);
        $this->assertContains('error', $statuses);
        $this->assertContains('blocked', $statuses);
        $this->assertContains('pending', $statuses);
    }
}