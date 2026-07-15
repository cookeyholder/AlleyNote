<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\SourceDistributionAnalyzer;
use App\Domains\Statistics\DTOs\SourceDistributionDTO;
use Tests\Support\UnitTestCase;

/**
 * SourceDistributionAnalyzer 單元測試.
 */
class SourceDistributionAnalyzerTest extends UnitTestCase
{
    private SourceDistributionAnalyzer $analyzer;

    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->analyzer = new SourceDistributionAnalyzer();
        $this->validData = [
            'top_sources' => [
                ['name' => 'Google Search', 'traffic' => 1000, 'percentage' => 40.0],
                ['name' => 'Direct', 'traffic' => 750, 'percentage' => 30.0],
                ['name' => 'Facebook', 'traffic' => 500, 'percentage' => 20.0],
            ],
            'by_traffic_type' => [
                'organic' => 1200, 'paid' => 300, 'direct' => 750,
                'referral' => 400, 'social' => 350, 'email' => 100,
            ],
            'by_channel' => [
                'search' => 1500, 'social_media' => 600, 'email_marketing' => 200,
                'referral_sites' => 400, 'direct' => 750,
            ],
            'by_device' => ['desktop' => 1800, 'mobile' => 1200, 'tablet' => 450],
            'by_geographic' => ['Taiwan' => 2000, 'Japan' => 800, 'Korea' => 600, 'USA' => 450],
            'search_engines' => [
                'total_traffic' => 1200,
                'engines' => ['Google' => 1000, 'Bing' => 150, 'Yahoo' => 50],
            ],
            'social_media' => [
                'total_traffic' => 600,
                'platforms' => ['Facebook' => 300, 'Instagram' => 200, 'Twitter' => 100],
            ],
            'referral_sites' => [
                'total_traffic' => 400,
                'sites' => ['example.com' => 200, 'partner.com' => 150, 'news.com' => 50],
            ],
            'content_types' => ['article' => 1500, 'video' => 800, 'infographic' => 400, 'podcast' => 200],
            'trends' => [
                'growth_rate' => 12.5, 'direction' => 'growing',
                'key_drivers' => ['SEO improvement', 'Social media campaign'],
                'emerging_sources' => ['TikTok', 'LinkedIn'],
                'declining_sources' => ['Twitter'],
                'seasonal_patterns' => ['Higher traffic in Q4'],
            ],
            'generated_at' => '2024-01-15T10:30:00Z',
        ];
    }

    private function createDTO(): SourceDistributionDTO
    {
        return SourceDistributionDTO::fromArray($this->validData);
    }

    public function testTrafficQualityAnalysis(): void
    {
        $dto = $this->createDTO();
        $analysis = $this->analyzer->getTrafficQualityAnalysis($dto);

        $this->assertArrayHasKey('quality_score', $analysis);
        $this->assertArrayHasKey('organic_percentage', $analysis);
        $this->assertArrayHasKey('direct_percentage', $analysis);
        $this->assertArrayHasKey('social_percentage', $analysis);
        $this->assertArrayHasKey('quality_level', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);

        $this->assertSame(38.71, $analysis['organic_percentage']);
        $this->assertSame(24.19, $analysis['direct_percentage']);
        $this->assertSame(11.29, $analysis['social_percentage']);
        $this->assertSame(28.87, $analysis['quality_score']);
        $this->assertSame('poor', $analysis['quality_level']);
    }

    public function testChannelPerformanceAnalysis(): void
    {
        $dto = $this->createDTO();
        $analysis = $this->analyzer->getChannelPerformanceAnalysis($dto);

        $this->assertArrayHasKey('channels', $analysis);
        $this->assertArrayHasKey('top_performer', $analysis);
        $this->assertArrayHasKey('diversity_score', $analysis);

        $channels = $analysis['channels'];
        $this->assertIsArray($channels);
        $this->assertArrayHasKey('search', $channels);
        $this->assertSame(1500, $channels['search']['traffic']);
        $this->assertSame(1, $channels['search']['rank']);
        $this->assertSame('search', $analysis['top_performer']);
    }

    public function testDeviceUsagePattern(): void
    {
        $dto = $this->createDTO();
        $pattern = $this->analyzer->getDeviceUsagePattern($dto);

        $this->assertArrayHasKey('pattern', $pattern);
        $this->assertArrayHasKey('mobile_percentage', $pattern);
        $this->assertArrayHasKey('desktop_percentage', $pattern);
        $this->assertArrayHasKey('tablet_percentage', $pattern);
        $this->assertArrayHasKey('is_mobile_first', $pattern);

        $this->assertSame('desktop_dominant', $pattern['pattern']);
        $this->assertSame(34.78, $pattern['mobile_percentage']);
        $this->assertSame(52.17, $pattern['desktop_percentage']);
        $this->assertFalse($pattern['is_mobile_first']);
    }

    public function testTrendInsights(): void
    {
        $dto = $this->createDTO();
        $insights = $this->analyzer->getTrendInsights($dto);

        $this->assertArrayHasKey('growth_rate', $insights);
        $this->assertArrayHasKey('trend_direction', $insights);

        $this->assertSame(12.5, $insights['growth_rate']);
        $this->assertSame('growing', $insights['trend_direction']);
        $this->assertSame(['SEO improvement', 'Social media campaign'], $insights['key_drivers']);
        $this->assertSame(['TikTok', 'LinkedIn'], $insights['emerging_sources']);
    }

    public function testMobileFirstPattern(): void
    {
        $this->validData['by_device'] = ['mobile' => 2000, 'desktop' => 500, 'tablet' => 200];
        $dto = $this->createDTO();
        $pattern = $this->analyzer->getDeviceUsagePattern($dto);

        $this->assertSame('mobile_first', $pattern['pattern']);
        $this->assertTrue($pattern['is_mobile_first']);
    }

    public function testBalancedDevicePattern(): void
    {
        $this->validData['by_device'] = ['mobile' => 1000, 'desktop' => 1100, 'tablet' => 300];
        $dto = $this->createDTO();
        $pattern = $this->analyzer->getDeviceUsagePattern($dto);

        $this->assertSame('balanced', $pattern['pattern']);
    }

    public function testQualityRecommendations(): void
    {
        $this->validData['by_traffic_type'] = [
            'organic' => 200, 'direct' => 100, 'social' => 2000, 'paid' => 100,
        ];
        $dto = $this->createDTO();
        $analysis = $this->analyzer->getTrafficQualityAnalysis($dto);

        $recommendations = $analysis['recommendations'];
        $this->assertContains('建議加強 SEO 優化以提升有機流量', $recommendations);
        $this->assertContains('建議提升品牌知名度以增加直接流量', $recommendations);
        $this->assertContains('建議平衡流量來源，減少對社群媒體的過度依賴', $recommendations);
    }

    public function testAnalyzeReturnsResult(): void
    {
        $dto = $this->createDTO();
        $result = $this->analyzer->analyze($dto);

        $this->assertInstanceOf(\App\Domains\Statistics\Analyzers\SourceDistributionResult::class, $result);
        $this->assertArrayHasKey('traffic_quality_analysis', $result->toArray());
        $this->assertArrayHasKey('channel_performance_analysis', $result->toArray());
        $this->assertArrayHasKey('device_usage_pattern', $result->toArray());
        $this->assertArrayHasKey('trend_insights', $result->toArray());
    }
}
