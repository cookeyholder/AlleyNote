<?php

declare(strict_types=1);

namespace App\Domains\Security\DTOs;

use App\Domains\Security\Enums\ActivitySeverity;
use DateTimeImmutable;
use JsonSerializable;

/**
 * 可疑活動分析結果 DTO.
 *
 * 封裝異常檢測結果的資料結構
 */
class SuspiciousActivityAnalysisDTO implements JsonSerializable
{
    /**
     * @param string $analysisId 分析識別碼
     * @param string $targetType 目標類型（'user', 'ip', 'global'）
     * @param string|null $targetId 目標識別碼
     * @param DateTimeImmutable $analysisTime 分析時間
     * @param int $timeWindowMinutes 分析時間窗口（分鐘）
     * @param bool $isSuspicious 是否可疑
     * @param ActivitySeverity $severityLevel 嚴重程度等級
     * @param array<string, int> $activityCounts 各活動類型計數
     * @param array<string, int> $failureCounts 各活動類型失敗計數
     * @param array<string, mixed> $detectionRules 觸發的檢測規則
     * @param array<string, mixed> $metadata 額外的元數據
     * @param string|null $recommendedAction 建議的處理動作
     * @param float $confidenceScore 信心分數（0-1）
     */
    public function __construct(
        private readonly string $analysisId,
        private readonly string $targetType,
        private readonly ?string $targetId,
        private readonly DateTimeImmutable $analysisTime,
        private readonly int $timeWindowMinutes,
        private readonly bool $isSuspicious,
        private readonly ActivitySeverity $severityLevel,
        private readonly array $activityCounts,
        private readonly array $failureCounts,
        private readonly array $anomalyScores,
        private readonly array $detectionRules,
        private readonly array $metadata,
        private readonly ?string $recommendedAction,
        private readonly float $confidenceScore,
    ) {}

    /**
     * 工廠方法：建立使用者分析結果.
     */
    public static function forUser(
        int $userId,
        int $timeWindowMinutes,
        bool $isSuspicious,
        ActivitySeverity $severityLevel,
        array $activityCounts,
        array $failureCounts,
        array $anomalyScores,
        array $detectionRules,
        array $metadata = [],
        ?string $recommendedAction = null,
        float $confidenceScore = 0.0,
    ): self {
        // Validate array structures
        $validActivityCounts = self::validateStringIntArray($activityCounts);
        $validFailureCounts = self::validateStringIntArray($failureCounts);
        $validDetectionRules = self::validateStringMixedArray($detectionRules);
        $validMetadata = self::validateStringMixedArray($metadata);

        return new self(
            analysisId: uniqid('analysis_', true),
            targetType: 'user',
            targetId: (string) $userId,
            analysisTime: new DateTimeImmutable(),
            timeWindowMinutes: $timeWindowMinutes,
            isSuspicious: $isSuspicious,
            severityLevel: $severityLevel,
            activityCounts: $validActivityCounts,
            failureCounts: $validFailureCounts,
            anomalyScores: $anomalyScores,
            detectionRules: $validDetectionRules,
            metadata: $validMetadata,
            recommendedAction: $recommendedAction,
            confidenceScore: $confidenceScore,
        );
    }

    /**
     * 工廠方法：建立 IP 分析結果.
     */
    public static function forIpAddress(
        string $ipAddress,
        int $timeWindowMinutes,
        bool $isSuspicious,
        ActivitySeverity $severityLevel,
        array $activityCounts,
        array $failureCounts,
        array $anomalyScores,
        array $detectionRules,
        array $metadata = [],
        ?string $recommendedAction = null,
        float $confidenceScore = 0.0,
    ): self {
        // Validate array structures
        $validActivityCounts = self::validateStringIntArray($activityCounts);
        $validFailureCounts = self::validateStringIntArray($failureCounts);
        $validDetectionRules = self::validateStringMixedArray($detectionRules);
        $validMetadata = self::validateStringMixedArray($metadata);

        return new self(
            analysisId: uniqid('analysis_', true),
            targetType: 'ip',
            targetId: $ipAddress,
            analysisTime: new DateTimeImmutable(),
            timeWindowMinutes: $timeWindowMinutes,
            isSuspicious: $isSuspicious,
            severityLevel: $severityLevel,
            activityCounts: $validActivityCounts,
            failureCounts: $validFailureCounts,
            anomalyScores: $anomalyScores,
            detectionRules: $validDetectionRules,
            metadata: $validMetadata,
            recommendedAction: $recommendedAction,
            confidenceScore: $confidenceScore,
        );
    }

    /**
     * 工廠方法：建立全域分析結果.
     */
    public static function forGlobalPattern(
        int $timeWindowMinutes,
        bool $isSuspicious,
        ActivitySeverity $severityLevel,
        array $activityCounts,
        array $failureCounts,
        array $anomalyScores,
        array $detectionRules,
        array $metadata = [],
        ?string $recommendedAction = null,
        float $confidenceScore = 0.0,
    ): self {
        // Validate array structures
        $validActivityCounts = self::validateStringIntArray($activityCounts);
        $validFailureCounts = self::validateStringIntArray($failureCounts);
        $validDetectionRules = self::validateStringMixedArray($detectionRules);
        $validMetadata = self::validateStringMixedArray($metadata);

        return new self(
            analysisId: uniqid('analysis_', true),
            targetType: 'global',
            targetId: null,
            analysisTime: new DateTimeImmutable(),
            timeWindowMinutes: $timeWindowMinutes,
            isSuspicious: $isSuspicious,
            severityLevel: $severityLevel,
            activityCounts: $validActivityCounts,
            failureCounts: $validFailureCounts,
            anomalyScores: $anomalyScores,
            detectionRules: $validDetectionRules,
            metadata: $validMetadata,
            recommendedAction: $recommendedAction,
            confidenceScore: $confidenceScore,
        );
    }

