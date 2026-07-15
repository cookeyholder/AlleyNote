<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use App\Domains\Statistics\DTOs\SourceDistributionDTO;

/**
 * 來源分佈分析器.
 *
 * 負責分析流量來源、管道效能、裝置使用模式與趨勢洞察
 */
class SourceDistributionAnalyzer
{
    /**
     * 執行完整來源分佈分析.
     */
    public function analyze(SourceDistributionDTO $dto): SourceDistributionResult
    {
        return new SourceDistributionResult(
            trafficQualityAnalysis: $this->getTrafficQualityAnalysis($dto),
            channelPerformanceAnalysis: $this->getChannelPerformanceAnalysis($dto),
            deviceUsagePattern: $this->getDeviceUsagePattern($dto),
            trendInsights: $this->getTrendInsights($dto),
        );
    }

    /**
     * 取得流量品質分析.
     *
     * @return array<string, mixed>
     */
    public function getTrafficQualityAnalysis(SourceDistributionDTO $dto): array
    {
        $organicPercentage = $dto->getOrganicPercentage();
        $directPercentage = $this->getDirectPercentage($dto);
        $socialPercentage = $this->getSocialPercentage($dto);
        $qualityScore = $this->calculateQualityScore($organicPercentage, $directPercentage, $socialPercentage);

        return [
            'quality_score'      => $qualityScore,
            'organic_percentage' => $organicPercentage,
            'direct_percentage'  => $directPercentage,
            'social_percentage'  => $socialPercentage,
            'quality_level'      => $this->getQualityLevel($qualityScore),
            'recommendations'    => $this->getQualityRecommendations($organicPercentage, $directPercentage, $socialPercentage),
        ];
    }

    /**
     * 取得管道效能分析.
     *
     * @return array<string, mixed>
     */
    public function getChannelPerformanceAnalysis(SourceDistributionDTO $dto): array
    {
        $totalTraffic = $dto->getTotalTraffic();
        $channelPerformance = [];
        foreach ($dto->getByChannel() as $channel => $traffic) {
            $percentage = $totalTraffic > 0 ? round(($traffic / $totalTraffic) * 100, 2) : 0.0;
            $channelPerformance[$channel] = [
                'traffic'    => $traffic,
                'percentage' => $percentage,
                'rank'       => 0,
            ];
        }
        uasort($channelPerformance, static fn($a, $b) => $b['traffic'] <=> $a['traffic']);
        $rank = 1;
        foreach ($channelPerformance as &$performance) {
            $performance['rank'] = $rank++;
        }

        return [
            'channels'        => $channelPerformance,
            'top_performer'   => array_key_first($channelPerformance),
            'diversity_score' => $this->calculateChannelDiversity($dto),
        ];
    }

    /**
     * 取得裝置使用模式.
     *
     * @return array<string, mixed>
     */
    public function getDeviceUsagePattern(SourceDistributionDTO $dto): array
    {
        $mobilePercentage = $dto->getMobilePercentage();
        $desktopPercentage = $dto->getDesktopPercentage();
        $tabletPercentage = 100 - $mobilePercentage - $desktopPercentage;
        $pattern = match (true) {
            $mobilePercentage > 70                                            => 'mobile_first',
            $desktopPercentage > 60                                           => 'desktop_dominant',
            $desktopPercentage > 50 && $desktopPercentage > $mobilePercentage => 'desktop_dominant',
            abs($mobilePercentage - $desktopPercentage) < 20                  => 'balanced',
            default                                                           => 'mixed',
        };

        return [
            'pattern'            => $pattern,
            'mobile_percentage'  => $mobilePercentage,
            'desktop_percentage' => $desktopPercentage,
            'tablet_percentage'  => $tabletPercentage,
            'is_mobile_first'    => $mobilePercentage > 50,
        ];
    }

    /**
     * 取得趨勢洞察.
     *
     * @return array<string, mixed>
     */
    public function getTrendInsights(SourceDistributionDTO $dto): array
    {
        $trends = $dto->getTrends();
        $growthRate = $trends['growth_rate'] ?? 0.0;
        $trendDirection = $trends['direction'] ?? 'stable';

        return [
            'growth_rate'       => is_numeric($growthRate) ? (float) $growthRate : 0.0,
            'trend_direction'   => is_string($trendDirection) ? $trendDirection : 'stable',
            'key_drivers'       => $trends['key_drivers'] ?? [],
            'emerging_sources'  => $trends['emerging_sources'] ?? [],
            'declining_sources' => $trends['declining_sources'] ?? [],
            'seasonal_patterns' => $trends['seasonal_patterns'] ?? [],
        ];
    }

    private function getDirectPercentage(SourceDistributionDTO $dto): float
    {
        $total = $dto->getTotalTraffic();

        return $total > 0 ? round(($dto->getDirectTraffic() / $total) * 100, 2) : 0.0;
    }

    private function getSocialPercentage(SourceDistributionDTO $dto): float
    {
        $total = $dto->getTotalTraffic();

        return $total > 0 ? round(($dto->getSocialTraffic() / $total) * 100, 2) : 0.0;
    }

    private function calculateQualityScore(float $organicPercentage, float $directPercentage, float $socialPercentage): float
    {
        $score = ($organicPercentage * 0.5) + ($directPercentage * 0.3) + ($socialPercentage * 0.2);

        return round($score, 2);
    }

    private function getQualityLevel(float $qualityScore): string
    {
        return match (true) {
            $qualityScore >= 70 => 'excellent',
            $qualityScore >= 50 => 'good',
            $qualityScore >= 30 => 'average',
            default             => 'poor',
        };
    }

    /**
     * @return array<string>
     */
    private function getQualityRecommendations(float $organicPercentage, float $directPercentage, float $socialPercentage): array
    {
        $recommendations = [];
        if ($organicPercentage < 30) {
            $recommendations[] = '建議加強 SEO 優化以提升有機流量';
        }
        if ($directPercentage < 20) {
            $recommendations[] = '建議提升品牌知名度以增加直接流量';
        }
        if ($socialPercentage > 50) {
            $recommendations[] = '建議平衡流量來源，減少對社群媒體的過度依賴';
        }

        return $recommendations;
    }

    private function calculateChannelDiversity(SourceDistributionDTO $dto): float
    {
        $totalTraffic = $dto->getTotalTraffic();
        $byChannel = $dto->getByChannel();
        if ($totalTraffic === 0 || empty($byChannel)) {
            return 0.0;
        }
        $entropy = 0.0;
        foreach ($byChannel as $traffic) {
            if ($traffic > 0) {
                $probability = $traffic / $totalTraffic;
                $entropy -= $probability * log($probability, 2);
            }
        }
        $maxEntropy = log(count($byChannel), 2);

        return $maxEntropy > 0 ? round(($entropy / $maxEntropy) * 100, 2) : 0.0;
    }
}
