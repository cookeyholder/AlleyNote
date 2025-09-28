<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Formatters;

use App\Domains\Statistics\Contracts\StatisticsFormatterInterface;
use DateTime;
use RuntimeException;

/**
 * CSV 統計資料格式化器.
 *
 * 將統計資料格式化為 CSV 格式。
 */
final class CSVStatisticsFormatter implements StatisticsFormatterInterface
{
    /** CSV 分隔符號 */
    private const DELIMITER = ',';

    /** CSV 引號字元 */
    private const ENCLOSURE = '"';

    /** CSV 跳脫字元 */
    private const ESCAPE_CHAR = '\\';

    public function getFormat(): string
    {
        return 'csv';
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }

    public function getMimeType(): string
    {
        return 'text/csv';
    }

    public function format(array $data, array $options = []): string
    {
        if (empty($data)) {
            return '';
        }

        $output = '';
        $delimiter = $options['delimiter'] ?? self::DELIMITER;
        $includeHeaders = $options['include_headers'] ?? true;
        $encoding = $options['encoding'] ?? 'UTF-8';

        // 處理巢狀資料結構
        $flattenedData = $this->flattenData($data);

        if (empty($flattenedData)) {
            return '';
        }

        // 建立暫存檔案處理 CSV
        $tempFile = tmpfile();
        if ($tempFile === false) {
            throw new RuntimeException('無法建立暫存檔案');
        }

        try {
            // 寫入標題行
            if ($includeHeaders) {
                $headers = array_keys($flattenedData[0]);
                fputcsv($tempFile, $headers, $delimiter, self::ENCLOSURE, self::ESCAPE_CHAR);
            }

            // 寫入資料行
            foreach ($flattenedData as $row) {
                fputcsv($tempFile, array_values($row), $delimiter, self::ENCLOSURE, self::ESCAPE_CHAR);
            }

            // 讀取檔案內容
            rewind($tempFile);
            $output = stream_get_contents($tempFile);

            if ($output === false) {
                throw new RuntimeException('無法讀取 CSV 資料');
            }

            // 處理編碼轉換
            if ($encoding !== 'UTF-8' && function_exists('mb_convert_encoding')) {
                $output = mb_convert_encoding($output, $encoding, 'UTF-8');
            }

            return $output;
        } finally {
            fclose($tempFile);
        }
    }

    public function supportsLargeData(): bool
    {
        return true;
    }

    public function getRecommendedFilename(string $type, array $options = []): string
    {
        $timestamp = new DateTime()->format('Y-m-d_H-i-s');
        $suffix = (string) ($options['filename_suffix'] ?? '');

        return "statistics_{$type}{$suffix}_{$timestamp}.csv";
    }

    /**
     * 扁平化巢狀資料結構.
     *
     * @return array<array<string, mixed>>
     */
    private function flattenData(array $data): array
    {
        $flattened = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssociativeArray($value)) {
                    // 如果是關聯陣列，展開為多個欄位
                    $flattened = array_merge($flattened, $this->expandAssociativeArray($key, $value));
                } elseif ($this->isSequentialArrayOfAssociativeArrays($value)) {
                    // 如果是關聯陣列的順序陣列，直接使用
                    $flattened = array_merge($flattened, $value);
                } else {
                    // 其他情況，轉換為字串
                    $flattened[] = [$key => $this->arrayToString($value)];
                }
            } else {
                // 純量值
                $flattened[] = [$key => $value];
            }
        }

        // 確保所有行有相同的欄位
        return $this->normalizeRows($flattened);
    }

    /**
     * 檢查是否為關聯陣列.
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * 檢查是否為關聯陣列的順序陣列.
     */
    private function isSequentialArrayOfAssociativeArrays(array $array): bool
    {
        if (empty($array) || !is_array($array[0])) {
            return false;
        }

        return $this->isAssociativeArray($array[0]);
    }

    /**
     * 展開關聯陣列.
     *
     * @return array<array<string, mixed>>
     */
    private function expandAssociativeArray(string $prefix, array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix . '_' . $key;

            if (is_array($value)) {
                $result[$fullKey] = $this->arrayToString($value);
            } else {
                $result[$fullKey] = $value;
            }
        }

        return [$result];
    }

    /**
     * 將陣列轉換為字串.
     */
    private function arrayToString(array $array): string
    {
        $result = json_encode($array, JSON_UNESCAPED_UNICODE);

        return $result !== false ? $result : '[]';
    }

    /**
     * 標準化行資料，確保所有行有相同的欄位.
     *
     * @param array<array<string, mixed>> $rows
     * @return array<array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        // 收集所有可能的欄位
        $allKeys = [];
        foreach ($rows as $row) {
            $allKeys = array_merge($allKeys, array_keys($row));
        }
        $allKeys = array_unique($allKeys);

        // 確保每行都有所有欄位
        $normalizedRows = [];
        foreach ($rows as $row) {
            $normalizedRow = [];
            foreach ($allKeys as $key) {
                $normalizedRow[$key] = $row[$key] ?? '';
            }
            $normalizedRows[] = $normalizedRow;
        }

        return $normalizedRows;
    }
}
