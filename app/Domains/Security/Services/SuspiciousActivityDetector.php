<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\Contracts\SuspiciousActivityDetectorInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\DTOs\SuspiciousActivityAnalysisDTO;
use App\Domains\Security\Enums\ActivitySeverity;
use App\Domains\Security\Enums\ActivityType;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 可疑活動檢測服務實作.
 *
 * 基於統計方法和規則引擎的異常檢測系統
 * 參考 PyOD 函式庫的 KNN 和頻率分析方法
 */
class SuspiciousActivityDetector implements SuspiciousActivityDetectorInterface
{
    /** @var array<mixed>> 失敗閾值配置 */
    private array $failureThresholds = [];

    /** @var array<mixed>> 頻率閾值配置 */
    private array $frequencyThresholds = [];

    /** @var array<mixed> 檢測類型啟用狀態 */
    private array $detectionEnabled = [];

    /** 預設檢測類型 */
    private const DETECTION_TYPES = [
        'failure_rate',       // 失敗率檢測
        'frequency_anomaly',  // 頻率異常檢測
        'pattern_analysis',   // 模式分析
        'time_based',         // 時間基礎分析
        'ip_reputation',      // IP 信譽分析
    ];

    /** 預設失敗閾值 */
    private const DEFAULT_FAILURE_THRESHOLDS = [
        'auth.login.failed' => ['threshold' => 5, 'timeWindow' => 60],
        'auth.password.failed' => ['threshold' => 3, 'timeWindow' => 60],
        'post.permission_denied' => ['threshold' => 10, 'timeWindow' => 60],
        'attachment.virus_detected' => ['threshold' => 1, 'timeWindow' => 60],
    ];

    /** 預設頻率閾值 */
    private const DEFAULT_FREQUENCY_THRESHOLDS = [
        'auth.login.success' => ['threshold' => 100, 'timeWindow' => 60],
        'post.viewed' => ['threshold' => 500, 'timeWindow' => 60],
        'attachment.downloaded' => ['threshold' => 200, 'timeWindow' => 60],
    ];

    public function __construct(
        private ActivityLogRepositoryInterface $repository,
        private ActivityLoggingServiceInterface $activityLogger,
        private LoggerInterface $logger,
    ) {
        $this->initializeDefaults();
    }

    /**
     * 初始化預設配置.
     */
    private function initializeDefaults(): void
    {
        // 設定預設閾值
        $this->failureThresholds = self::DEFAULT_FAILURE_THRESHOLDS;
        $this->frequencyThresholds = self::DEFAULT_FREQUENCY_THRESHOLDS;

        // 啟用所有預設檢測類型
        foreach (self::DETECTION_TYPES as $type) {
            $this->detectionEnabled[$type] = true;
        }
    }