    // Getters
    public function getAnalysisId(): string
    {
        return $this->analysisId;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function getTargetId(): ?string
    {
        return $this->targetId;
    }

    public function getAnalysisTime(): DateTimeImmutable
    {
        return $this->analysisTime;
    }

    public function getTimeWindowMinutes(): int
    {
        return $this->timeWindowMinutes;
    }

    public function isSuspicious(): bool
    {
        return $this->isSuspicious;
    }

    public function getSeverityLevel(): ActivitySeverity
    {
        return $this->severityLevel;
    }

    public function getActivityCounts(): array
    {
        return $this->activityCounts;
    }

    public function getFailureCounts(): array
    {
        return $this->failureCounts;
    }

    public function getAnomalyScores(): array
    {
        return $this->anomalyScores;
    }

    public function getDetectionRules(): array
    {
        return $this->detectionRules;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getRecommendedAction(): ?string
    {
        return $this->recommendedAction;
    }

    public function getConfidenceScore(): float
    {
        return $this->confidenceScore;
    }

    /**
     * 取得總活動數量.
     */
    public function getTotalActivityCount(): int
    {
        return array_sum($this->activityCounts);
    }

    /**
     * 取得總失敗數量.
     */
    public function getTotalFailureCount(): int
    {
        return array_sum($this->failureCounts);
    }

    /**
     * 取得失敗率.
     */
    public function getFailureRate(): float
    {
        $totalActivities = $this->getTotalActivityCount();
        if ($totalActivities === 0) {
            return 0.0;
        }

        return $this->getTotalFailureCount() / $totalActivities;
    }

    /**
     * 取得最高異常分數.
     */
    public function getMaxAnomalyScore(): float
    {
        if (empty($this->anomalyScores)) {
            return 0.0;
        }

        $maxValue = max($this->anomalyScores);

        return is_numeric($maxValue) ? (float) $maxValue : 0.0;
    }

    /**
     * 取得平均異常分數.
     */
    public function getAverageAnomalyScore(): float
    {
        if (empty($this->anomalyScores)) {
            return 0.0;
        }

        return array_sum($this->anomalyScores) / count($this->anomalyScores);
    }

    /**
     * 檢查是否需要立即處理.
     */
    public function requiresImmediateAction(): bool
    {
        return $this->isSuspicious && (
            $this->severityLevel === ActivitySeverity::CRITICAL
            || $this->getMaxAnomalyScore() > 0.9
            || $this->getFailureRate() > 0.8
        );
    }

    /**
     * 取得格式化的摘要
     */
    public function getSummary(): string
    {
        $target = match ($this->targetType) {
            'user' => "使用者 {$this->targetId}",
            'ip' => "IP {$this->targetId}",
            'global' => '全域模式',
            default => '未知目標',
        };

        $status = $this->isSuspicious ? '可疑' : '正常';
        $totalActivities = $this->getTotalActivityCount();
        $totalFailures = $this->getTotalFailureCount();

        return "{$target} 在過去 {$this->timeWindowMinutes} 分鐘內的活動分析：{$status}（活動：{$totalActivities}，失敗：{$totalFailures}，嚴重程度：{$this->severityLevel->getDisplayName()}）";
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'analysis_id' => $this->analysisId,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'analysis_time' => $this->analysisTime->format('Y-m-d H:i:s'),
            'time_window_minutes' => $this->timeWindowMinutes,
            'is_suspicious' => $this->isSuspicious,
            'severity_level' => $this->severityLevel->value,
            'activity_counts' => $this->activityCounts,
            'failure_counts' => $this->failureCounts,
            'anomaly_scores' => $this->anomalyScores,
            'detection_rules' => $this->detectionRules,
            'metadata' => $this->metadata,
            'recommended_action' => $this->recommendedAction,
            'confidence_score' => $this->confidenceScore,
            'total_activity_count' => $this->getTotalActivityCount(),
            'total_failure_count' => $this->getTotalFailureCount(),
            'failure_rate' => $this->getFailureRate(),
            'max_anomaly_score' => $this->getMaxAnomalyScore(),
            'average_anomaly_score' => $this->getAverageAnomalyScore(),
            'requires_immediate_action' => $this->requiresImmediateAction(),
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * JSON 序列化.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 驗證並確保陣列 key 都是 string，value 是 int.
     *
     * @return array<string, int>
     */
    private static function validateStringIntArray(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if (is_string($key) && is_numeric($value)) {
                $result[$key] = (int) $value;
            }
        }

        return $result;
    }

    /**
     * 驗證並確保陣列 key 都是 string.
     *
     * @return array<string, mixed>
     */
    private static function validateStringMixedArray(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
