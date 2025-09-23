<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Formatters;

use App\Domains\Statistics\Contracts\StatisticsFormatterInterface;
use DateTime;

/**
 * PDF 統計資料格式化器.
 *
 * 將統計資料格式化為 PDF 格式（簡化實作）。
 * 注意：這是一個基本實作，實際生產環境建議使用專業的 PDF 產生函式庫。
 */
final class PDFStatisticsFormatter implements StatisticsFormatterInterface
{
    public function getFormat(): string
    {
        return 'pdf';
    }

    public function getFileExtension(): string
    {
        return 'pdf';
    }

    public function getMimeType(): string
    {
        return 'application/pdf';
    }

    public function format(array $data, array $options = []): string
    {
        // 這是一個簡化的 PDF 實作
        // 實際應用中應使用 TCPDF、FPDF 或其他 PDF 函式庫

        $title = $options['title'] ?? '統計報告';
        $author = $options['author'] ?? '系統';
        $date = new DateTime()->format('Y-m-d H:i:s');

        // 建立簡化的 HTML 內容用於轉換
        $htmlContent = $this->generateHtmlContent($data, $title, $date);

        // 這裡應該使用真正的 PDF 產生函式庫
        // 目前返回模擬的 PDF 內容標記
        return $this->generateMockPdf($htmlContent, $title, $author);
    }

    public function supportsLargeData(): bool
    {
        return false; // PDF 格式通常不適合大量資料
    }

    public function getRecommendedFilename(string $type, array $options = []): string
    {
        $timestamp = new DateTime()->format('Y-m-d_H-i-s');
        $suffix = (string) ($options['filename_suffix'] ?? '');

        return "statistics_{$type}{$suffix}_{$timestamp}.pdf";
    }

    /**
     * 產生 HTML 內容.
     */
    private function generateHtmlContent(array $data, string $title, string $date): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; border-bottom: 2px solid #333; }
                h2 { color: #666; margin-top: 30px; }
                table { border-collapse: collapse; width: 100%; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .summary { background-color: #f9f9f9; padding: 15px; margin: 10px 0; }
                .date { color: #888; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <h1>{$title}</h1>
            <p class='date'>產生時間: {$date}</p>
        ";

        foreach ($data as $section => $sectionData) {
            $html .= '<h2>' . ucfirst($section) . '</h2>';

            if (is_array($sectionData)) {
                if ($this->isSequentialArray($sectionData) && !empty($sectionData) && is_array($sectionData[0])) {
                    // 表格資料
                    $html .= $this->generateTable($sectionData);
                } else {
                    // 摘要資料
                    $html .= $this->generateSummary($sectionData);
                }
            } else {
                $html .= "<p>{$sectionData}</p>";
            }
        }

        $html .= '
        </body>
        </html>';

        return $html;
    }

    /**
     * 產生表格 HTML.
     *
     * @param array<array<string, mixed>> $tableData
     */
    private function generateTable(array $tableData): string
    {
        if (empty($tableData)) {
            return '<p>無資料</p>';
        }

        $headers = array_keys($tableData[0]);
        $html = '<table><thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($tableData as $row) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * 產生摘要 HTML.
     *
     * @param array<string, mixed> $summaryData
     */
    private function generateSummary(array $summaryData): string
    {
        $html = '<div class="summary">';

        foreach ($summaryData as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $html .= '<p><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars((string) $value) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * 產生模擬的 PDF 內容.
     */
    private function generateMockPdf(string $htmlContent, string $title, string $author): string
    {
        // 這是一個模擬的 PDF 內容
        // 實際實作應該使用真正的 PDF 函式庫來轉換 HTML
        $pdfHeader = "%PDF-1.4\n";
        $pdfHeader .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
        $pdfHeader .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n";
        $pdfHeader .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\n";

        // 添加文件資訊
        $info = [
            'Title' => $title,
            'Author' => $author,
            'CreationDate' => 'D:' . new DateTime()->format('YmdHis'),
            'Producer' => 'Statistics Export Service',
        ];

        $mockContent = $pdfHeader;
        $mockContent .= "% Mock PDF Content - Title: {$title}\n";
        $mockContent .= '% HTML Length: ' . strlen($htmlContent) . " characters\n";
        $mockContent .= '% Generated: ' . date('Y-m-d H:i:s') . "\n";
        $mockContent .= "%%EOF\n";

        return $mockContent;
    }

    /**
     * 檢查是否為順序陣列.
     */
    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
}