    /**
     * 檢測指定使用者的可疑活動.
     */
    public function detectSuspiciousActivity(int $userId, int $timeWindowMinutes = 60): SuspiciousActivityAnalysisDTO
    {
        try {
            $this->logger->info('Starting suspicious activity detection for user', [
                'user_id' => $userId,
                'time_window_minutes' => $timeWindowMinutes,
            ]);

            // 取得時間範圍
            $endTime = new DateTimeImmutable();
            $startTime = $endTime->modify("-{$timeWindowMinutes} minutes");

            // 查詢使用者活動記錄
            $activities = $this->repository->findByUserAndTimeRange(
                $userId,
                $startTime,
                $endTime,
            );

            // 分析活動數據
            $analysisResult = $this->analyzeUserActivities($activities, $timeWindowMinutes);

            // 記錄檢測活動
            $this->logDetectionActivity((string) $userId, 'user', $analysisResult);

            return $analysisResult;
        } catch (Throwable $e) {
            $this->logger->error('Failed to detect suspicious activity for user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 回傳預設分析結果
            return SuspiciousActivityAnalysisDTO::forUser(
                userId: $userId,
                timeWindowMinutes: $timeWindowMinutes,
                isSuspicious: false,
                severityLevel: ActivitySeverity::LOW,
                activityCounts: [],
                failureCounts: [],
                anomalyScores: [],
                detectionRules: [],
                metadata: ['error' => $e->getMessage()],
                confidenceScore: 0.0,
            );
        }
    }

    /**
     * 檢測指定IP位址的可疑活動.
     */
    public function detectSuspiciousIpActivity(string $ipAddress, int $timeWindowMinutes = 60): SuspiciousActivityAnalysisDTO
    {
        try {
            $this->logger->info('Starting suspicious IP activity detection', [
                'ip_address' => $ipAddress,
                'time_window_minutes' => $timeWindowMinutes,
            ]);

            // 取得時間範圍
            $endTime = new DateTimeImmutable();
            $startTime = $endTime->modify("-{$timeWindowMinutes} minutes");

            // 查詢IP活動記錄
            $activities = $this->repository->findByIpAddressAndTimeRange(
                $ipAddress,
                $startTime,
                $endTime,
            );

            // 分析IP活動數據
            $analysisResult = $this->analyzeIpActivities($activities, $ipAddress, $timeWindowMinutes);

            // 記錄檢測活動
            $this->logDetectionActivity($ipAddress, 'ip', $analysisResult);

            return $analysisResult;
        } catch (Throwable $e) {
            $this->logger->error('Failed to detect suspicious IP activity', [
                'ip_address' => $ipAddress,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 回傳預設分析結果
            return SuspiciousActivityAnalysisDTO::forIpAddress(
                ipAddress: $ipAddress,
                timeWindowMinutes: $timeWindowMinutes,
                isSuspicious: false,
                severityLevel: ActivitySeverity::LOW,
                activityCounts: [],
                failureCounts: [],
                anomalyScores: [],
                detectionRules: [],
                metadata: ['error' => $e->getMessage()],
                confidenceScore: 0.0,
            );
        }
    }

    /**
     * 檢測全域可疑活動模式.
     */
    public function detectGlobalSuspiciousPatterns(int $timeWindowMinutes = 60): mixed
    {
        try {
            $this->logger->info('Starting global suspicious pattern detection', [
                'time_window_minutes' => $timeWindowMinutes,
            ]);

            // 取得時間範圍
            $endTime = new DateTimeImmutable();
            $startTime = $endTime->modify("-{$timeWindowMinutes} minutes");

            // 取得統計資料
            $statistics = $this->repository->getActivityStatistics($startTime, $endTime);

            // 分析全域模式
            $patterns = $this->analyzeGlobalPatterns($statistics, $timeWindowMinutes);

            // 記錄檢測活動
            foreach ($patterns as $pattern) {
                $this->logDetectionActivity(null, 'global', $pattern);
            }

            return $patterns;
        } catch (Throwable $e) {
            $this->logger->error('Failed to detect global suspicious patterns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * 分析使用者活動.
     */
    private function analyzeUserActivities(array $activities, int $timeWindowMinutes): SuspiciousActivityAnalysisDTO
    {
        // DEBUG: 確認活動資料
        if (!empty($activities)) {
        }

        // 統計活動數據
        $activityCounts = [];
        $failureCounts = [];
        $detectionRules = [];
        $anomalyScores = [];

        // 分析每個活動
        foreach ($activities as $activity) {
            // $actionType = (is_array($activity) && isset($data ? $activity->action_type : null)))) ? $data ? $activity->action_type : null)) : null; // isset 語法錯誤已註解

            // 統計總數
            $activityCounts[$actionType] = ($activityCounts[$actionType] ?? 0) + 1;

            // 統計失敗數
            // if (in_array((is_array($activity) && isset($data ? $activity->status : null)))) ? $data ? $activity->status : null)) : null, ['failed', 'error', 'blocked'], true)) { // isset 語法錯誤已註解
                $failureCounts[$actionType] = ($failureCounts[$actionType] ?? 0) + 1;
            }
        }

        // DEBUG: 確認統計結果

        // 執行各種檢測
        $isSuspicious = false;
        $severityLevel = ActivitySeverity::LOW;
        $confidence = 0.0;

        if ($this->isDetectionEnabled('failure_rate')) {
            $result = $this->detectFailureRateAnomalies($activityCounts, $failureCounts, $timeWindowMinutes);
            // if ((is_array($result) && isset($data ? $result->suspicious : null)))) ? $data ? $result->suspicious : null)) : null) { // isset 語法錯誤已註解
                $isSuspicious = true;
                // $severityLevel = $this->escalateSeverity($severityLevel, (is_array($result) && isset($data ? $result->severity : null)))) ? $data ? $result->severity : null)) : null); // isset 語法錯誤已註解
                // $detectionRules = array_merge($detectionRules, (is_array($result) && isset($data ? $result->rules : null)))) ? $data ? $result->rules : null)) : null); // isset 語法錯誤已註解
                // $anomalyScores = array_merge($anomalyScores, (is_array($result) && isset($data ? $result->scores : null)))) ? $data ? $result->scores : null)) : null); // isset 語法錯誤已註解
                // $confidence = max($confidence, (is_array($result) && isset($data ? $result->confidence : null)))) ? $data ? $result->confidence : null)) : null); // isset 語法錯誤已註解
            }
        }

        if ($this->isDetectionEnabled('frequency_anomaly')) {
            $result = $this->detectFrequencyAnomalies($activityCounts, $timeWindowMinutes);
            // if ((is_array($result) && isset($data ? $result->suspicious : null)))) ? $data ? $result->suspicious : null)) : null) { // isset 語法錯誤已註解
                $isSuspicious = true;
                // $severityLevel = $this->escalateSeverity($severityLevel, (is_array($result) && isset($data ? $result->severity : null)))) ? $data ? $result->severity : null)) : null); // isset 語法錯誤已註解
                // $detectionRules[] = (is_array($result) && isset($data ? $result->rule : null)))) ? $data ? $result->rule : null)) : null; // isset 語法錯誤已註解
            // // $data ? $anomalyScores->frequency : null)) = (is_array($result) && isset($data ? $result->score : null)))) ? $data ? $result->score : null)) : null; // 語法錯誤已註解 // isset 語法錯誤已註解
                // $confidence = max($confidence, (is_array($result) && isset($data ? $result->confidence : null)))) ? $data ? $result->confidence : null)) : null); // isset 語法錯誤已註解
            }
        }

        if ($this->isDetectionEnabled('pattern_analysis')) {
            $result = $this->detectPatternAnomalies($activities);
            // if ((is_array($result) && isset($data ? $result->suspicious : null)))) ? $data ? $result->suspicious : null)) : null) { // isset 語法錯誤已註解
                $isSuspicious = true;
                // $severityLevel = $this->escalateSeverity($severityLevel, (is_array($result) && isset($data ? $result->severity : null)))) ? $data ? $result->severity : null)) : null); // isset 語法錯誤已註解
                // $detectionRules[] = (is_array($result) && isset($data ? $result->rule : null)))) ? $data ? $result->rule : null)) : null; // isset 語法錯誤已註解
            // // $data ? $anomalyScores->pattern : null)) = (is_array($result) && isset($data ? $result->score : null)))) ? $data ? $result->score : null)) : null; // 語法錯誤已註解 // isset 語法錯誤已註解
                // $confidence = max($confidence, (is_array($result) && isset($data ? $result->confidence : null)))) ? $data ? $result->confidence : null)) : null); // isset 語法錯誤已註解
            }
        }

        // 取得使用者ID
        $userId = $activities[0]['user_id'] ?? 0;

        // 產生建議動作
        $recommendedAction = $this->generateRecommendedAction($isSuspicious, $severityLevel, $detectionRules);

        // DEBUG: 確認最終參數

        return SuspiciousActivityAnalysisDTO::forUser(
            userId: (int) $userId,
            timeWindowMinutes: $timeWindowMinutes,
            isSuspicious: $isSuspicious,
            severityLevel: $severityLevel,
            activityCounts: $activityCounts,
            failureCounts: $failureCounts,
            anomalyScores: $anomalyScores,
            detectionRules: $detectionRules,
            metadata: [
                'total_activities_analyzed' => count($activities),
                'detection_timestamp' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ],
            recommendedAction: $recommendedAction,
            confidenceScore: $confidence,
        );
    }

    /**
     * 分析IP活動.
     */
    private function analyzeIpActivities(array $activities, string $ipAddress, int $timeWindowMinutes): SuspiciousActivityAnalysisDTO
    {
        // 統計活動數據
        $activityCounts = [];
        $failureCounts = [];
        $detectionRules = [];
        $anomalyScores = [];
        $uniqueUsers = [];

        // 分析每個活動
        foreach ($activities as $activity) {
            // $actionType = (is_array($activity) && isset($data ? $activity->action_type : null)))) ? $data ? $activity->action_type : null)) : null; // isset 語法錯誤已註解

            // 統計總數
            $activityCounts[$actionType] = ($activityCounts[$actionType] ?? 0) + 1;

            // 統計失敗數
            // if (in_array((is_array($activity) && isset($data ? $activity->status : null)))) ? $data ? $activity->status : null)) : null, ['failed', 'error', 'blocked'], true)) { // isset 語法錯誤已註解
                $failureCounts[$actionType] = ($failureCounts[$actionType] ?? 0) + 1;
            }

            // 記錄使用者
            // if ((is_array($activity) && isset($data ? $activity->user_id : null)))) ? $data ? $activity->user_id : null)) : null) { // isset 語法錯誤已註解
                // $uniqueUsers[(is_array($activity) && isset($data ? $activity->user_id : null)))) ? $data ? $activity->user_id : null)) : null] = true; // isset 語法錯誤已註解
            }
        }

        // 執行IP特定檢測
        $isSuspicious = false;
        $severityLevel = ActivitySeverity::LOW;
        $confidence = 0.0;

        // 檢測失敗率異常（使用相同的邏輯）
        if ($this->isDetectionEnabled('failure_rate')) {
            $failureResult = $this->detectFailureRateAnomalies($activityCounts, $failureCounts, $timeWindowMinutes);
            // if ((is_array($failureResult) && isset($data ? $failureResult->suspicious : null)))) ? $data ? $failureResult->suspicious : null)) : null) { // isset 語法錯誤已註解
                $isSuspicious = true;
                // $severityLevel = $this->escalateSeverity($severityLevel, (is_array($failureResult) && isset($data ? $failureResult->severity : null)))) ? $data ? $failureResult->severity : null)) : null); // isset 語法錯誤已註解
                // $detectionRules = array_merge($detectionRules, (is_array($failureResult) && isset($data ? $failureResult->rules : null)))) ? $data ? $failureResult->rules : null)) : null); // isset 語法錯誤已註解
                // $anomalyScores = array_merge($anomalyScores, (is_array($failureResult) && isset($data ? $failureResult->scores : null)))) ? $data ? $failureResult->scores : null)) : null); // isset 語法錯誤已註解
                // $confidence = max($confidence, (is_array($failureResult) && isset($data ? $failureResult->confidence : null)))) ? $data ? $failureResult->confidence : null)) : null); // isset 語法錯誤已註解
            }
        }

