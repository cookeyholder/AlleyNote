<?php

declare(strict_types=1);

namespace App\DTOs\Attachment;

use App\DTOs\BaseDTO;

/**
 * 建立附件的資料傳輸物件
 * 
 * 用於安全地傳輸建立附件所需的資料，防止巨量賦值攻擊
 */
class CreateAttachmentDTO extends BaseDTO
{
    public readonly int $postId;
    public readonly string $filename;
    public readonly string $originalName;
    public readonly string $mimeType;
    public readonly int $fileSize;
    public readonly string $storagePath;
    public readonly int $uploadedBy;

    /**
     * @param array $data 輸入資料
     * @throws \InvalidArgumentException 當必填欄位缺失或資料格式錯誤時
     */
    public function __construct(array $data)
    {
        // 驗證必填欄位
        $this->validateRequired([
            'post_id',
            'filename',
            'original_name',
            'mime_type',
            'file_size',
            'storage_path',
            'uploaded_by'
        ], $data);

        // 設定屬性
        $this->postId = $this->getInt($data, 'post_id');
        $this->filename = $this->getString($data, 'filename');
        $this->originalName = $this->getString($data, 'original_name');
        $this->mimeType = $this->getString($data, 'mime_type');
        $this->fileSize = $this->getInt($data, 'file_size');
        $this->storagePath = $this->getString($data, 'storage_path');
        $this->uploadedBy = $this->getInt($data, 'uploaded_by');

        // 驗證資料
        $this->validate();
    }

    /**
     * 驗證資料完整性
     * 
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        // 驗證檔案名稱
        if (strlen($this->filename) > 255) {
            throw new \InvalidArgumentException('檔案名稱不能超過 255 字元');
        }

        if (strlen($this->originalName) > 255) {
            throw new \InvalidArgumentException('原始檔案名稱不能超過 255 字元');
        }

        // 驗證檔案大小（限制為 10MB）
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        if ($this->fileSize <= 0 || $this->fileSize > $maxFileSize) {
            throw new \InvalidArgumentException(
                sprintf('檔案大小必須在 1 byte 到 %d bytes 之間', $maxFileSize)
            );
        }

        // 驗證 MIME 類型
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        if (!in_array($this->mimeType, $allowedMimeTypes, true)) {
            throw new \InvalidArgumentException('不支援的檔案類型');
        }

        // 驗證儲存路徑
        if (strlen($this->storagePath) > 500) {
            throw new \InvalidArgumentException('儲存路徑不能超過 500 字元');
        }

        // 驗證路徑不包含危險字元
        if (preg_match('/[<>:"|?*]/', $this->storagePath)) {
            throw new \InvalidArgumentException('儲存路徑包含不安全的字元');
        }
    }

    /**
     * 轉換為陣列格式（供 Repository 使用）
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'post_id' => $this->postId,
            'filename' => $this->filename,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'storage_path' => $this->storagePath,
            'uploaded_by' => $this->uploadedBy,
        ];
    }

    /**
     * 檢查是否為圖片檔案
     * 
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    /**
     * 取得檔案副檔名
     * 
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }
}
