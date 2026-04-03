<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Services;
use RuntimeException;
class FileSecurityService implements FileSecurityServiceInterface
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'text/plain' => ['txt'],
        'text/csv' => ['csv'],
    ];
    private const FORBIDDEN_EXTENSIONS = [
        'php',
        'php3',
        'php4',
        'php5',
        'phtml',
        'phar',
        'exe',
        'bat',
        'cmd',
        'com',
        'scr',
        'msi',
        'sh',
        'bash',
        'zsh',
        'fish',
        'cgi',
        'pl',
        'py',
        'rb',
        'go',
        'asp',
        'aspx',
        'jsp',
        'jspx',
        'html',
        'htm',
        'js',
        'vbs',
        'htaccess',
        'htpasswd',
        'sql',
        'db',
        'sqlite',
    ];
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const MAX_FILENAME_LENGTH = 255;
    public function validateUpload(UploadedFileInterface $file): void
    {
        $this->validateBasicProperties($file);
        $this->validateFileName($file->getClientFilename());
        $this->validateMimeType($file);
        $this->validateFileContent($file);
    }
    public function generateSecureFileName(string $originalName, string $prefix = ''): string
    {
        $extension = $this->extractSafeExtension($originalName);
        $timestamp = date('YmdHis');
        $random = bin2hex(random_bytes(8));
        return $prefix . $timestamp . '_' . $random . '.' . $extension;
    }
    public function detectActualMimeType(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw ValidationException::fromSingleError('file', '檔案不存在');
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('無法初始化檔案資訊檢測器');
        }
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        if ($mimeType === false) {
            throw ValidationException::fromSingleError('file', '無法檢測檔案 MIME 類型');
        }
        return $mimeType;
    }
    public function sanitizeFileName(string $fileName): string
    {
        // 移除路徑分隔符號和其他危險字元
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        // 移除多個連續的點號（防止路徑遍歷）
        $fileName = preg_replace('/\.{2,}/', '.', $fileName);
        // 確保不以點號開始（隱藏檔案）
        $fileName = ltrim($fileName, '.');
        // 限制檔名長度
        if (strlen($fileName) > self::MAX_FILENAME_LENGTH) {
            $fileName = substr($fileName, 0, self::MAX_FILENAME_LENGTH);
        }
        return $fileName;
    }
    public function isInAllowedDirectory(string $filePath, string $allowedBaseDir): bool
    {
        $realFilePath = realpath($filePath);
        $realBaseDir = realpath($allowedBaseDir);
        if ($realFilePath === false || $realBaseDir === false) {
            return false;
        }
        return str_starts_with($realFilePath, $realBaseDir);
    }
    private function validateBasicProperties(UploadedFileInterface $file): void
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw ValidationException::fromSingleError('file', '檔案上傳失敗：' . $this->getUploadErrorMessage($file->getError()));
        }
        if ($file->getSize() === null || $file->getSize() === 0) {
            throw ValidationException::fromSingleError('file', '檔案大小無效');
        }
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::fromSingleError('file', '檔案大小超過限制（10MB）');
        }
    }
    private function validateFileName(?string $fileName): void
    {
        if (empty($fileName)) {
            throw ValidationException::fromSingleError('filename', '檔案名稱無效');
        }
        // 檢查路徑遍歷攻擊
        if (
            str_contains($fileName, '..')
            || str_contains($fileName, '/')
            || str_contains($fileName, '\\')
        ) {
            throw ValidationException::fromSingleError('filename', '檔案名稱包含不安全字元');
        }
        // 檢查空字節攻擊
        if (str_contains($fileName, "\0")) {
            throw ValidationException::fromSingleError('filename', '檔案名稱包含空字節');
        }
        // 檢查多重副檔名
        $parts = explode('.', $fileName);
        if (count($parts) > 3) { // 允許最多兩個副檔名，如 file.tar.gz
            throw ValidationException::fromSingleError('filename', '檔案名稱包含過多副檔名');
        }
        // 檢查是否包含禁止的副檔名
        array_shift($parts); // 移除檔案名稱部分
        foreach ($parts as $extension) {
            if (in_array(strtolower($extension), self::FORBIDDEN_EXTENSIONS, true)) {
                throw ValidationException::fromSingleError('file', '不允許的檔案類型');
            }
        }
    }
    private function validateMimeType(UploadedFileInterface $file): void
    {
        $clientMimeType = $file->getClientMediaType();
        if (empty($clientMimeType) || !array_key_exists($clientMimeType, self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::fromSingleError('file', '不支援的檔案類型');
        }
        // 驗證副檔名與 MIME 類型是否匹配
        $fileName = $file->getClientFilename();
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_MIME_TYPES[$clientMimeType], true)) {
            throw ValidationException::fromSingleError('file', '檔案副檔名與類型不匹配');
        }
    }
    private function validateFileContent(UploadedFileInterface $file): void
    {
        $stream = $file->getStream();
        $content = $stream->read(8192); // 讀取前 8KB 檢查
        $stream->rewind();
        if ($this->containsMaliciousContent($content)) {
            throw ValidationException::fromSingleError('file', '檔案內容包含惡意程式碼');
        }
        // 檢查檔案簽名（魔術數字）
        if (!$this->validateFileSignature($content, $file->getClientMediaType())) {
            throw ValidationException::fromSingleError('file', '檔案格式驗證失敗');
        }
    }
    private function containsMaliciousContent(string $content): bool
    {
        $maliciousPatterns = [
            // Script tags
            '/<script[^>]*>/i',
            '/}<\/script>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:(?!image\/)/i',
            // Event handlers
            '/on\w+\s*=/i',
            // Server-side code
            '/<\?php/i',
            '/<%[\s\S]*?%>/i',
            '/<asp:/i',
            '/<jsp:/i',
            // Executable content
            '/MZ\x90\x00/', // PE executable header
            '/\x7fELF/', // ELF executable header
            '/#!/i', // Shebang
            // Base64 encoded scripts
            '/base64[,;]/i',
        ];
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        return false;
    }
    private function validateFileSignature(string $content, string $mimeType): bool
    {
        $signatures = [
            'image/jpeg' => ["\xFF\xD8\xFF"],
            'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
            'image/gif' => ['GIF87a', 'GIF89a'],
            'image/webp' => ['RIFF'],
            'application/pdf' => ['%PDF-'],
            'text/plain' => [], // 純文字檔案無固定簽名
            'text/csv' => [],
        ];
        if (!isset($signatures[$mimeType])) {
            return false;
        }
        if (empty($signatures[$mimeType])) {
            return true; // 無需驗證簽名的檔案類型
        }
        foreach ($signatures[$mimeType] as $signature) {
            if (str_starts_with($content, $signature)) {
                return true;
            }
        }
        return false;
    }
    private function extractSafeExtension(string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $extension = strtolower($extension);
        // 確保副檔名在允許清單中
        foreach (self::ALLOWED_MIME_TYPES as $allowedExtensions) {
            if (in_array($extension, $allowedExtensions, true)) {
                return $extension;
            }
        }
        return 'bin'; // 預設副檔名
    }
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => '檔案大小超過 PHP 設定限制',
            UPLOAD_ERR_FORM_SIZE => '檔案大小超過表單限制',
            UPLOAD_ERR_PARTIAL => '檔案僅部分上傳',
            UPLOAD_ERR_NO_FILE => '沒有檔案被上傳',
            UPLOAD_ERR_NO_TMP_DIR => '缺少臨時目錄',
            UPLOAD_ERR_CANT_WRITE => '檔案寫入失敗',
            UPLOAD_ERR_EXTENSION => 'PHP 擴充功能停止檔案上傳',
            default => '未知的上傳錯誤'
        };
    }
}