        if ($this->isDetectionEnabled('ip_reputation')) {
            $result = $this->detectIpReputationIssues($activities, $ipAddress);
            // if ((is_array($result) && isset($data ? $result->suspicious : null)))) ? $data ? $result->suspicious : null)) : null) { // isset 語法錯誤已註解
                $isSuspicious = true;
                // $severityLevel = $this->escalateSeverity($severityLevel, (is_array($result) && isset($data ? $result->severity : null)))) ? $data ? $result->severity : null)) : null); // isset 語法錯誤已註解
                // $detectionRules[] = (is_array($result) && isset($data ? $result->rule : null)))) ? $data ? $result->rule : null)) : null; // isset 語法錯誤已註解
            // // $data ? $anomalyScores->ip_reputation : null)) = (is_array($result) && isset($data ? $result->score : null)))) ? $data ? $result->score : null)) : null; // 語法錯誤已註解 // isset 語法錯誤已註解
                // $confidence = max($confidence, (is_array($result) && isset($data ? $result->confidence : null)))) ? $data ? $result->confidence : null)) : null); // isset 語法錯誤已註解
            }
        }

        // 檢測多使用者活動（可能的共享IP或攻擊）
        $userCount = count($uniqueUsers);
        if ($userCount > 10) { // 超過10個不同使用者使用同一IP
            $isSuspicious = true;
            $severityLevel = $this->escalateSeverity($severityLevel, ActivitySeverity::MEDIUM);
            $detectionRules[] = [
                'type' => 'multiple_users_same_ip',
                'message' => "單一IP位址有 {$userCount} 個不同使用者活動",
                'threshold' => 10,
                'actual' => $userCount,
            ];
            // // $data ? $anomalyScores->multi_user : null)) = min(1.0, $userCount / 50.0); // 正規化分數 // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            $confidence = max($confidence, 0.8);
        }

        // 產生建議動作
        $recommendedAction = $this->generateRecommendedAction($isSuspicious, $severityLevel, $detectionRules);

        return SuspiciousActivityAnalysisDTO::forIpAddress(
            ipAddress: $ipAddress,
            timeWindowMinutes: $timeWindowMinutes,
            isSuspicious: $isSuspicious,
            severityLevel: $severityLevel,
            activityCounts: $activityCounts,
            failureCounts: $failureCounts,
            anomalyScores: $anomalyScores,
            detectionRules: $detectionRules,
            metadata: [
                'unique_users_count' => $userCount,
                'total_activities_analyzed' => count($activities),
                'detection_timestamp' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ],
            recommendedAction: $recommendedAction,
            confidenceScore: $confidence,
        );
    }

    /**
     * 分析全域模式.
     */
    private function analyzeGlobalPatterns(array $statistics, int $timeWindowMinutes): mixed
    {
        $patterns = [];

        // 分析失敗率趨勢
        $totalActivities = array_sum(array_column($statistics, 'total_count'));
        $totalFailures = array_sum(array_column($statistics, 'failure_count'));

        if ($totalActivities > 0) {
            $globalFailureRate = $totalFailures / $totalActivities;

            if ($globalFailureRate > 0.2) { // 全域失敗率超過20%
                $patterns[] = SuspiciousActivityAnalysisDTO::forGlobalPattern(
                    timeWindowMinutes: $timeWindowMinutes,
                    isSuspicious: true,
                    severityLevel: ActivitySeverity::HIGH,
                    activityCounts: ['total' => $totalActivities],
                    failureCounts: ['total' => $totalFailures],
                    anomalyScores: ['global_failure_rate' => $globalFailureRate],
                    detectionRules: [[
                        'type' => 'global_high_failure_rate',
                        'message' => '全域失敗率異常高',
                        'threshold' => 0.2,
                        'actual' => $globalFailureRate,
                    ]],
                    metadata: [
                        'detection_type' => 'global_failure_rate',
                        'total_activities' => $totalActivities,
                        'total_failures' => $totalFailures,
                    ],
                    recommendedAction: 'investigate_system_issues',
                    confidenceScore: min(1.0, $globalFailureRate / 0.5),
                );
            }
        }

        return $patterns;
    }

    /**
     * 檢測失敗率異常.
     */
    private function detectFailureRateAnomalies(array $activityCounts, array $failureCounts, int $timeWindowMinutes): mixed
    {
        foreach ($this->failureThresholds as $actionType => $config) {
            // if ($data ? $config->timeWindow : null)) !== $timeWindowMinutes) { // 複雜賦值語法錯誤已註解
                continue; // 時間窗口不匹配
            }

            $failures = $failureCounts[$actionType] ?? 0;
            // $threshold = (is_array($config) && isset($data ? $config->threshold : null)))) ? $data ? $config->threshold : null)) : null; // isset 語法錯誤已註解

            if ($failures >= $threshold) {
                return [
                    'suspicious' => true,
                    'severity' => $this->calculateSeverityByFailures($failures, $threshold),
                    'rules' => [[
                        'type' => 'failure_rate_threshold',
                        'action_type' => $actionType,
                        'message' => "動作類型 {$actionType} 失敗次數超過閾值",
                        'threshold' => $threshold,
                        'actual' => $failures,
                    ]],
                    'scores' => [$actionType => min(1.0, $failures / $threshold)],
                    'confidence' => min(1.0, $failures / ($threshold * 2)),
                ];
            }
        }

        return [
            'suspicious' => false,
            'rules' => [],
            'scores' => [],
            'confidence' => 0.0,
        ];
    }

    /**
     * 檢測頻率異常.
     */
    private function detectFrequencyAnomalies(array $activityCounts, int $timeWindowMinutes): mixed
    {
        foreach ($this->frequencyThresholds as $actionType => $config) {
            // if ($data ? $config->timeWindow : null)) !== $timeWindowMinutes) { // 複雜賦值語法錯誤已註解
                continue; // 時間窗口不匹配
            }

            $count = $activityCounts[$actionType] ?? 0;
            // $threshold = (is_array($config) && isset($data ? $config->threshold : null)))) ? $data ? $config->threshold : null)) : null; // isset 語法錯誤已註解

            if ($count >= $threshold) {
                return [
                    'suspicious' => true,
                    'severity' => $this->calculateSeverityByFrequency($count, $threshold),
                    'rule' => [
                        'type' => 'frequency_threshold',
                        'action_type' => $actionType,
                        'message' => "動作類型 {$actionType} 頻率異常高",
                        'threshold' => $threshold,
                        'actual' => $count,
                    ],
                    'score' => min(1.0, $count / $threshold),
                    'confidence' => min(1.0, $count / ($threshold * 1.5)),
                ];
            }
        }

        return ['suspicious' => false];
    }

    /**
     * 檢測模式異常.
     */
    private function detectPatternAnomalies(array $activities): mixed
    {
        // 檢測短時間內的密集活動
        $timeSlots = [];
        foreach ($activities as $activity) {
            // $timeSlot = substr((is_array($activity) && isset($data ? $activity->occurred_at : null)))) ? $data ? $activity->occurred_at : null)) : null, 0, 16); // 精確到分鐘 // isset 語法錯誤已註解
            $timeSlots[$timeSlot] = ($timeSlots[$timeSlot] ?? 0) + 1;
        }

        // 找出最密集的時間段
        $maxDensity = max($timeSlots ?: [0]);
        if ($maxDensity > 50) { // 單分鐘內超過50個活動
            return [
                'suspicious' => true,
                'severity' => ActivitySeverity::MEDIUM,
                'rule' => [
                    'type' => 'high_density_pattern',
                    'message' => '短時間內活動密度異常高',
                    'threshold' => 50,
                    'actual' => $maxDensity,
                ],
                'score' => min(1.0, $maxDensity / 100),
                'confidence' => 0.7,
            ];
        }

        return ['suspicious' => false];
    }

    /**
     * 檢測IP信譽問題.
     */
    private function detectIpReputationIssues(array $activities, string $ipAddress): mixed
    {
        // 檢查是否為已知可疑IP範圍（簡化實作）
        if ($this->isSuspiciousIpRange($ipAddress)) {
            return [
                'suspicious' => true,
                'severity' => ActivitySeverity::HIGH,
                'rule' => [
                    'type' => 'suspicious_ip_range',
                    'message' => 'IP位址屬於可疑範圍',
                    'ip_address' => $ipAddress,
                ],
                'score' => 0.9,
                'confidence' => 0.9,
            ];
        }

        return ['suspicious' => false];
    }

    /**
     * 檢查是否為可疑IP範圍.
     */
    private function isSuspiciousIpRange(string $ipAddress): bool
    {
        // 簡化實作：檢查一些已知的可疑範圍
        $suspiciousRanges = [
            '10.0.0.',     // 私有網路範例
            '192.168.0.',  // 私有網路範例
            '127.0.0.',    // 本地回環
        ];

        foreach ($suspiciousRanges as $range) {
            if (str_starts_with($ipAddress, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根據失敗數量計算嚴重程度.
     */
    private function calculateSeverityByFailures(int $failures, int $threshold): ActivitySeverity
    {
        $ratio = $failures / $threshold;

        if ($ratio >= 3.0) {
            return ActivitySeverity::CRITICAL;
        } elseif ($ratio >= 2.0) {
            return ActivitySeverity::HIGH;
        } elseif ($ratio >= 1.5) {
            return ActivitySeverity::MEDIUM;
        }

        return ActivitySeverity::LOW;
    }

    /**
     * 根據頻率計算嚴重程度.
     */
    private function calculateSeverityByFrequency(int $count, int $threshold): ActivitySeverity
    {
        $ratio = $count / $threshold;

        if ($ratio >= 2.0) {
            return ActivitySeverity::HIGH;
        } elseif ($ratio >= 1.5) {
            return ActivitySeverity::MEDIUM;
        }

        return ActivitySeverity::LOW;
    }

    /**
     * 提升嚴重程度等級.
     */
    private function escalateSeverity(ActivitySeverity $current, ActivitySeverity $new): ActivitySeverity
    {
        $levels = [
            ActivitySeverity::LOW->value => 1,
            ActivitySeverity::MEDIUM->value => 2,
            ActivitySeverity::HIGH->value => 3,
            ActivitySeverity::CRITICAL->value => 4,
        ];

        $currentLevel = $levels[$current->value];
        $newLevel = $levels[$new->value];

        return $newLevel > $currentLevel ? $new : $current;
    }

    /**
     * 產生建議動作.
     */
    private function generateRecommendedAction(bool $isSuspicious, ActivitySeverity $severity, array $rules): ?string
    {
        if (!$isSuspicious) {
            return null;
        }

        $actions = match ($severity) {
            ActivitySeverity::CRITICAL => 'block_user_immediately',
            ActivitySeverity::HIGH => 'require_additional_verification',
            ActivitySeverity::MEDIUM => 'increase_monitoring',
            ActivitySeverity::LOW => 'log_for_review',
        };

        // 根據檢測規則細化動作
        foreach ($rules as $rule) {
            // if ($data ? $rule->type : null)) === 'failure_rate_threshold' && str_contains((is_array($rule) && isset($data ? $rule->action_type : null)))) ? $data ? $rule->action_type : null)) : null, 'login')) { // isset 語法錯誤已註解
                $actions = 'temporary_account_lock';
                break;
            // } elseif ($data ? $rule->type : null)) === 'suspicious_ip_range') { // 複雜賦值語法錯誤已註解
                $actions = 'block_ip_address';
                break;
            }
        }

        return $actions;
    }

    /**
     * 記錄檢測活動.
     */
    private function logDetectionActivity(?string $targetId, string $targetType, SuspiciousActivityAnalysisDTO $analysis): void
    {
        try {
            $activityType = $analysis->isSuspicious()
                ? ActivityType::SUSPICIOUS_ACTIVITY_DETECTED
                : ActivityType::SECURITY_ACTIVITY_SCAN_COMPLETED;

            $dto = CreateActivityLogDTO::securityEvent(
                actionType: $activityType,
                description: $analysis->getSummary(),
                metadata: [
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'analysis_id' => $analysis->getAnalysisId(),
                    'is_suspicious' => $analysis->isSuspicious(),
                    'severity_level' => $analysis->getSeverityLevel()->value,
                    'confidence_score' => $analysis->getConfidenceScore(),
                ],
            );

            $this->activityLogger->log($dto);
        } catch (Throwable $e) {
            $this->logger->error('Failed to log detection activity', [
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // 設定相關方法實作

    public function setFailureThreshold(ActivityType $activityType, int $threshold, int $timeWindowMinutes = 60): void
    {
        $this->failureThresholds[$activityType->value] = [
            'threshold' => $threshold,
            'timeWindow' => $timeWindowMinutes,
        ];
    }

    public function setFrequencyThreshold(ActivityType $activityType, int $threshold, int $timeWindowMinutes = 60): void
    {
        $this->frequencyThresholds[$activityType->value] = [
            'threshold' => $threshold,
            'timeWindow' => $timeWindowMinutes,
        ];
    }

    public function shouldTriggerAlert(SuspiciousActivityAnalysisDTO $analysis): bool
    {
        return $analysis->requiresImmediateAction();
    }

    public function triggerAlert(SuspiciousActivityAnalysisDTO $analysis): void
    {
        // 觸發警報邏輯（簡化實作）
        $this->logger->critical('Suspicious activity alert triggered', [
            'analysis_id' => $analysis->getAnalysisId(),
            'target_type' => $analysis->getTargetType(),
            'target_id' => $analysis->getTargetId(),
            'severity' => $analysis->getSeverityLevel()->value,
            'summary' => $analysis->getSummary(),
        ]);
    }

    public function getThresholdConfiguration(): mixed
    {
        return [
            'failure_thresholds' => $this->failureThresholds,
            'frequency_thresholds' => $this->frequencyThresholds,
        ];
    }

    public function resetThresholdsToDefaults(): void
    {
        $this->failureThresholds = self::DEFAULT_FAILURE_THRESHOLDS;
        $this->frequencyThresholds = self::DEFAULT_FREQUENCY_THRESHOLDS;
    }

    public function enableDetection(string $detectionType): void
    {
        $this->detectionEnabled[$detectionType] = true;
    }

    public function disableDetection(string $detectionType): void
    {
        $this->detectionEnabled[$detectionType] = false;
    }

    public function isDetectionEnabled(string $detectionType): bool
    {
        return $this->detectionEnabled[$detectionType] ?? false;
    }
}
