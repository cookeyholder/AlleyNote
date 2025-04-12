<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AttachmentRepository;
use App\Repositories\PostRepository;
use App\Services\CacheService;
use App\Services\Enums\FileRules;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\Attachment;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;

class AttachmentService
{
    public function __construct(
        private AttachmentRepository $attachmentRepo,
        private PostRepository $postRepo,
        private CacheService $cache,
        private string $uploadDir
    ) {}

    public function upload(int $postId, UploadedFileInterface $file): Attachment
    {
        // 檢查文章是否存在
        if (!$this->postRepo->find($postId)) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 驗證檔案
        $this->validateFile($file);

        // 準備儲存資訊
        $originalName = $file->getClientFilename();
        $mimeType = $file->getClientMediaType();
        $size = $file->getSize();
        $extension = FileRules::MIME_EXTENSIONS[$mimeType] ?? 'bin';

        // 生成檔案名稱與儲存路徑
        $yearMonth = date('Y/m');
        $filename = Uuid::uuid4()->toString() . '.' . $extension;
        $relativePath = "{$yearMonth}/{$filename}";
        $absolutePath = "{$this->uploadDir}/{$relativePath}";

        // 確保目錄存在
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // 移動上傳的檔案
        $file->moveTo($absolutePath);

        // 建立附件記錄
        return $this->attachmentRepo->create([
            'post_id' => $postId,
            'filename' => $relativePath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $size,
            'storage_path' => $relativePath
        ]);
    }

    private function validateFile(UploadedFileInterface $file): void
    {
        // 檢查檔案是否成功上傳
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException('檔案上傳失敗');
        }

        // 檢查檔案類型
        $mimeType = $file->getClientMediaType();
        if (!in_array($mimeType, FileRules::ALLOWED_MIME_TYPES)) {
            throw new ValidationException('不支援的檔案類型');
        }

        // 檢查檔案大小
        if ($file->getSize() > FileRules::MAX_FILE_SIZE) {
            throw new ValidationException('檔案大小不可超過 20MB');
        }
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
