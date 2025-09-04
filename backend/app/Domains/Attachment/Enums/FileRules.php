<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Enums;

class FileRules
{
    // 允許的檔案類型
    public const ALLOWED_MIME_TYPES = [
        // 圖片
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        // 文件
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // 文本
        'text/plain',
        'text/csv',
    ];

    // 檔案大小限制 (20MB)
    public const MAX_FILE_SIZE = 20 * 1024 * 1024;

    // 檔案副檔名對應表
    public const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
    ];
}
