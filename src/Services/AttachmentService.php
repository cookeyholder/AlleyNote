<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\AttachmentServiceInterface;
use App\Repositories\AttachmentRepository;
use App\Repositories\PostRepository;
use App\Services\CacheService;
use App\Services\Enums\FileRules;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\Attachment;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;

class AttachmentService implements AttachmentServiceInterface
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];

    private const MAX_FILE_SIZE = 10485760; // 10MB

    public function __construct(
        private AttachmentRepository $attachmentRepo,
        private PostRepository $postRepo,
        private CacheService $cache,
        private string $uploadDir
    ) {}

    private function validateFile(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException('無效的檔案上傳');
        }

        // 檢查檔案大小
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new ValidationException('檔案大小超過限制（10MB）');
        }

        // 驗證 MIME 類型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new ValidationException('不支援的檔案類型');
        }

        // 掃描檔案內容是否含有潛在的惡意程式碼
        $content = file_get_contents($file['tmp_name']);
        if ($this->containsMaliciousContent($content)) {
            throw new ValidationException('檔案內容不安全');
        }
    }

    private function containsMaliciousContent(string $content): bool
    {
        $maliciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            '/base64/i',
            '/%3Cscript/i',
            '/eval\(/i',
            '/onload=/i',
            '/onclick=/i',
            '/onmouseover=/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    public function upload(int $postId, array $file): Attachment
    {
        // 檢查文章是否存在
        if (!$this->postRepo->find($postId)) {
            throw new NotFoundException('找不到指定的文章');
        }

        $this->validateFile($file);

        // 安全的檔案名稱產生
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $storagePath = "{$this->uploadDir}/attachments/" . $newFilename;

        // 安全地移動檔案
        if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
            throw new ValidationException('檔案上傳失敗');
        }

        // 設定正確的檔案權限
        chmod($storagePath, 0644);

        return $this->attachmentRepo->create([
            'post_id' => $postId,
            'filename' => $newFilename,
            'original_name' => $file['name'],
            'mime_type' => $file['type'],
            'file_size' => $file['size'],
            'storage_path' => $storagePath
        ]);
    }

    public function getByPostId(int $postId): array
    {
        return $this->attachmentRepo->getByPostId($postId);
    }

    public function delete(int $id): bool
    {
        $attachment = $this->attachmentRepo->find($id);
        if (!$attachment) {
            throw new NotFoundException('找不到指定的附件');
        }

        // 刪除實體檔案
        $path = "{$this->uploadDir}/{$attachment->getStoragePath()}";
        if (file_exists($path)) {
            unlink($path);
        }

        return $this->attachmentRepo->delete($id);
    }
}
