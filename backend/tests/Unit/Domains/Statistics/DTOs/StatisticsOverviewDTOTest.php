<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use App\Shared\Contracts\ValidatorInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Tests\Support\UnitTestCase;

/**
 * 統計概覽 DTO 測試.
 */
final class StatisticsOverviewDTOTest extends UnitTestCase
{
    /**
     * 取得有效的統計概覽測試資料.
     *
     * @return array<string, mixed>
     */
    private function getValidData(): array
    {
        return [
            'total_posts'   => 50,
            'active_users'  => 20,
            'new_users'     => 5,
            'post_activity' => [
                'total_posts'     => 50,
                'published_posts' => 45,
                'draft_posts'     => 5,
            ],
            'user_activity' => [
                'total_users'  => 100,
                'active_users' => 20,
                'new_users'    => 5,
            ],
            'engagement_metrics' => [
                'posts_per_active_user' => 2.5,
                'user_growth_rate'      => 25.0,
            ],
            'period_summary' => [
                'type'          => 'monthly',
                'duration_days' => 30,
            ],
            'generated_at' => '2025-01-15T12:00:00Z',
            'metadata'     => ['version' => '1.0'],
        ];
    }

    public function testFromArrayAndGetters(): void
    {
        $raw = $this->getValidData();
        $dto = StatisticsOverviewDTO::fromArray($raw);

        $this->assertSame(50, $dto->getTotalPosts());
        $this->assertSame(20, $dto->getActiveUsers());
        $this->assertSame(5, $dto->getNewUsers());
        $this->assertSame($raw['post_activity'], $dto->getPostActivity());
        $this->assertSame($raw['user_activity'], $dto->getUserActivity());
        $this->assertSame($raw['engagement_metrics'], $dto->getEngagementMetrics());
        $this->assertSame($raw['period_summary'], $dto->getPeriodSummary());
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->getGeneratedAt());
        $this->assertSame($raw['metadata'], $dto->getMetadata());

        // 計算方法
        $this->assertSame(25.0, $dto->getGrowthRate());
        $this->assertSame(2.5, $dto->getPostsPerUser());
        $this->assertTrue($dto->hasData());

        $summary = $dto->getSummary();
        $this->assertSame(50, $summary['total_posts']);
        $this->assertSame(20, $summary['active_users']);
        $this->assertSame(5, $summary['new_users']);
        $this->assertSame(25.0, $summary['growth_rate']);

        $array = $dto->toArray();
        $this->assertArrayHasKey('calculated_metrics', $array);
        $this->assertArrayHasKey('generated_at', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertSame($array, $dto->jsonSerialize());
    }

    public function testZeroActiveUsersCalculations(): void
    {
        $raw = $this->getValidData();
        $raw['total_posts'] = 0;
        $raw['active_users'] = 0;
        $raw['new_users'] = 0;
        $dto = StatisticsOverviewDTO::fromArray($raw);

        $this->assertSame(0.0, $dto->getGrowthRate());
        $this->assertSame(0.0, $dto->getPostsPerUser());
        $this->assertFalse($dto->hasData());

        // new_users > 0 且 active_users = 0
        $raw['new_users'] = 10;
        $dto2 = StatisticsOverviewDTO::fromArray($raw);
        $this->assertSame(100.0, $dto2->getGrowthRate());
        $this->assertTrue($dto2->hasData());
    }

    public function testCreateWithValidation(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $raw = $this->getValidData();

        $validator->shouldReceive('validate')
            ->once()
            ->with($raw, Mockery::type('array'));

        $dto = StatisticsOverviewDTO::createWithValidation($validator, $raw);
        $this->assertSame(50, $dto->getTotalPosts());
    }

    public function testNegativeValuesValidation(): void
    {
        $raw = $this->getValidData();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章總數不能為負數');
        $raw['total_posts'] = -1;
        StatisticsOverviewDTO::fromArray($raw);
    }

    public function testNegativeActiveUsersValidation(): void
    {
        $raw = $this->getValidData();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('活躍使用者數不能為負數');
        $raw['active_users'] = -1;
        StatisticsOverviewDTO::fromArray($raw);
    }

    public function testNegativeNewUsersValidation(): void
    {
        $raw = $this->getValidData();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('新使用者數不能為負數');
        $raw['new_users'] = -1;
        StatisticsOverviewDTO::fromArray($raw);
    }

    public function testMissingArrayStructureKeysValidation(): void
    {
        $raw = $this->getValidData();
        $postActivity = $raw['post_activity'];
        $this->assertIsArray($postActivity);
        unset($postActivity['published_posts']);
        $raw['post_activity'] = $postActivity;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('post_activity 缺少必要的鍵: published_posts');
        StatisticsOverviewDTO::fromArray($raw);
    }
}
