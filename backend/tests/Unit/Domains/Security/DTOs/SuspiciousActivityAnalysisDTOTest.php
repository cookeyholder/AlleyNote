<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\DTOs;

use App\Domains\Security\DTOs\SuspiciousActivityAnalysisDTO;
use App\Domains\Security\Enums\ActivitySeverity;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * SuspiciousActivityAnalysisDTO 測試.
 */
#[CoversClass(SuspiciousActivityAnalysisDTO::class)]
class SuspiciousActivityAnalysisDTOTest extends UnitTestCase
{
    /**
     * 建立分析結果 DTO 的輔助方法.
     *
     * @param array<string, int> $activityCounts
     * @param array<string, int> $failureCounts
     * @param array<string, float> $anomalyScores
     */
    private function makeDto(
        string $targetType = 'user',
        ?string $targetId = '123',
        bool $isSuspicious = false,
        ActivitySeverity $severity = ActivitySeverity::LOW,
        array $activityCounts = [],
        array $failureCounts = [],
        array $anomalyScores = [],
    ): SuspiciousActivityAnalysisDTO {
        $detectionRules = [
            'failure_rate' => ['type' => 'failure_rate_threshold'],
        ];

        return new SuspiciousActivityAnalysisDTO(
            analysisId: 'analysis_test',
            targetType: $targetType,
            targetId: $targetId,
            analysisTime: new DateTimeImmutable('2026-01-01 08:00:00'),
            timeWindowMinutes: 30,
            isSuspicious: $isSuspicious,
            severityLevel: $severity,
            activityCounts: $activityCounts,
            failureCounts: $failureCounts,
            anomalyScores: $anomalyScores,
            detectionRules: $detectionRules,
            metadata: ['source' => 'test'],
            recommendedAction: null,
            confidenceScore: 0.5,
        );
    }

    #[Test]
    public function factory_methods_set_target_type_correctly(): void
    {
        $forUser = SuspiciousActivityAnalysisDTO::forUser(
            userId: 42,
            timeWindowMinutes: 60,
            isSuspicious: false,
            severityLevel: ActivitySeverity::LOW,
            activityCounts: [],
            failureCounts: [],
            anomalyScores: [],
            detectionRules: [],
        );
        $forIp = SuspiciousActivityAnalysisDTO::forIpAddress(
            ipAddress: '203.0.113.9',
            timeWindowMinutes: 60,
            isSuspicious: true,
            severityLevel: ActivitySeverity::HIGH,
            activityCounts: ['login_failed' => 6],
            failureCounts: ['login_failed' => 6],
            anomalyScores: ['ip_reputation' => 0.95],
            detectionRules: [['type' => 'suspicious_ip_range']],
            metadata: [],
            recommendedAction: 'block_ip_address',
            confidenceScore: 0.9,
        );
        $forGlobal = SuspiciousActivityAnalysisDTO::forGlobalPattern(
            timeWindowMinutes: 15,
            isSuspicious: false,
            severityLevel: ActivitySeverity::MEDIUM,
            activityCounts: [],
            failureCounts: [],
            anomalyScores: [],
            detectionRules: [],
        );

        $this->assertSame('user', $forUser->getTargetType());
        $this->assertSame('42', $forUser->getTargetId());
        $this->assertSame(60, $forUser->getTimeWindowMinutes());
        $this->assertStringStartsWith('analysis_', $forUser->getAnalysisId());

        $this->assertSame('ip', $forIp->getTargetType());
        $this->assertSame('203.0.113.9', $forIp->getTargetId());
        $this->assertTrue($forIp->isSuspicious());
        $this->assertSame(ActivitySeverity::HIGH, $forIp->getSeverityLevel());
        $this->assertSame(['login_failed' => 6], $forIp->getActivityCounts());
        $this->assertSame(['ip_reputation' => 0.95], $forIp->getAnomalyScores());
        $this->assertSame('block_ip_address', $forIp->getRecommendedAction());
        $this->assertSame(0.9, $forIp->getConfidenceScore());
        $this->assertInstanceOf(DateTimeImmutable::class, $forIp->getAnalysisTime());

        $this->assertSame('global', $forGlobal->getTargetType());
        $this->assertNull($forGlobal->getTargetId());
    }

