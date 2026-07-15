<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\SourceDistributionDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

class SourceDistributionDTOTest extends UnitTestCase
{
    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'top_sources' => [
                [
                    'name'       => 'Google Search',
                    'traffic'    => 1000,
                    'percentage' => 40.0,
                ],
                [
                    'name'       => 'Direct',
                    'traffic'    => 750,
                    'percentage' => 30.0,
                ],
                [
                    'name'       => 'Facebook',
                    'traffic'    => 500,
                    'percentage' => 20.0,
                ],
            ],
            'by_traffic_type' => [
                'organic'  => 1200,
                'paid'     => 300,
                'direct'   => 750,
                'referral' => 400,
                'social'   => 350,
                'email'    => 100,
            ],
            'by_channel' => [
                'search'          => 1500,
                'social_media'    => 600,
                'email_marketing' => 200,
                'referral_sites'  => 400,
                'direct'          => 750,
            ],
            'by_device' => [
                'desktop' => 1800,
                'mobile'  => 1200,
                'tablet'  => 450,
            ],
            'by_geographic' => [
                'Taiwan' => 2000,
                'Japan'  => 800,
                'Korea'  => 600,
                'USA'    => 450,
            ],
            'search_engines' => [
                'total_traffic' => 1200,
                'engines'       => [
                    'Google' => 1000,
                    'Bing'   => 150,
                    'Yahoo'  => 50,
                ],
            ],
            'social_media' => [
                'total_traffic' => 600,
                'platforms'     => [
                    'Facebook'  => 300,
                    'Instagram' => 200,
                    'Twitter'   => 100,
                ],
            ],
            'referral_sites' => [
                'total_traffic' => 400,
                'sites'         => [
                    'example.com' => 200,
                    'partner.com' => 150,
                    'news.com'    => 50,
                ],
            ],
            'content_types' => [
                'article'     => 1500,
                'video'       => 800,
                'infographic' => 400,
                'podcast'     => 200,
            ],
            'trends' => [
                'growth_rate'       => 12.5,
                'direction'         => 'growing',
                'key_drivers'       => ['SEO improvement', 'Social media campaign'],
                'emerging_sources'  => ['TikTok', 'LinkedIn'],
                'declining_sources' => ['Twitter'],
                'seasonal_patterns' => ['Higher traffic in Q4'],
            ],
            'generated_at' => '2024-01-15T10:30:00Z',
            'metadata'     => [
                'report_id' => 'source_dist_001',
                'version'   => '1.0',
            ],
        ];
    }

    public function testConstructionWithValidData(): void
    {
        $generatedAt = new DateTimeImmutable('2024-01-15T10:30:00Z');

        $dto = new SourceDistributionDTO(
            topSources: [['name' => 'Google', 'traffic' => 1000]],
            byTrafficType: ['organic' => 1000],
            byChannel: ['search' => 1000],
            byDevice: ['desktop' => 800],
            byGeographic: ['Taiwan' => 1000],
            searchEngines: ['total_traffic' => 1000],
            socialMedia: ['total_traffic' => 500],
            referralSites: ['total_traffic' => 200],
            contentTypes: ['article' => 800],
            trends: ['growth_rate' => 10.0],
            generatedAt: $generatedAt,
            metadata: ['report_id' => 'test'],
        );

        $this->assertSame([['name' => 'Google', 'traffic' => 1000]], $dto->getTopSources());
        $this->assertSame(['organic' => 1000], $dto->getByTrafficType());
        $this->assertSame($generatedAt, $dto->getGeneratedAt());
        $this->assertSame(['report_id' => 'test'], $dto->getMetadata());
    }

    public function testFromArray(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        $this->assertSame($this->validData['top_sources'], $dto->getTopSources());
        $this->assertSame($this->validData['by_traffic_type'], $dto->getByTrafficType());
        $this->assertSame($this->validData['by_channel'], $dto->getByChannel());
        $this->assertSame($this->validData['by_device'], $dto->getByDevice());
        $this->assertSame($this->validData['by_geographic'], $dto->getByGeographic());
        $this->assertSame($this->validData['search_engines'], $dto->getSearchEngines());
        $this->assertSame($this->validData['social_media'], $dto->getSocialMedia());
        $this->assertSame($this->validData['referral_sites'], $dto->getReferralSites());
        $this->assertSame($this->validData['content_types'], $dto->getContentTypes());
        $this->assertSame($this->validData['trends'], $dto->getTrends());
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->getGeneratedAt());
        $this->assertSame($this->validData['metadata'], $dto->getMetadata());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $dto = SourceDistributionDTO::fromArray([]);

        $this->assertSame([], $dto->getTopSources());
        $this->assertSame([], $dto->getByTrafficType());
        $this->assertSame([], $dto->getByChannel());
        $this->assertSame([], $dto->getByDevice());
        $this->assertSame([], $dto->getByGeographic());
        $this->assertNull($dto->getGeneratedAt());
        $this->assertSame([], $dto->getMetadata());
    }

    public function testCalculatedTrafficMetrics(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        // 總流量 = 1200 + 300 + 750 + 400 + 350 + 100 = 3100
        $this->assertSame(3100, $dto->getTotalTraffic());
        $this->assertSame(1200, $dto->getOrganicTraffic());
        $this->assertSame(300, $dto->getPaidTraffic());
        $this->assertSame(750, $dto->getDirectTraffic());
        $this->assertSame(400, $dto->getReferralTraffic());
        $this->assertSame(350, $dto->getSocialTraffic());
        $this->assertSame(100, $dto->getEmailTraffic());

        // 百分比計算
        $this->assertSame(38.71, $dto->getOrganicPercentage()); // 1200 / 3100 * 100
        $this->assertSame(9.68, $dto->getPaidPercentage()); // 300 / 3100 * 100
    }

    public function testCalculatedDeviceMetrics(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        $this->assertSame(1800, $dto->getDesktopTraffic());
        $this->assertSame(1200, $dto->getMobileTraffic());
        $this->assertSame(450, $dto->getTabletTraffic());

        // 裝置總流量 = 1800 + 1200 + 450 = 3450
        $this->assertSame(34.78, $dto->getMobilePercentage()); // 1200 / 3450 * 100
        $this->assertSame(52.17, $dto->getDesktopPercentage()); // 1800 / 3450 * 100
    }

    public function testTopSourceAndPlatforms(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        $topSource = $dto->getTopSource();
        $this->assertNotNull($topSource);
        $this->assertSame('Google Search', $topSource['name']);
        $this->assertSame(1000, $topSource['traffic']);

        $this->assertSame('Google', $dto->getTopSearchEngine());
        $this->assertSame('Facebook', $dto->getTopSocialPlatform());
        $this->assertSame('example.com', $dto->getTopReferralSite());
        $this->assertSame('Taiwan', $dto->getTopGeographicLocation());
    }

    public function testTopSourceWhenEmpty(): void
    {
        $data = $this->validData;
        $data['top_sources'] = [];

        $dto = SourceDistributionDTO::fromArray($data);

        $this->assertNull($dto->getTopSource());
    }

    public function testTopEngineWhenEmpty(): void
    {
        $data = $this->validData;
        $data['search_engines'] = ['engines' => []];

        $dto = SourceDistributionDTO::fromArray($data);

        $this->assertNull($dto->getTopSearchEngine());
    }

    public function testEngineTrafficCounts(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        $this->assertSame(1200, $dto->getSearchEngineTraffic());
        $this->assertSame(600, $dto->getSocialMediaTraffic());
        $this->assertSame(400, $dto->getReferralTrafficCount());
    }

    public function testToArray(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        $array = $dto->toArray();

        $this->assertArrayHasKey('top_sources', $array);
        $this->assertArrayHasKey('by_traffic_type', $array);
        $this->assertArrayHasKey('calculated_metrics', $array);
        $this->assertArrayNotHasKey('traffic_quality_analysis', $array);
        $this->assertArrayNotHasKey('channel_performance_analysis', $array);
        $this->assertArrayNotHasKey('device_usage_pattern', $array);
        $this->assertArrayNotHasKey('trend_insights', $array);
        $this->assertArrayHasKey('generated_at', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame('2024-01-15T10:30:00Z', $array['generated_at']);

        $metrics = $array['calculated_metrics'];
        $this->assertIsArray($metrics);
        $this->assertSame(3100, $metrics['total_traffic']);
        $this->assertSame(38.71, $metrics['organic_percentage']);
    }

    public function testJsonSerialize(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        $json = json_encode($dto, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        $this->assertArrayHasKey('top_sources', $decoded);
        $this->assertArrayHasKey('calculated_metrics', $decoded);
        $calculatedMetrics = $decoded['calculated_metrics'];
        $this->assertIsArray($calculatedMetrics);
        $this->assertSame(3100, $calculatedMetrics['total_traffic']);
    }

    public function testHasData(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);
        $this->assertTrue($dto->hasData());

        $emptyDto = SourceDistributionDTO::fromArray([]);
        $this->assertFalse($emptyDto->hasData());

        $partialDto = SourceDistributionDTO::fromArray(['top_sources' => [['name' => 'test', 'traffic' => 1]]]);
        $this->assertTrue($partialDto->hasData());
    }

    public function testGetSummary(): void
    {
        $dto = SourceDistributionDTO::fromArray($this->validData);

        $summary = $dto->getSummary();

        $this->assertArrayHasKey('total_traffic', $summary);
        $this->assertArrayHasKey('organic_percentage', $summary);
        $this->assertArrayHasKey('mobile_percentage', $summary);
        $this->assertArrayHasKey('top_source', $summary);
        $this->assertArrayHasKey('top_search_engine', $summary);
        $this->assertArrayNotHasKey('device_pattern', $summary);

        $this->assertSame(3100, $summary['total_traffic']);
        $this->assertSame(38.71, $summary['organic_percentage']);
        $this->assertSame(34.78, $summary['mobile_percentage']);
        $this->assertSame('Google Search', $summary['top_source']);
        $this->assertSame('Google', $summary['top_search_engine']);
    }

    public function testValidationFailsWithInvalidTopSources(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('主要來源資料結構不正確');

        new SourceDistributionDTO(
            topSources: [['invalid' => 'structure']],
            byTrafficType: [],
            byChannel: [],
            byDevice: [],
            byGeographic: [],
            searchEngines: [],
            socialMedia: [],
            referralSites: [],
            contentTypes: [],
            trends: [],
        );
    }

    public function testValidationFailsWithInvalidTrafficType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('流量類型統計資料格式不正確');

        new SourceDistributionDTO(
            topSources: [],
            byTrafficType: ['organic' => -1],
            byChannel: [],
            byDevice: [],
            byGeographic: [],
            searchEngines: [],
            socialMedia: [],
            referralSites: [],
            contentTypes: [],
            trends: [],
        );
    }

    public function testValidationFailsWithInvalidChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('管道統計資料格式不正確');

        new SourceDistributionDTO(
            topSources: [],
            byTrafficType: [],
            byChannel: ['search' => -1],
            byDevice: [],
            byGeographic: [],
            searchEngines: [],
            socialMedia: [],
            referralSites: [],
            contentTypes: [],
            trends: [],
        );
    }

    public function testValidationFailsWithInvalidDevice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('裝置統計資料格式不正確');

        new SourceDistributionDTO(
            topSources: [],
            byTrafficType: [],
            byChannel: [],
            byDevice: ['mobile' => -1],
            byGeographic: [],
            searchEngines: [],
            socialMedia: [],
            referralSites: [],
            contentTypes: [],
            trends: [],
        );
    }

    public function testValidationFailsWithInvalidGeographic(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('地理統計資料格式不正確');

        new SourceDistributionDTO(
            topSources: [],
            byTrafficType: [],
            byChannel: [],
            byDevice: [],
            byGeographic: ['Taiwan' => -1],
            searchEngines: [],
            socialMedia: [],
            referralSites: [],
            contentTypes: [],
            trends: [],
        );
    }

    public function testValidationFailsWithInvalidContentTypes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('內容類型統計資料格式不正確');

        new SourceDistributionDTO(
            topSources: [],
            byTrafficType: [],
            byChannel: [],
            byDevice: [],
            byGeographic: [],
            searchEngines: [],
            socialMedia: [],
            referralSites: [],
            contentTypes: ['article' => -1],
            trends: [],
        );
    }
}
