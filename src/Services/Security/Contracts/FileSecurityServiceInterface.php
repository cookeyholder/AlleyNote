<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

use Psr\Http\Message\UploadedFileInterface;

interface FileSecurityServiceInterface
{
    /**
     * 驗證上傳的檔案是否安全
     *
     * @throws \App\Exceptions\ValidationException
     */
    public function validateUpload(UploadedFileInterface $file): void;

    /**
     * 產生安全的檔案名稱
     */
    public function generateSecureFileName(string $originalName, string $prefix = ''): string;

    /**
     * 檢測檔案的實際 MIME 類型
     */
    public function detectActualMimeType(string $filePath): string;

    /**
     * 清理檔案名稱，移除危險字元
     */
    public function sanitizeFileName(string $fileName): string;

    /**
     * 檢查檔案路徑是否在允許的目錄內
     */
    public function isInAllowedDirectory(string $filePath, string $allowedBaseDir): bool;
}
