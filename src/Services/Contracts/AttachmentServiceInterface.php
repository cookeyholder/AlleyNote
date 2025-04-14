<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Attachment;
use Psr\Http\Message\UploadedFileInterface;

interface AttachmentServiceInterface
{
    public function upload(int $postId, UploadedFileInterface $file): Attachment;
    public function download(string $uuid): array;
    public function delete(string $uuid): void;
    public function validateFile(UploadedFileInterface $file): void;
}
