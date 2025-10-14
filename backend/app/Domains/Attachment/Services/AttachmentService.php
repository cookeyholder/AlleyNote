<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Services;

use App\Domains\Attachment\Contracts\AttachmentServiceInterface;
use App\Domains\Attachment\Models\Attachment;
use App\Domains\Attachment\Repositories\AttachmentRepository;
use App\Domains\Auth\Services\AuthorizationService;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Exception;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use PDO;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class AttachmentService implements AttachmentServiceInterface
{
    /**
     * 允許的 MIME 類型（包含 Microsoft Office 和 LibreOffice 格式）.
     */
    private const ALLOWED_MIME_TYPES = [
        // 圖片
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',

        // PDF
        'application/pdf',

        // Microsoft Office - Word
        'application/msword', // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx

        // Microsoft Office - Excel
        'application/vnd.ms-excel', // .xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx

        // Microsoft Office - PowerPoint
        'application/vnd.ms-powerpoint', // .ppt
        'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx

        // LibreOffice - Writer
        'application/vnd.oasis.opendocument.text', // .odt
        'application/vnd.oasis.opendocument.text-template', // .ott

        // LibreOffice - Calc
        'application/vnd.oasis.opendocument.spreadsheet', // .ods
        'application/vnd.oasis.opendocument.spreadsheet-template', // .ots

        // LibreOffice - Impress
        'application/vnd.oasis.opendocument.presentation', // .odp
        'application/vnd.oasis.opendocument.presentation-template', // .otp

        // LibreOffice - Draw
        'application/vnd.oasis.opendocument.graphics', // .odg

        // 純文字
        'text/plain',

        // 壓縮檔
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',

        // 媒體檔
        'audio/mpeg', // .mp3
        'video/mp4', // .mp4
        'video/x-msvideo', // .avi
        'video/quicktime', // .mov
    ];

    private const MAX_FILE_SIZE = 10485760; // 10MB（從設定讀取）

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
        'jsp',
    ];

    private FinfoMimeTypeDetector $mimeDetector;

    public function __construct(
        private AttachmentRepository $attachmentRepo,
        private PostRepository $postRepo,
        private AuthorizationService $authService,
        private ActivityLoggingServiceInterface $activityLogger,
        private string $uploadDir,
    ) {
        // 使用 League MIME type detector（基於 magic numbers）
        $this->mimeDetector = new FinfoMimeTypeDetector();
    }

    public function validateFile(UploadedFileInterface $file): void
    {
        $filename = $file->getClientFilename();

        // 檢查檔案名稱是否包含路徑遍歷嘗試
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            throw ValidationException::fromSingleError('file', '不支援的檔案類型');
        }

        // 檢查是否有多重副檔名
        $extensions = explode('.', $filename);
        array_shift($extensions); // 移除檔案名稱部分
        foreach ($extensions as $ext) {
            if (in_array(strtolower($ext), self::FORBIDDEN_EXTENSIONS, true)) {
                throw ValidationException::fromSingleError('file', '不支援的檔案類型');
            }
        }

        // 檢查檔案大小（從設定讀取）
        $maxSize = $this->getMaxFileSize();
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);

            throw ValidationException::fromSingleError('file', "檔案大小超過限制（{$maxSizeMB}MB）");
        }

        // 取得檔案內容
        $stream = $file->getStream();
        $content = $stream->getContents();
        $stream->rewind(); // 重置串流位置

        // 1. 使用 magic numbers 檢測真實的 MIME 類型
        $realMimeType = $this->mimeDetector->detectMimeTypeFromBuffer($content);

        // 2. 取得客戶端宣告的 MIME 類型作為參考
        $clientMimeType = $file->getClientMediaType();

        // 3. 從副檔名取得預期的 MIME 類型
        $pathMimeType = $this->mimeDetector->detectMimeTypeFromPath($filename ?? '');

        // 4. 驗證 MIME 類型（優先使用 magic numbers 檢測結果）
        $finalMimeType = $realMimeType ?? $clientMimeType ?? $pathMimeType;

        // 檢查是否在允許的類型列表中
        $allowedTypes = $this->getAllowedFileTypes();
        if ($finalMimeType !== null && !in_array($finalMimeType, $allowedTypes, true)) {
            // 如果不在允許列表中，再檢查是否有替代的 MIME 類型（某些檔案可能有多個 MIME 類型）
            if (!$this->isAlternativeMimeTypeAllowed($finalMimeType, $allowedTypes)) {
                throw ValidationException::fromSingleError('file', "不支援的檔案類型：{$finalMimeType}");
            }
        } elseif ($finalMimeType === null) {
            throw ValidationException::fromSingleError('file', '無法識別檔案類型');
        }

        // 5. 驗證客戶端宣告的 MIME 與實際檢測的是否一致（防止偽裝）
        if ($realMimeType && $clientMimeType && $realMimeType !== $clientMimeType) {
            // 允許某些已知的合法差異（例如 text/plain 與 application/octet-stream）
            if (!$this->isAcceptableMimeTypeMismatch($realMimeType, $clientMimeType)) {
                throw ValidationException::fromSingleError('file', '檔案類型驗證失敗：檔案內容與宣告不符');
            }
        }

        // 6. 掃描檔案內容是否含有潛在的惡意程式碼
        if ($this->containsMaliciousContent($content)) {
            throw ValidationException::fromSingleError('file', '檔案內容不安全');
        }
    }

    /**
     * 從設定讀取最大檔案大小.
     */
    private function getMaxFileSize(): int
    {
        try {
            /** @var string $dbPath */
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'max_upload_size' LIMIT 1");
            $stmt->execute();
            /** @var array<string, mixed>|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (is_array($result) && isset($result['value']) && is_numeric($result['value'])) {
                return (int) $result['value'];
            }
        } catch (Exception $e) {
            // 讀取失敗，使用預設值
        }

        return self::MAX_FILE_SIZE;
    }

    /**
     * 從設定讀取單篇文章附件數量上限.
     */
    private function getMaxAttachmentsPerPost(): int
    {
        try {
            /** @var string $dbPath */
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'max_attachments_per_post' LIMIT 1");
            $stmt->execute();
            /** @var array<string, mixed>|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (is_array($result) && isset($result['value']) && is_numeric($result['value'])) {
                return (int) $result['value'];
            }
        } catch (Exception $e) {
            // 讀取失敗，使用預設值
        }

        return 10; // 預設值：10 個附件
    }

    /**
     * 從設定讀取允許的檔案類型.
     */
    private function getAllowedFileTypes(): array
    {
        try {
            /** @var string $dbPath */
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'allowed_file_types' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (is_array($result) && isset($result['value']) && is_string($result['value'])) {
                $extensions = json_decode($result['value'], true);
                if (is_array($extensions)) {
                    // 將副檔名對應到 MIME 類型
                    return $this->extensionsToMimeTypes($extensions);
                }
            }
        } catch (Exception $e) {
            // 讀取失敗，使用預設值
        }

        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * 將副檔名陣列轉換為 MIME 類型陣列.
     */
    private function extensionsToMimeTypes(array $extensions): array
    {
        $mimeTypes = [];
        $extensionToMime = [
            // 圖片
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',

            // PDF
            'pdf' => 'application/pdf',

            // Microsoft Office
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

            // LibreOffice
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ott' => 'application/vnd.oasis.opendocument.text-template',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'otp' => 'application/vnd.oasis.opendocument.presentation-template',
            'odg' => 'application/vnd.oasis.opendocument.graphics',

            // 純文字
            'txt' => 'text/plain',

            // 壓縮檔
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',

            // 媒體
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
        ];

        foreach ($extensions as $ext) {
            if (isset($extensionToMime[$ext])) {
                $mimeTypes[] = $extensionToMime[$ext];
            }
        }

        return array_unique($mimeTypes);
    }

    /**
     * 檢查是否為可接受的替代 MIME 類型.
     */
    private function isAlternativeMimeTypeAllowed(string $mimeType, array $allowedTypes): bool
    {
        // 某些檔案格式可能有多個合法的 MIME 類型
        $alternatives = [
            'application/octet-stream' => ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'],
            'application/x-zip' => ['application/zip'],
            'application/x-zip-compressed' => ['application/zip'],
        ];

        if (isset($alternatives[$mimeType])) {
            foreach ($alternatives[$mimeType] as $alt) {
                if (in_array($alt, $allowedTypes, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 檢查 MIME 類型不一致是否可接受.
     */
    private function isAcceptableMimeTypeMismatch(string $realMimeType, string $clientMimeType): bool
    {
        // 某些已知的合法差異
        $acceptableMismatches = [
            // ZIP 檔案可能被識別為不同的 MIME 類型
            ['application/zip', 'application/x-zip-compressed'],
            ['application/zip', 'application/octet-stream'],

            // Office 檔案
            ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/octet-stream'],
            ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/octet-stream'],

            // 純文字
            ['text/plain', 'application/octet-stream'],
        ];

        foreach ($acceptableMismatches as [$type1, $type2]) {
            if (($realMimeType === $type1 && $clientMimeType === $type2)
                || ($realMimeType === $type2 && $clientMimeType === $type1)) {
                return true;
            }
        }

        return false;
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

    /**
     * 重新渲染圖片檔案以移除潛在的惡意程式碼
     */
    private function sanitizeImage(string $filePath, string $mimeType): bool
    {
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            return true; // 非圖片檔案不需要處理
        }

        try {
            // 檢查 GD 擴充是否可用
            if (!extension_loaded('gd')) {
                error_log('GD extension not available for image sanitization');

                return true; // 如果沒有 GD，跳過圖片處理
            }

            $image = null;

            // 根據 MIME 類型載入圖片
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($filePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($filePath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($filePath);
                    break;
            }

            if ($image === false) {
                throw ValidationException::fromSingleError('file', '無法處理圖片檔案');
            }

            // 取得圖片尺寸
            $width = imagesx($image);
            $height = imagesy($image);

            // 檢查圖片尺寸是否合理（防止記憶體攻擊）
            if ($width > 4096 || $height > 4096) {
                imagedestroy($image);

                throw ValidationException::fromSingleError('file', '圖片尺寸過大');
            }

            // 建立新的乾淨畫布
            $cleanImage = imagecreatetruecolor($width, $height);

            // 處理透明度（PNG 和 GIF）
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($cleanImage, false);
                imagesavealpha($cleanImage, true);
                $transparent = imagecolorallocatealpha($cleanImage, 255, 255, 255, 127);
                imagefill($cleanImage, 0, 0, $transparent);
                imagealphablending($cleanImage, true);
            }

            // 複製圖片到新畫布
            imagecopyresampled($cleanImage, $image, 0, 0, 0, 0, $width, $height, $width, $height);

            // 儲存乾淨的圖片（覆蓋原檔案）
            $result = false;
            switch ($mimeType) {
                case 'image/jpeg':
                    $result = imagejpeg($cleanImage, $filePath, 90);
                    break;
                case 'image/png':
                    $result = imagepng($cleanImage, $filePath, 6);
                    break;
                case 'image/gif':
                    $result = imagegif($cleanImage, $filePath);
                    break;
            }

            // 清理記憶體
            imagedestroy($image);
            imagedestroy($cleanImage);

            return $result;
        } catch (Exception $e) {
            error_log('Image sanitization failed: ' . $e->getMessage());

            throw ValidationException::fromSingleError('file', '圖片處理失敗：' . $e->getMessage());
        }
    }

    /**
     * 病毒掃描（如果可用）.
     */
    private function scanForVirus(string $filePath): bool
    {
        // 檢查是否有 ClamAV 可用
        $clamavPath = shell_exec('which clamscan');
        if (empty($clamavPath)) {
            // ClamAV 不可用，跳過掃描
            return true;
        }

        // 執行病毒掃描
        $command = escapeshellcmd(trim($clamavPath)) . ' --no-summary --infected ' . escapeshellarg($filePath);
        $output = shell_exec($command . ' 2>&1');
        $exitCode = shell_exec('echo $?');

        // ClamAV 回傳碼：0=乾淨, 1=感染, 2=錯誤
        if (intval($exitCode) === 1) {
            error_log("Virus detected in file: {$filePath}");

            return false;
        }

        return true;
    }

    /**
     * 改善的檔案驗證流程（減緩 TOCTOU 風險）.
     */
    private function secureFileValidation(UploadedFileInterface $file): array
    {
        $originalFilename = $file->getClientFilename();
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $safeExtension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension));
        $newFilename = bin2hex(random_bytes(16)) . '.' . $safeExtension;

        // 建立安全的臨時目錄
        $tempDir = sys_get_temp_dir() . '/alleynote_upload_' . bin2hex(random_bytes(8));
        if (!mkdir($tempDir, 0o700, true)) {
            throw ValidationException::fromSingleError('directory', '無法建立臨時目錄');
        }

        $tempPath = $tempDir . '/' . $newFilename;

        try {
            // 移動上傳檔案到安全的臨時位置
            $file->moveTo($tempPath);

            // 在臨時位置進行所有驗證
            $this->validateFile($file);

            // 重新驗證檔案類型（基於實際內容）
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actualMimeType = finfo_file($finfo, $tempPath);
            finfo_close($finfo);

            if (!in_array($actualMimeType, self::ALLOWED_MIME_TYPES, true)) {
                throw ValidationException::fromSingleError('file', '檔案類型不符合預期');
            }

            // 圖片重新渲染
            $this->sanitizeImage($tempPath, $actualMimeType);

            // 病毒掃描
            if (!$this->scanForVirus($tempPath)) {
                throw ValidationException::fromSingleError('file', '檔案包含惡意程式碼');
            }

            return [
                'temp_path' => $tempPath,
                'temp_dir' => $tempDir,
                'filename' => $newFilename,
                'original_name' => $originalFilename,
                'mime_type' => $actualMimeType,
                'file_size' => filesize($tempPath),
            ];
        } catch (Exception $e) {
            // 清理臨時檔案
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }

            // 將 RuntimeException 轉換為 ValidationException
            if ($e instanceof RuntimeException) {
                throw ValidationException::fromSingleError('file', '檔案上傳失敗');
            }

            throw $e;
        }
    }

    /**
     * 檢查使用者是否有權限操作指定文章.
     */
    private function canAccessPost(int $userId, int $postId): bool
    {
        // 檢查是否為管理員
        if ($this->authService->isSuperAdmin($userId)) {
            return true;
        }

        // 檢查文章是否存在並且是否為文章的擁有者
        $post = $this->postRepo->find($postId);
        if (!$post) {
            return false;
        }

        return $post->getUserId() === $userId;
    }

    /**
     * 檢查使用者是否有權限操作指定附件.
     */
    private function canAccessAttachment(int $userId, string $attachmentUuid): bool
    {
        // 檢查是否為管理員
        if ($this->authService->isSuperAdmin($userId)) {
            return true;
        }

        // 找到附件並檢查關聯的文章
        $attachment = $this->attachmentRepo->findByUuid($attachmentUuid);
        if (!$attachment) {
            return false;
        }

        // 透過附件找到文章，檢查是否為文章的擁有者
        return $this->canAccessPost($userId, $attachment->getPostId());
    }

    public function upload(int $postId, UploadedFileInterface $file, int $currentUserId): Attachment
    {
        if (!$this->canAccessPost($currentUserId, $postId)) {
            // 記錄權限檢查失敗
            $this->activityLogger->logFailure(
                ActivityType::ATTACHMENT_PERMISSION_DENIED,
                $currentUserId,
                reason: '嘗試上傳附件到無權限的文章',
                metadata: [
                    'post_id' => $postId,
                    'filename' => $file->getClientFilename(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getClientMediaType(),
                ],
            );

            throw ValidationException::fromSingleError('post_id', '無權限上傳附件到此公告');
        }

        // 檢查附件數量限制
        $currentAttachmentCount = $this->attachmentRepo->countByPostId($postId);
        $maxAttachments = $this->getMaxAttachmentsPerPost();

        if ($currentAttachmentCount >= $maxAttachments) {
            throw ValidationException::fromSingleError('file', "此文章附件數量已達上限（{$maxAttachments} 個）");
        }

        // 使用改善的檔案驗證流程
        try {
            $fileInfo = $this->secureFileValidation($file);
        } catch (ValidationException $e) {
            // 記錄不同類型的驗證失敗
            $error = $e->getErrors()[0] ?? ['message' => $e->getMessage()];
            $activityType = ActivityType::ATTACHMENT_SIZE_EXCEEDED; // 預設

            // 根據錯誤訊息判斷具體的失敗類型
            if (str_contains($error['message'], '病毒') || str_contains($error['message'], '惡意程式碼')) {
                $activityType = ActivityType::ATTACHMENT_VIRUS_DETECTED;
            } elseif (str_contains($error['message'], '大小超過')) {
                $activityType = ActivityType::ATTACHMENT_SIZE_EXCEEDED;
            }

            $this->activityLogger->logFailure(
                $activityType,
                $currentUserId,
                reason: $error['message'],
                metadata: [
                    'post_id' => $postId,
                    'filename' => $file->getClientFilename(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getClientMediaType(),
                ],
            );

            throw $e;
        }

        try {
            // 確保上傳目錄存在
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0o755, true);
            }

            // 移動檔案到最終位置
            $finalPath = $this->uploadDir . '/' . $fileInfo['filename'];
            if (!rename($fileInfo['temp_path'], $finalPath)) {
                throw ValidationException::fromSingleError('file', '檔案移動失敗');
            }

            // 清理臨時目錄
            rmdir($fileInfo['temp_dir']);

            // 儲存到資料庫
            $attachmentData = [
                'post_id' => $postId,
                'filename' => $fileInfo['filename'],
                'original_name' => $fileInfo['original_name'],
                'file_size' => $fileInfo['file_size'],
                'mime_type' => $fileInfo['mime_type'],
                'storage_path' => $finalPath,
            ];

            $attachment = $this->attachmentRepo->create($attachmentData);

            // 記錄成功上傳
            $this->activityLogger->logSuccess(
                ActivityType::ATTACHMENT_UPLOADED,
                $currentUserId,
                metadata: [
                    'attachment_uuid' => $attachment->getUuid(),
                    'post_id' => $postId,
                    'filename' => $fileInfo['filename'],
                    'original_name' => $fileInfo['original_name'],
                    'file_size' => $fileInfo['file_size'],
                    'mime_type' => $fileInfo['mime_type'],
                ],
            );

            return $attachment;
        } catch (Exception $e) {
            // 清理失敗時的檔案
            if (file_exists($fileInfo['temp_path'])) {
                unlink($fileInfo['temp_path']);
            }
            if (is_dir($fileInfo['temp_dir'])) {
                rmdir($fileInfo['temp_dir']);
            }
            if (isset($finalPath) && file_exists($finalPath)) {
                unlink($finalPath);
            }

            throw $e;
        }
    }

    public function download(string $uuid, int $currentUserId): array
    {
        $attachment = $this->attachmentRepo->findByUuid($uuid);
        if (!$attachment) {
            throw new NotFoundException('找不到指定的附件');
        }

        // 檢查權限
        if (!$this->canAccessAttachment($currentUserId, $uuid)) {
            $this->activityLogger->logFailure(
                ActivityType::ATTACHMENT_PERMISSION_DENIED,
                $currentUserId,
                reason: '嘗試下載無權限的附件',
                metadata: [
                    'attachment_uuid' => $uuid,
                    'post_id' => $attachment->getPostId(),
                    'filename' => $attachment->getOriginalName(),
                ],
            );

            throw ValidationException::fromSingleError('permission', '您沒有權限下載此附件');
        }

        $filePath = "{$this->uploadDir}/{$attachment->getStoragePath()}";

        // 確保檔案在允許的目錄中
        $realPath = realpath($filePath);
        $uploadDirReal = realpath($this->uploadDir);

        if ($realPath === false || strpos($realPath, $uploadDirReal) !== 0) {
            throw ValidationException::fromSingleError('path', '無效的檔案路徑');
        }

        if (!file_exists($filePath)) {
            throw new NotFoundException('找不到附件檔案');
        }

        // 記錄成功下載
        $this->activityLogger->logSuccess(
            ActivityType::ATTACHMENT_DOWNLOADED,
            $currentUserId,
            metadata: [
                'attachment_uuid' => $uuid,
                'post_id' => $attachment->getPostId(),
                'filename' => $attachment->getOriginalName(),
                'file_size' => $attachment->getFileSize(),
                'mime_type' => $attachment->getMimeType(),
            ],
        );

        return [
            'path' => $filePath,
            'name' => $attachment->getOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'size' => $attachment->getFileSize(),
        ];
    }

    public function delete(string $uuid, int $currentUserId): void
    {
        // 檢查使用者是否有權限操作此附件
        if (!$this->canAccessAttachment($currentUserId, $uuid)) {
            $this->activityLogger->logFailure(
                ActivityType::ATTACHMENT_PERMISSION_DENIED,
                $currentUserId,
                reason: '嘗試刪除無權限的附件',
                metadata: ['attachment_uuid' => $uuid],
            );

            throw ValidationException::fromSingleError('permission', '您沒有權限刪除此附件');
        }

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
                throw ValidationException::fromSingleError('path', '無效的檔案路徑');
            }

            unlink($path);
        }

        $this->attachmentRepo->delete($attachment->getId());

        // 記錄成功刪除
        $this->activityLogger->logSuccess(
            ActivityType::ATTACHMENT_DELETED,
            $currentUserId,
            metadata: [
                'attachment_uuid' => $uuid,
                'post_id' => $attachment->getPostId(),
                'filename' => $attachment->getOriginalName(),
                'file_size' => $attachment->getFileSize(),
            ],
        );
    }

    public function getByPostId(int $postId): array
    {
        return $this->attachmentRepo->getByPostId($postId);
    }
}
