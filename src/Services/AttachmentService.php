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

    private const FORBIDDEN_EXTENSIONS = [
        'php',
        'php3',
        'php4',
        'php5',
        'phtml',
        'exe',
        'bat',
        'cmd',
        'sh',
        'cgi',
        'pl',
        'py',
        'asp',
        'aspx',
        'jsp'
    ];

    public function __construct(
        private AttachmentRepository $attachmentRepo,
        private PostRepository $postRepo,
        private CacheService $cache,
        private string $uploadDir
    ) {}

    public function validateFile(UploadedFileInterface $file): void
    {
        $filename = $file->getClientFilename();

        // 檢查檔案名稱是否包含路徑遍歷嘗試
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            throw new ValidationException('不支援的檔案類型');
        }

        // 檢查是否有多重副檔名
        $extensions = explode('.', $filename);
        array_shift($extensions); // 移除檔案名稱部分
        foreach ($extensions as $ext) {
            if (in_array(strtolower($ext), self::FORBIDDEN_EXTENSIONS, true)) {
                throw new ValidationException('不支援的檔案類型');
            }
        }

        // 檢查檔案大小
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new ValidationException('檔案大小超過限制（10MB）');
        }

        // 驗證 MIME 類型
        $mimeType = $file->getClientMediaType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new ValidationException('不支援的檔案類型');
        }

        // 掃描檔案內容是否含有潛在的惡意程式碼
        $stream = $file->getStream();
        $content = $stream->getContents();
        $stream->rewind(); // 重置串流位置

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
            '/onerror=/i',
            '/onfocus=/i',
            '/onblur=/i',
            '/onsubmit=/i',
            '/onmouseout=/i',
            '/ondblclick=/i',
            '/onkeypress=/i',
            '/onkeydown=/i',
            '/onkeyup=/i',
            '/<?php/i',
            '/<%/i',
            '/<asp/i',
            '/<jsp/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    public function upload(int $postId, UploadedFileInterface $file): Attachment
    {
        // 檢查文章是否存在
        if (!$this->postRepo->find($postId)) {
            throw new NotFoundException('找不到指定的文章');
        }

        $this->validateFile($file);

        // 安全的檔案名稱產生
        $originalFilename = $file->getClientFilename();
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $safeExtension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension));
        $newFilename = bin2hex(random_bytes(16)) . '.' . $safeExtension;

        // 確保上傳目錄存在且具有正確的權限
        $uploadPath = "{$this->uploadDir}/attachments";
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $storagePath = $uploadPath . '/' . $newFilename;

        try {
            // 先建立臨時檔案
            $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
            $file->moveTo($tempPath);

            // 將檔案移動到目標位置並設定權限
            if (!rename($tempPath, $storagePath)) {
                throw new \RuntimeException('無法移動檔案到目標位置');
            }

            chmod($storagePath, 0644);

            return $this->attachmentRepo->create([
                'uuid' => Uuid::uuid4()->toString(),
                'post_id' => $postId,
                'filename' => $newFilename,
                'original_name' => $originalFilename,
                'mime_type' => $file->getClientMediaType(),
                'file_size' => $file->getSize(),
                'storage_path' => "attachments/{$newFilename}"
            ]);
        } catch (\Throwable $e) {
            // 清理臨時檔案
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            // 清理目標檔案
            if (file_exists($storagePath)) {
                unlink($storagePath);
            }
            throw new ValidationException('檔案上傳失敗：' . $e->getMessage());
        }
    }

    public function download(string $uuid): array
    {
        $attachment = $this->attachmentRepo->findByUuid($uuid);
        if (!$attachment) {
            throw new NotFoundException('找不到指定的附件');
        }

        $filePath = "{$this->uploadDir}/{$attachment->getStoragePath()}";

        // 確保檔案在允許的目錄中
        $realPath = realpath($filePath);
        $uploadDirReal = realpath($this->uploadDir);

        if ($realPath === false || strpos($realPath, $uploadDirReal) !== 0) {
            throw new ValidationException('無效的檔案路徑');
        }

        if (!file_exists($filePath)) {
            throw new NotFoundException('找不到附件檔案');
        }

        return [
            'path' => $filePath,
            'name' => $attachment->getOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'size' => $attachment->getFileSize()
        ];
    }

    public function delete(string $uuid): void
    {
        $attachment = $this->attachmentRepo->findByUuid($uuid);
        if (!$attachment) {
            throw new NotFoundException('找不到指定的附件');
        }

        // 安全地刪除檔案
        $path = "{$this->uploadDir}/{$attachment->getStoragePath()}";
        if (file_exists($path)) {
            // 確保檔案在允許的目錄中
            $realPath = realpath($path);
            $uploadDirReal = realpath($this->uploadDir);

            if ($realPath === false || strpos($realPath, $uploadDirReal) !== 0) {
                throw new ValidationException('無效的檔案路徑');
            }

            unlink($path);
        }

        $this->attachmentRepo->delete($attachment->getId());
    }

    public function getByPostId(int $postId): array
    {
        return $this->attachmentRepo->getByPostId($postId);
    }
}
