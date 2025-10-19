<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

use App\Domains\Security\DTOs\SuspiciousActivityAnalysisDTO;
use App\Domains\Security\Enums\ActivityType;

/**
 * 可疑活動檢測服務介面.
 *
 * 提供異常行為檢測、閾值配置和警報觸發功能
 */
interface SuspiciousActivityDetectorInterface
{
    /**
     * 檢測指定使用者的可疑活動.
     *
     * @param int $userId 使用者ID
     * @param int $timeWindowMinutes 檢測時間窗口（分鐘）
     * @return SuspiciousActivityAnalysisDTO 分析結果
     */
    public function detectSuspiciousActivity(int $userId, int $timeWindowMinutes = 60): SuspiciousActivityAnalysisDTO;

    /**
     * 檢測指定IP位址的可疑活動.
     *
     * @param string $ipAddress IP位址
     * @param int $timeWindowMinutes 檢測時間窗口（分鐘）
     * @return SuspiciousActivityAnalysisDTO 分析結果
     */
    public function detectSuspiciousIpActivity(string $ipAddress, int $timeWindowMinutes = 60): SuspiciousActivityAnalysisDTO;

    /**
     * 檢測全域可疑活動模式.
     *
     * @param int $timeWindowMinutes 檢測時間窗口（分鐘）
     * @return array<SuspiciousActivityAnalysisDTO> 分析結果列表
     */
    public function detectGlobalSuspiciousPatterns(int $timeWindowMinutes = 60): array;

    /**
     * 設定特定活動類型的失敗閾值
     *
     * @param ActivityType $activityType 活動類型
     * @param int $threshold 閾值
     * @param int $timeWindowMinutes 時間窗口（分鐘）
     */
    public function setFailureThreshold(ActivityType $activityType, int $threshold, int $timeWindowMinutes = 60): void;

    /**
     * 設定特定活動類型的頻率閾值
     *
     * @param ActivityType $activityType 活動類型
     * @param int $threshold 閾值
     * @param int $timeWindowMinutes 時間窗口（分鐘）
     */
    public function setFrequencyThreshold(ActivityType $activityType, int $threshold, int $timeWindowMinutes = 60): void;

    /**
     * 檢查是否需要觸發警報.
     *
     * @param SuspiciousActivityAnalysisDTO $analysis 分析結果
     * @return bool 是否需要觸發警報
     */
    public function shouldTriggerAlert(SuspiciousActivityAnalysisDTO $analysis): bool;

    /**
     * 觸發安全警報.
     *
     * @param SuspiciousActivityAnalysisDTO $analysis 分析結果
     */
    public function triggerAlert(SuspiciousActivityAnalysisDTO $analysis): void;

    /**
     * 取得所有配置的閾值
     *
     * @return array{failure_thresholds: array<string, array<string, int>>, frequency_thresholds: array<string, array<string, int>>}
     */
    public function getThresholdConfiguration(): array;

    /**
     * 重置所有閾值為預設值
     */
    public function resetThresholdsToDefaults(): void;

    /**
     * 啟用特定檢測類型.
     *
     * @param string $detectionType 檢測類型
     */
    public function enableDetection(string $detectionType): void;

    /**
     * 停用特定檢測類型.
     *
     * @param string $detectionType 檢測類型
     */
    public function disableDetection(string $detectionType): void;

    /**
     * 檢查特定檢測類型是否啟用.
     *
     * @param string $detectionType 檢測類型
     * @return bool 是否啟用
     */
    public function isDetectionEnabled(string $detectionType): bool;
}
