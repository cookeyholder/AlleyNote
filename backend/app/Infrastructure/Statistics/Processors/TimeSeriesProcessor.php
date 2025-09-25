<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Processors;

use App\Domains\Statistics\ValueObjects\ChartData;
use App\Domains\Statistics\ValueObjects\ChartDataset;
use App\Domains\Statistics\ValueObjects\ChartType;
use App\Domains\Statistics\ValueObjects\TimeSeriesDataPoint;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class TimeSeriesProcessor
{
    private const SUPPORTED_GRANULARITIES = [
        'hour' => 'PT1H',
        'day' => 'P1D',
        'week' => 'P1W',
        'month' => 'P1M',
        'year' => 'P1Y',
    ];

    public function processTimeSeriesData(
        array $rawData,
        string $metric,
        string $granularity = 'day',
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): ChartData {
        if ($startDate === null || $endDate === null) {
            [$startDate, $endDate] = $this->inferDateRangeFromData($rawData);
        }

        $this->validateGranularity($granularity);

        $dataPoints = [];
        foreach ($rawData as $item) {
            if (is_array($item)) {
                $timestamp = $item['timestamp'] ?? $item['date'] ?? 'now';
                $value = $item['value'] ?? 0;

                $timestampStr = is_string($timestamp) ? $timestamp : 'now';
                $numericValue = is_numeric($value) ? (float) $value : 0.0;

                $dataPoints[] = TimeSeriesDataPoint::forDate(
                    new DateTimeImmutable($timestampStr),
                    $numericValue,
                );
            }
        }

        return ChartData::forTimeSeries(
            $dataPoints,
            $metric,
            ChartType::Line->getDefaultOptions(),
        );
    }

    public function processMultiSeriesData(
        array $allData,
        string $title,
        string $granularity,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): ChartData {
        if ($startDate === null || $endDate === null) {
            foreach ($allData as $metricData) {
                if (!empty($metricData) && is_array($metricData)) {
                    [$inferredStart, $inferredEnd] = $this->inferDateRangeFromData($metricData);
                    $startDate ??= $inferredStart;
                    $endDate ??= $inferredEnd;
                    break;
                }
            }
        }

        if ($startDate === null || $endDate === null) {
            $now = new DateTimeImmutable();
            $startDate = $now->sub(new DateInterval('P30D'));
            $endDate = $now;
        }

        $datasets = [];
        $colors = $this->getDefaultColors();
        $colorIndex = 0;

        foreach ($allData as $seriesName => $rawData) {
            $data = [];
            if (is_array($rawData)) {
                foreach ($rawData as $item) {
                    if (is_array($item)) {
                        $value = $item['value'] ?? 0;
                        $data[] = is_numeric($value) ? (float) $value : 0.0;
                    }
                }
            }

            $currentColor = $colors[$colorIndex % count($colors)];
            $validColor = is_string($currentColor) ? $currentColor : '#000000';

            $dataset = new ChartDataset(
                $seriesName,
                $data,
                ChartType::Line,
                $validColor,
                [$validColor],
                2,
            );

            $datasets[] = $dataset;
            $colorIndex++;
        }

        return new ChartData(
            labels: array_keys($allData),
            datasets: $datasets,
            options: [],
        );
    }

    public function processEngagementData(
        array $rawData,
        string $granularity = 'day',
    ): ChartData {
        return $this->processTimeSeriesData(
            $rawData,
            'engagement',
            $granularity,
        );
    }

    public function processMultiMetricData(
        array $allData,
        string $title,
        string $granularity,
        array $chartOptions = [],
    ): ChartData {
        return $this->processMultiSeriesData(
            $allData,
            $title,
            $granularity,
        );
    }

    private function inferDateRangeFromData(array $rawData): array
    {
        if (empty($rawData)) {
            $now = new DateTimeImmutable();

            return [$now->sub(new DateInterval('P30D')), $now];
        }

        $timestamps = array_map(function ($item) {
            $timestamp = null;
            if (is_array($item)) {
                $timestamp = $item['timestamp'] ?? $item['date'] ?? null;
            }

            $timestampStr = is_string($timestamp) ? $timestamp : 'now';

            return new DateTimeImmutable($timestampStr);
        }, $rawData);

        return [min($timestamps), max($timestamps)];
    }

    private function validateGranularity(string $granularity): void
    {
        if (!array_key_exists($granularity, self::SUPPORTED_GRANULARITIES)) {
            throw new InvalidArgumentException(
                "不支援的時間粒度: {$granularity}。支援的選項: "
                . implode(', ', array_keys(self::SUPPORTED_GRANULARITIES)),
            );
        }
    }

    public function getDefaultColors(): array
    {
        return [
            '#FF6384', // 紅色
            '#36A2EB', // 藍色
            '#FFCE56', // 黃色
            '#4BC0C0', // 青色
            '#9966FF', // 紫色
            '#FF9F40', // 橙色
        ];
    }
}
