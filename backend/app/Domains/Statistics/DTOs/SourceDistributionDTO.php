<?php

declare(strict_types=1);

namespace App\Domains\Statistics\DTOs;

use App\Domains\Statistics\Helpers\ArraySanitizer;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

class SourceDistributionDTO implements JsonSerializable
{
    /**
     * @param array<int, array<string, mixed>> $topSources 主要來源清單
     * @param array<string, int> $byTrafficType 按流量類型分組的統計
     * @param array<string, int> $byChannel 按管道分組的統計
     * @param array<string, int> $byDevice 按裝置分組的統計
     * @param array<string, int> $byGeographic 按地理位置分組的統計
     * @param array<string, mixed> $searchEngines 搜尋引擎統計
     * @param array<string, mixed> $socialMedia 社群媒體統計
     * @param array<string, mixed> $referralSites 推薦網站統計
     * @param array<string, int> $contentTypes 內容類型統計
     * @param array<string, mixed> $trends 趨勢資料
     * @param DateTimeImmutable|null $generatedAt 生成時間
     * @param array<string, mixed> $metadata 額外元資料
     */
    public function __construct(
        private readonly array $topSources,
        private readonly array $byTrafficType,
        private readonly array $byChannel,
        private readonly array $byDevice,
        private readonly array $byGeographic,
        private readonly array $searchEngines,
        private readonly array $socialMedia,
        private readonly array $referralSites,
        private readonly array $contentTypes,
        private readonly array $trends,
        private readonly ?DateTimeImmutable $generatedAt = null,
        private readonly array $metadata = [],
    ) {
        $this->validateData();
    }

    /**
     * 從陣列建立 DTO.
     *
     * @param array<string, mixed> $data 原始資料陣列
     *
     * @throws InvalidArgumentException 當資料格式不正確時
     */
    public static function fromArray(array $data): self
    {
        $generatedAt = null;
        if (isset($data['generated_at']) && is_string($data['generated_at'])) {
            $generatedAt = new DateTimeImmutable($data['generated_at']);
        }

        return new self(
            topSources: ArraySanitizer::ensureIntArrayStringMixedArray($data['top_sources'] ?? []),
            byTrafficType: ArraySanitizer::ensureStringIntArray($data['by_traffic_type'] ?? []),
            byChannel: ArraySanitizer::ensureStringIntArray($data['by_channel'] ?? []),
            byDevice: ArraySanitizer::ensureStringIntArray($data['by_device'] ?? []),
            byGeographic: ArraySanitizer::ensureStringIntArray($data['by_geographic'] ?? []),
            searchEngines: ArraySanitizer::ensureStringMixedArray($data['search_engines'] ?? []),
            socialMedia: ArraySanitizer::ensureStringMixedArray($data['social_media'] ?? []),
            referralSites: ArraySanitizer::ensureStringMixedArray($data['referral_sites'] ?? []),
            contentTypes: ArraySanitizer::ensureStringIntArray($data['content_types'] ?? []),
            trends: ArraySanitizer::ensureStringMixedArray($data['trends'] ?? []),
            generatedAt: $generatedAt,
            metadata: ArraySanitizer::ensureStringMixedArray($data['metadata'] ?? []),
        );
    }

    // Getters
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSources(): array
    {
        return $this->topSources;
    }

    /**
     * @return array<string, int>
     */
    public function getByTrafficType(): array
    {
        return $this->byTrafficType;
    }

    /**
     * @return array<string, int>
     */
    public function getByChannel(): array
    {
        return $this->byChannel;
    }

    /**
     * @return array<string, int>
     */
    public function getByDevice(): array
    {
        return $this->byDevice;
    }

