<?php

declare(strict_types=1);

namespace App\Domains\Attachment\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use App\Shared\Exceptions\ValidationException;
use InvalidArgumentException;

/**
 * 建立附件的資料傳輸物件.
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
     * @param ValidatorInterface $validator 驗證器實例
     * @param array<string, mixed> $data 輸入資料
     * @throws ValidationException 當驗證失敗時
     */
    public function __construct(ValidatorInterface $validator, array $data)
    {
        parent::__construct($validator);

        // 添加附件專用驗證規則
        $this->addAttachmentValidationRules();

        // 驗證資料
        $validatedData = $this->validate($data);

        // 設定屬性
        $this->postId = is_int($validatedData['post_id']) ? $validatedData['post_id'] : (is_numeric($validatedData['post_id']) ? (int) $validatedData['post_id'] : throw new InvalidArgumentException('post_id must be numeric'));
        $this->filename = is_string($validatedData['filename']) ? trim($validatedData['filename']) : throw new InvalidArgumentException('filename must be string');
        $this->originalName = is_string($validatedData['original_name']) ? trim($validatedData['original_name']) : throw new InvalidArgumentException('original_name must be string');
        $this->mimeType = is_string($validatedData['mime_type']) ? trim($validatedData['mime_type']) : throw new InvalidArgumentException('mime_type must be string');
        $this->fileSize = is_int($validatedData['file_size']) ? $validatedData['file_size'] : (is_numeric($validatedData['file_size']) ? (int) $validatedData['file_size'] : throw new InvalidArgumentException('file_size must be numeric'));
        $this->storagePath = is_string($validatedData['storage_path']) ? trim($validatedData['storage_path']) : throw new InvalidArgumentException('storage_path must be string');
        $this->uploadedBy = is_int($validatedData['uploaded_by']) ? $validatedData['uploaded_by'] : (is_numeric($validatedData['uploaded_by']) ? (int) $validatedData['uploaded_by'] : throw new InvalidArgumentException('uploaded_by must be numeric'));
    }

    /**
     * 添加附件專用驗證規則.
     */
    private function addAttachmentValidationRules(): void
    {
        // 文章 ID 驗證規則
        $this->validator->addRule('post_id', function ($value) {
            return is_numeric($value) && (int) $value > 0;
        });

        // 檔案名稱驗證規則
        $this->validator->addRule('filename', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $filename = trim($value);
            $maxLength = $parameters[0] ?? 255;

            // 檢查長度
            if (mb_strlen($filename, 'UTF-8') > $maxLength) {
                return false;
            }

            // 檢查是否包含危險字元
            if (preg_match('/[<>:"|?*\\/]/', $filename)) {
                return false;
            }

            // 檢查是否為空
            if (empty($filename)) {
                return false;
            }

            return true;
        });

        // 原始檔案名稱驗證規則
        $this->validator->addRule('original_name', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $originalName = trim($value);
            $maxLength = $parameters[0] ?? 255;

            // 檢查長度
            if (mb_strlen($originalName, 'UTF-8') > $maxLength) {
                return false;
            }

            // 檢查是否為空
            if (empty($originalName)) {
                return false;
            }

            return true;
        });

        // MIME 類型驗證規則
        $this->validator->addRule('mime_type', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'application/pdf',
                'text/plain',
                'text/csv',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'application/x-zip-compressed',
                'application/octet-stream',
            ];

            return in_array($value, $allowedMimeTypes, true);
        });

        // 檔案大小驗證規則
        $this->validator->addRule('file_size', function ($value, array $parameters) {
            if (!is_numeric($value)) {
                return false;
            }

            $fileSize = (int) $value;
            $minSize = $parameters[0] ?? 1;
            $maxSize = $parameters[1] ?? (10 * 1024 * 1024); // 預設 10MB

            return $fileSize >= $minSize && $fileSize <= $maxSize;
        });

        // 儲存路徑驗證規則
        $this->validator->addRule('storage_path', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $storagePath = trim($value);
            $maxLength = $parameters[0] ?? 500;

            // 檢查長度
            if (mb_strlen($storagePath, 'UTF-8') > $maxLength) {
                return false;
            }

            // 檢查是否包含危險字元
            if (preg_match('/[<>:"|?*]/', $storagePath)) {
                return false;
            }

            // 檢查是否為空
            if (empty($storagePath)) {
                return false;
            }

            // 檢查路徑格式（應該以 uploads/ 開頭）
            if (!str_starts_with($storagePath, 'uploads/')) {
                return false;
            }

            return true;
        });

        // 上傳者 ID 驗證規則
        $this->validator->addRule('uploaded_by', function ($value) {
            return is_numeric($value) && (int) $value > 0;
        });

        // 添加繁體中文錯誤訊息
        $this->validator->addMessage('post_id', '文章 ID 必須是正整數');
        $this->validator->addMessage('filename', '檔案名稱長度不能超過 :max 個字元，且不能包含危險字元');
        $this->validator->addMessage('original_name', '原始檔案名稱長度不能超過 :max 個字元');
        $this->validator->addMessage('mime_type', '不支援的檔案類型');
        $this->validator->addMessage('file_size', '檔案大小必須在 :min bytes 到 :max bytes 之間');
        $this->validator->addMessage('storage_path', '儲存路徑長度不能超過 :max 個字元，且不能包含危險字元');
        $this->validator->addMessage('uploaded_by', '上傳者 ID 必須是正整數');
    }

    /**
     * 取得驗證規則.
     */
    protected function getValidationRules(): array
    {
        return [
            'post_id' => 'required|post_id',
            'filename' => 'required|string|filename:255',
            'original_name' => 'required|string|original_name:255',
            'mime_type' => 'required|string|mime_type',
            'file_size' => 'required|file_size:1,10485760', // 1 byte 到 10MB
            'storage_path' => 'required|string|storage_path:500',
            'uploaded_by' => 'required|uploaded_by',
        ];
    }

    /**
     * 轉換為陣列格式（供 Repository 使用）.
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
     * 檢查是否為圖片檔案.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    /**
     * 檢查是否為文件檔案.
     */
    public function isDocument(): bool
    {
        $documentMimes = [
            'application/pdf',
            'text/plain',
            'text/csv',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return in_array($this->mimeType, $documentMimes, true);
    }

    /**
     * 檢查是否為壓縮檔案.
     */
    public function isArchive(): bool
    {
        $archiveMimes = [
            'application/zip',
            'application/x-zip-compressed',
        ];

        return in_array($this->mimeType, $archiveMimes, true);
    }

    /**
     * 取得檔案副檔名.
     */
    public function getExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * 取得人類可讀的檔案大小.
     */
    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * 取得檔案類型類別.
     */
    public function getFileCategory(): string
    {
        if ($this->isImage()) {
            return 'image';
        }

        if ($this->isDocument()) {
            return 'document';
        }

        if ($this->isArchive()) {
            return 'archive';
        }

        return 'other';
    }
}
