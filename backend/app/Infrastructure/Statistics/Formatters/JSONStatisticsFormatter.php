<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Formatters;

use App\Domains\Statistics\Contracts\StatisticsFormatterInterface;
use DateTime;
use RuntimeException;

/**
 * JSON 統計資料格式化器.
 *
 * 將統計資料格式化為 JSON 格式。
 */
final class JSONStatisticsFormatter implements StatisticsFormatterInterface
{
    public function getFormat(): string
    {
        return 'json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }

    public function getMimeType(): string
    {
        return 'application/json';
    }

    public function format(array $data, array $options = []): string
    {
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($options['compact'] ?? false) {
            $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        }

        // 添加格式化元資料
        $formattedData = [
            'metadata' => [
                'format' => 'json',
                'exported_at' => new DateTime()->format('c'),
                'version' => '1.0',
                'options' => $options,
            ],
            'data' => $data,
        ];

        $result = json_encode($formattedData, $jsonOptions);

        if ($result === false) {
            throw new RuntimeException('JSON 編碼失敗: ' . json_last_error_msg());
        }

        return $result;
    }

    public function supportsLargeData(): bool
    {
        return true;
    }

    public function getRecommendedFilename(string $type, array $options = []): string
    {
        $timestamp = new DateTime()->format('Y-m-d_H-i-s');
        $suffix = (string) ($options['filename_suffix'] ?? '');

        return "statistics_{$type}{$suffix}_{$timestamp}.json";
    }
}