    /**
     * @return array<string, int>
     */
    public function getByGeographic(): array
    {
        return $this->byGeographic;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSearchEngines(): array
    {
        return $this->searchEngines;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSocialMedia(): array
    {
        return $this->socialMedia;
    }

    /**
     * @return array<string, mixed>
     */
    public function getReferralSites(): array
    {
        return $this->referralSites;
    }

    /**
     * @return array<string, int>
     */
    public function getContentTypes(): array
    {
        return $this->contentTypes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrends(): array
    {
        return $this->trends;
    }

    public function getGeneratedAt(): ?DateTimeImmutable
    {
        return $this->generatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // 計算方法
    public function getTotalTraffic(): int
    {
        return array_sum($this->byTrafficType);
    }

    public function getOrganicTraffic(): int
    {
        return $this->byTrafficType['organic'] ?? 0;
    }

    public function getPaidTraffic(): int
    {
        return $this->byTrafficType['paid'] ?? 0;
    }

    public function getDirectTraffic(): int
    {
        return $this->byTrafficType['direct'] ?? 0;
    }

    public function getReferralTraffic(): int
    {
        return $this->byTrafficType['referral'] ?? 0;
    }

    public function getSocialTraffic(): int
    {
        return $this->byTrafficType['social'] ?? 0;
    }

    public function getEmailTraffic(): int
    {
        return $this->byTrafficType['email'] ?? 0;
    }

    public function getOrganicPercentage(): float
    {
        $total = $this->getTotalTraffic();

        return $total > 0 ? round(($this->getOrganicTraffic() / $total) * 100, 2) : 0.0;
    }

    public function getPaidPercentage(): float
    {
        $total = $this->getTotalTraffic();

        return $total > 0 ? round(($this->getPaidTraffic() / $total) * 100, 2) : 0.0;
    }

    public function getTopSource(): ?array
    {
        return $this->topSources[0] ?? null;
    }

    public function getTopSearchEngine(): ?string
    {
        if (!isset($this->searchEngines['engines']) || !is_array($this->searchEngines['engines'])) {
            return null;
        }
        $engines = $this->searchEngines['engines'];
        if (empty($engines)) {
            return null;
        }
        $maxCount = 0;
        $topEngine = null;
        foreach ($engines as $engine => $count) {
            if (is_string($engine) && is_numeric($count) && $count > $maxCount) {
                $maxCount = (int) $count;
                $topEngine = $engine;
            }
        }

        return $topEngine;
    }

    public function getTopSocialPlatform(): ?string
    {
        if (!isset($this->socialMedia['platforms']) || !is_array($this->socialMedia['platforms'])) {
            return null;
        }
        $platforms = $this->socialMedia['platforms'];
        if (empty($platforms)) {
            return null;
        }
        $maxCount = 0;
        $topPlatform = null;
        foreach ($platforms as $platform => $count) {
            if (is_string($platform) && is_numeric($count) && $count > $maxCount) {
                $maxCount = (int) $count;
                $topPlatform = $platform;
            }
        }

        return $topPlatform;
    }

    public function getTopReferralSite(): ?string
    {
        if (!isset($this->referralSites['sites']) || !is_array($this->referralSites['sites'])) {
            return null;
        }
        $sites = $this->referralSites['sites'];
        if (empty($sites)) {
            return null;
        }
        $maxCount = 0;
        $topSite = null;
        foreach ($sites as $site => $count) {
            if (is_string($site) && is_numeric($count) && $count > $maxCount) {
                $maxCount = (int) $count;
                $topSite = $site;
            }
        }

        return $topSite;
    }

    public function getDesktopTraffic(): int
    {
        return $this->byDevice['desktop'] ?? 0;
    }

    public function getMobileTraffic(): int
    {
        return $this->byDevice['mobile'] ?? 0;
    }

    public function getTabletTraffic(): int
    {
        return $this->byDevice['tablet'] ?? 0;
    }

    public function getMobilePercentage(): float
    {
        $totalDeviceTraffic = array_sum($this->byDevice);

        return $totalDeviceTraffic > 0 ? round(($this->getMobileTraffic() / $totalDeviceTraffic) * 100, 2) : 0.0;
    }

    public function getDesktopPercentage(): float
    {
        $totalDeviceTraffic = array_sum($this->byDevice);

        return $totalDeviceTraffic > 0 ? round(($this->getDesktopTraffic() / $totalDeviceTraffic) * 100, 2) : 0.0;
    }

    public function getTopGeographicLocation(): ?string
    {
        if (empty($this->byGeographic)) {
            return null;
        }
        $maxCount = max($this->byGeographic);
        $topLocations = array_keys($this->byGeographic, $maxCount);

        return $topLocations[0] ?? null;
    }

    public function getSearchEngineTraffic(): int
    {
        $traffic = $this->searchEngines['total_traffic'] ?? 0;

        return is_numeric($traffic) ? (int) $traffic : 0;
    }

    public function getSocialMediaTraffic(): int
    {
        $traffic = $this->socialMedia['total_traffic'] ?? 0;

        return is_numeric($traffic) ? (int) $traffic : 0;
    }

    public function getReferralTrafficCount(): int
    {
        $traffic = $this->referralSites['total_traffic'] ?? 0;

        return is_numeric($traffic) ? (int) $traffic : 0;
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'top_sources'        => $this->topSources,
            'by_traffic_type'    => $this->byTrafficType,
            'by_channel'         => $this->byChannel,
            'by_device'          => $this->byDevice,
            'by_geographic'      => $this->byGeographic,
            'search_engines'     => $this->searchEngines,
            'social_media'       => $this->socialMedia,
            'referral_sites'     => $this->referralSites,
            'content_types'      => $this->contentTypes,
            'trends'             => $this->trends,
            'calculated_metrics' => [
                'total_traffic'           => $this->getTotalTraffic(),
                'organic_percentage'      => $this->getOrganicPercentage(),
                'paid_percentage'         => $this->getPaidPercentage(),
                'mobile_percentage'       => $this->getMobilePercentage(),
                'desktop_percentage'      => $this->getDesktopPercentage(),
                'top_source'              => $this->getTopSource(),
                'top_search_engine'       => $this->getTopSearchEngine(),
                'top_social_platform'     => $this->getTopSocialPlatform(),
                'top_referral_site'       => $this->getTopReferralSite(),
                'top_geographic_location' => $this->getTopGeographicLocation(),
            ],
        ];
        if ($this->generatedAt !== null) {
            $data['generated_at'] = $this->generatedAt->format('Y-m-d\TH:i:s\Z');
        }
        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * JSON 序列化.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查是否有有效資料.
     */
    public function hasData(): bool
    {
        return !empty($this->topSources) || !empty($this->byTrafficType) || !empty($this->byChannel);
    }

    /**
     * 取得摘要資訊.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'total_traffic'      => $this->getTotalTraffic(),
            'organic_percentage' => $this->getOrganicPercentage(),
            'mobile_percentage'  => $this->getMobilePercentage(),
            'top_source'         => $this->getTopSource()['name'] ?? null,
            'top_search_engine'  => $this->getTopSearchEngine(),
        ];
    }

    /**
     * 驗證資料完整性.
     *
     * @throws InvalidArgumentException 當資料無效時
     */
    private function validateData(): void
    {
        // 驗證主要來源
        foreach ($this->topSources as $source) {
            if (!is_array($source) || !isset($source['name'], $source['traffic'])) {
                throw new InvalidArgumentException('主要來源資料結構不正確');
            }
        }
        // 驗證流量類型統計
        foreach ($this->byTrafficType as $type => $count) {
            if (!is_string($type) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('流量類型統計資料格式不正確');
            }
        }
        // 驗證管道統計
        foreach ($this->byChannel as $channel => $count) {
            if (!is_string($channel) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('管道統計資料格式不正確');
            }
        }
        // 驗證裝置統計
        foreach ($this->byDevice as $device => $count) {
            if (!is_string($device) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('裝置統計資料格式不正確');
            }
        }
        // 驗證地理統計
        foreach ($this->byGeographic as $location => $count) {
            if (!is_string($location) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('地理統計資料格式不正確');
            }
        }
        // 驗證內容類型統計
        foreach ($this->contentTypes as $type => $count) {
            if (!is_string($type) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('內容類型統計資料格式不正確');
            }
        }
    }
}
