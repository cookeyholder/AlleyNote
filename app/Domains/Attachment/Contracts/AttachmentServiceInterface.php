<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Contracts;

use App\Domains\Attachment\Models\Attachment;
use Psr\Http\Message\UploadedFileInterface;

interface AttachmentServiceInterface
{
    public function upload(int $postId, UploadedFileInterface $file, int $currentUserId): Attachment;

    public function download(string $uuid): array;

    public function delete(string $uuid, int $currentUserId): void;

    public function validateFile(UploadedFileInterface $file): void;
}