    #[Test]
    public function it_computes_activity_totals_and_failure_rate(): void
    {
        $dto = $this->makeDto(
            activityCounts: ['login_success' => 8, 'post_viewed' => 2],
            failureCounts: ['login_success' => 1, 'post_viewed' => 3],
            anomalyScores: ['frequency' => 0.4, 'pattern' => 0.8],
        );

        $this->assertSame(10, $dto->getTotalActivityCount());
        $this->assertSame(4, $dto->getTotalFailureCount());
        $this->assertSame(0.4, $dto->getFailureRate());
        $this->assertSame(0.8, $dto->getMaxAnomalyScore());
        $this->assertEqualsWithDelta(0.6, $dto->getAverageAnomalyScore(), 0.0001);
        $this->assertSame(['source' => 'test'], $dto->getMetadata());
        $this->assertSame(['failure_rate' => ['type' => 'failure_rate_threshold']], $dto->getDetectionRules());
        $this->assertSame(ActivitySeverity::LOW, $dto->getSeverityLevel());
    }

    #[Test]
    public function it_returns_zero_scores_for_empty_data(): void
    {
        $dto = $this->makeDto();

        $this->assertSame(0.0, $dto->getFailureRate());
        $this->assertSame(0.0, $dto->getMaxAnomalyScore());
        $this->assertSame(0.0, $dto->getAverageAnomalyScore());
    }

    #[Test]
    public function it_requires_immediate_action_for_critical_suspicious_result(): void
    {
        $critical = $this->makeDto(isSuspicious: true, severity: ActivitySeverity::CRITICAL);
        $notSuspiciousCritical = $this->makeDto(isSuspicious: false, severity: ActivitySeverity::CRITICAL);

        $this->assertTrue($critical->requiresImmediateAction());
        $this->assertFalse($notSuspiciousCritical->requiresImmediateAction());
    }

    #[Test]
    public function it_requires_immediate_action_when_max_anomaly_score_exceeds_threshold(): void
    {
        $dto = $this->makeDto(
            isSuspicious: true,
            severity: ActivitySeverity::NORMAL,
            anomalyScores: ['pattern' => 0.95],
        );

        $this->assertTrue($dto->requiresImmediateAction());
    }

    #[Test]
    public function it_requires_immediate_action_when_failure_rate_is_high(): void
    {
        $dto = $this->makeDto(
            isSuspicious: true,
            severity: ActivitySeverity::NORMAL,
            activityCounts: ['login_failed' => 10],
            failureCounts: ['login_failed' => 9],
        );

        $this->assertTrue($dto->requiresImmediateAction());
    }

    #[Test]
    public function it_does_not_require_action_for_benign_results(): void
    {
        $dto = $this->makeDto(
            isSuspicious: true,
            severity: ActivitySeverity::LOW,
            activityCounts: ['post_viewed' => 10],
            failureCounts: ['post_viewed' => 1],
            anomalyScores: ['frequency' => 0.5],
        );

        $this->assertFalse($dto->requiresImmediateAction());
    }

    #[Test]
    public function it_builds_summary_per_target_type(): void
    {
        $userSummary = $this->makeDto()->getSummary();
        $ipSummary = $this->makeDto(targetType: 'ip', targetId: '198.51.100.7')->getSummary();
        $globalSummary = $this->makeDto(targetType: 'global', targetId: null)->getSummary();
        $suspiciousUserSummary = $this->makeDto(
            isSuspicious: true,
            severity: ActivitySeverity::HIGH,
            activityCounts: ['login_failed' => 5],
            failureCounts: ['login_failed' => 5],
        )->getSummary();

        $this->assertStringContainsString('使用者 123', $userSummary);
        $this->assertStringContainsString('正常', $userSummary);
        $this->assertStringContainsString('IP 198.51.100.7', $ipSummary);
        $this->assertStringContainsString('全域模式', $globalSummary);
        $this->assertStringContainsString('可疑', $suspiciousUserSummary);
        $this->assertStringContainsString('活動：5，失敗：5', $suspiciousUserSummary);
        $this->assertStringContainsString(ActivitySeverity::HIGH->getDisplayName(), $suspiciousUserSummary);
    }

    #[Test]
    public function it_converts_to_array_and_json(): void
    {
        $dto = $this->makeDto(activityCounts: ['a' => 2], failureCounts: ['a' => 1]);
        $array = $dto->toArray();

        $this->assertSame('analysis_test', $array['analysis_id']);
        $this->assertSame('2026-01-01 08:00:00', $array['analysis_time']);
        $this->assertSame(30, $array['time_window_minutes']);
        $this->assertFalse($array['is_suspicious']);
        $this->assertSame(2, $array['total_activity_count']);
        $this->assertSame(1, $array['total_failure_count']);
        $this->assertSame(0.5, $array['failure_rate']);
        $this->assertFalse($array['requires_immediate_action']);
        $this->assertArrayHasKey('summary', $array);
        $this->assertSame($array, $dto->jsonSerialize());
    }
}
