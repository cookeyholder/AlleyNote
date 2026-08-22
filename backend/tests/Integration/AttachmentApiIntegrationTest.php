<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use GuzzleHttp\Psr7\UploadedFile;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Tests\Support\AuthApiIntegrationTestCase;
use Throwable;

/**
 * Attachment HTTP API 整合測試.
 *
 * 以完整應用程式堆疊（路由、中介軟體、控制器、領域服務、SQLite 資料庫）覆蓋：
 * - 上傳附件（成功、權限、MIME 驗證、副檔名黑名單、內容安全掃描、大小限制）
 * - 附件列表（含附件與空清單）
 * - 下載端點（記錄目前尚未實作的行為）
 * - 刪除附件（擁有者、管理員越權刪除、無關使用者、不存在的 UUID）
 *
 * 上傳檔案寫入 storage/uploads 與系統暫存目錄，測試結束後一律清理。
 */
#[Group('integration')]
#[Group('api')]
#[Group('attachment')]
final class AttachmentApiIntegrationTest extends AuthApiIntegrationTestCase
{
    private const ADMIN_EMAIL = 'attachment-admin@example.com';

    private const OWNER_EMAIL = 'attachment-owner@example.com';

    private const OTHER_EMAIL = 'attachment-other@example.com';

    private const CLIENT_IP = '203.0.113.31';

    /**
     * 最小可用的 1x1 PNG（僅含 magic number 與 IHDR，可供 finfo 偵測）.
     */
    private const PNG_1X1_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private int $ownerId;

    private int $postId;

    /**
     * 測試開始前已存在於上傳目錄的檔案，tearDown 時據此保留.
     *
     * @var array<string>
     */
    private array $preExistingFiles = [];

    private string $previousDbEnv = '';

    protected function setUp(): void
    {
        parent::setUp();

        // 讓 AttachmentService 內部以獨立 PDO 讀取設定時落入確定性的預設值（10MB、完整 MIME 白名單）
        $previousDbEnv = $_ENV['DB_DATABASE'] ?? null;
        $this->previousDbEnv = is_string($previousDbEnv) ? $previousDbEnv : '';
        $_ENV['DB_DATABASE'] = ':memory:';

        $this->ownerId = $this->createAuthUser('attachmentowner', self::OWNER_EMAIL, ['user']);
        $this->createAuthUser('attachmentother', self::OTHER_EMAIL, ['user']);
        $this->createAuthUser('attachmentadmin', self::ADMIN_EMAIL, ['admin']);

        // 建立屬於 owner 的測試公告
        $this->postId = $this->insertTestPost([
            'user_id' => $this->ownerId,
            'title'   => '附件整合測試公告',
            'content' => '<p>附件整合測試內容</p>',
        ]);

        // 快照既有上傳檔案，供 tearDown 辨識新增檔案
        $this->preExistingFiles = is_dir($this->uploadDir())
            ? array_values(array_diff(scandir($this->uploadDir()) ?: [], ['.', '..']))
            : [];
    }

    protected function tearDown(): void
    {
        // 刪除測試期間新增的上傳檔案
        $uploadDir = $this->uploadDir();
        if (is_dir($uploadDir)) {
            foreach (array_diff(scandir($uploadDir) ?: [], ['.', '..']) as $file) {
                if (!in_array($file, $this->preExistingFiles, true)) {
                    @unlink($uploadDir . '/' . $file);
                }
            }
        }
        // 清理服務層遺留的暫存目錄
        foreach (glob(sys_get_temp_dir() . '/alleynote_upload_*') ?: [] as $tempDir) {
            $this->removeDirectory((string) $tempDir);
        }

        if ($this->previousDbEnv !== '') {
            $_ENV['DB_DATABASE'] = $this->previousDbEnv;
        } else {
            unset($_ENV['DB_DATABASE']);
        }

        parent::tearDown();
    }

    /**
     * 測試公告擁有者可上傳附件並寫入資料庫與磁碟.
     */
    public function testOwnerCanUploadAttachmentToOwnPost(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $content = base64_decode(self::PNG_1X1_BASE64, true);
        $this->assertNotFalse($content);
        $response = $this->uploadFile($token, 'screenshot.png', 'image/png', $content);
        $data = $this->getJson($response);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $uuid = $this->getStringValue($data, 'data', 'uuid');
        $storagePath = $this->getStringValue($data, 'data', 'storage_path');

        $this->assertNotSame('', $uuid);
        $this->assertSame($this->postId, $this->getIntValue($data, 'data', 'post_id'));
        $this->assertSame('screenshot.png', $this->getStringValue($data, 'data', 'original_name'));
        $this->assertSame('image/png', $this->getStringValue($data, 'data', 'mime_type'));
        $this->assertGreaterThan(0, $this->getIntValue($data, 'data', 'file_size'));
        $this->assertStringEndsWith('.png', basename($storagePath));

        // 檔案應存在於磁碟且實際內容仍為 PNG
        $absolutePath = $this->resolveUploadPath($storagePath);
        $this->assertFileExists($absolutePath);
        $this->assertSame($this->getIntValue($data, 'data', 'file_size'), (int) filesize($absolutePath));
        $this->assertSame('image/png', mime_content_type($absolutePath));

        // 資料庫狀態應與回應一致
        $row = $this->fetchAttachmentRow($uuid);
        $this->assertNotNull($row, '資料庫應存在剛上傳的附件');
        $this->assertSame($this->postId, self::intOf($row['post_id'] ?? null));
        $this->assertSame('screenshot.png', self::strOf($row['original_name'] ?? null));
        $this->assertSame('image/png', self::strOf($row['mime_type'] ?? null));
        $this->assertSame($storagePath, self::strOf($row['storage_path'] ?? null));
        $this->assertNull($row['deleted_at'] ?? null);
    }

    /**
     * 測試未認證上傳回傳 401 且不留下任何資料或檔案.
     */
    public function testUploadWithoutTokenReturns401(): void
    {
        $content = base64_decode(self::PNG_1X1_BASE64, true);
        $this->assertNotFalse($content);
        $response = $this->uploadFile(null, 'anon.png', 'image/png', $content);

        $this->assertErrorResponse($response, 401);
        $this->assertSame([], $this->attachmentRows(), '未認證請求不應寫入任何附件');
    }

    /**
     * 測試非擁有者對他人公告上傳附件回傳 400.
     */
    public function testUploadByNonOwnerReturns400(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OTHER_EMAIL, ip: $this->uniqueClientIp()));
        $content = base64_decode(self::PNG_1X1_BASE64, true);
        $this->assertNotFalse($content);
        $response = $this->uploadFile($token, 'intruder.png', 'image/png', $content);
        $data = $this->getJson($response);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('無權限', $this->getStringValue($data, 'error'));
        $this->assertSame([], $this->attachmentRows());
    }

    /**
     * 測試管理員可對任意公告上傳附件.
     */
    public function testAdminCanUploadToAnyPost(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::ADMIN_EMAIL, ip: $this->uniqueClientIp()));
        $response = $this->uploadFile($token, 'report.txt', 'text/plain', '管理員報告內容');

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(1, $this->attachmentRows());
    }

    /**
     * 測試危險副檔名被拒絕回傳 400.
     */
    public function testUploadWithForbiddenExtensionReturns400(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $response = $this->uploadFile($token, 'malware.exe', 'application/octet-stream', 'binary-like-content');
        $data = $this->getJson($response);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('不支援的檔案類型', $this->getStringValue($data, 'error'));
        $this->assertSame([], $this->attachmentRows());
    }

    /**
     * 測試宣告的 MIME 與實際內容不符時回傳 400.
     */
    public function testUploadWithMismatchedMimeTypeReturns400(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        // 內容為純文字卻宣稱是 PNG
        $response = $this->uploadFile($token, 'fake.png', 'image/png', 'just a plain text payload');
        $data = $this->getJson($response);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('檔案類型', $this->getStringValue($data, 'error'));
        $this->assertSame([], $this->attachmentRows());
    }

    /**
     * 測試含有惡意腳本內容的檔案被拒絕回傳 400.
     */
    public function testUploadWithMaliciousContentReturns400(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $response = $this->uploadFile($token, 'notes.txt', 'text/plain', '<script>alert("xss")</script>');
        $data = $this->getJson($response);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $error = $this->getStringValue($data, 'error');
        $this->assertTrue(
            str_contains($error, '不支援的檔案類型') || str_contains($error, '不安全'),
            '應以檔案類型或內容安全為由拒絕，實際訊息：' . $error,
        );
        $this->assertSame([], $this->attachmentRows());
    }

    /**
     * 測試超過大小限制的檔案回傳 400.
     */
    public function testUploadOversizedFileReturns400(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        // 預設上限 10MB（10485760），使用 10MB + 1 byte 的內容觸發限制
        $oversized = str_repeat("\0", 10485761);
        $response = $this->uploadFile($token, 'huge.bin', 'application/octet-stream', $oversized);
        $data = $this->getJson($response);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('大小超過限制', $this->getStringValue($data, 'error'));
        $this->assertSame([], $this->attachmentRows());
    }

    /**
     * 測試缺少檔案欄位回傳 400.
     */
    public function testUploadWithoutFileFieldReturns400(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $token]);
        $request = new ServerRequest(
            'POST',
            new Uri('http://localhost/api/posts/' . $this->postId . '/attachments'),
            $csrf['headers'],
            null,
            '1.1',
            ['REMOTE_ADDR' => self::CLIENT_IP],
        );
        $request = $request->withCookieParams($csrf['cookies']);

        $response = $this->app->run($request);
        $data = $this->getJson($response);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('缺少上傳檔案', $this->getStringValue($data, 'error'));
    }

    /**
     * 測試列表端點回傳公告的全部附件.
     */
    public function testListAttachmentsReturnsAllForPost(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $firstUuid = $this->uploadSuccessfully($token, 'first.txt', 'text/plain', '第一個附件');
        $secondUuid = $this->uploadSuccessfully($token, 'second.txt', 'text/plain', '第二個附件');

        $response = $this->request('GET', '/api/posts/' . $this->postId . '/attachments');
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        /** @var mixed $items */
        $items = $this->getNestedValue($data, 'data');
        $this->assertIsArray($items);
        $this->assertCount(2, $items);

        $uuids = [];
        foreach ($items as $item) {
            $this->assertIsArray($item);
            $uuids[] = $item['uuid'] ?? '';
            $this->assertSame($this->postId, $item['post_id'] ?? 0);
            $this->assertSame('text/plain', $item['mime_type'] ?? '');
            /** @var mixed $storagePath */
            $storagePath = $item['storage_path'] ?? '';
            $this->assertIsString($storagePath);
            $this->assertFileExists($this->resolveUploadPath($storagePath));
        }
        $this->assertEqualsCanonicalizing([$firstUuid, $secondUuid], $uuids);
    }

    /**
     * 測試沒有附件的公告回傳空列表.
     */
    public function testListAttachmentsWithoutAttachmentsReturnsEmptyArray(): void
    {
        $response = $this->request('GET', '/api/posts/' . $this->postId . '/attachments');
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        /** @var mixed $items */
        $items = $this->getNestedValue($data, 'data');
        $this->assertIsArray($items);
        $this->assertSame([], $items);
    }

    /**
     * 測試下載端點目前的行為（尚未實作，回傳 501）.
     */
    public function testDownloadEndpointIsNotImplementedYet(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $uuid = $this->uploadSuccessfully($token, 'download-me.txt', 'text/plain', '下載測試內容');

        $response = $this->request('GET', '/api/attachments/' . $uuid . '/download');
        $data = $this->getJson($response);

        // 控制器尚未實作檔案下載邏輯，此處記錄現況行為
        $this->assertSame(501, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('尚未實作', $this->getStringValue($data, 'error'));
    }

    /**
     * 測試擁有者刪除附件會軟刪除資料列並移除磁碟檔案.
     */
    public function testOwnerDeleteRemovesRowAndFile(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $uuid = $this->uploadSuccessfully($token, 'to-delete.txt', 'text/plain', '即將刪除的附件');
        $storagePath = $this->attachmentStoragePath($uuid);
        $this->assertFileExists($storagePath);

        $response = $this->deleteAttachment($token, $uuid);

        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('', (string) $response->getBody(), '204 回應不應有內容');

        // 附件採軟刪除：資料列保留但標記 deleted_at，且不再出現在列表中
        $row = $this->fetchAttachmentRow($uuid);
        $this->assertNotNull($row);
        $this->assertNotNull($row['deleted_at'] ?? null, '刪除後資料列應被標記 deleted_at');
        $listResponse = $this->request('GET', '/api/posts/' . $this->postId . '/attachments');
        /** @var mixed $items */
        $items = $this->getNestedValue($this->getJson($listResponse), 'data');
        $this->assertIsArray($items);
        $this->assertSame([], $items, '已刪除的附件不應出現在列表中');
        $this->assertFileDoesNotExist($storagePath);
    }

    /**
     * 測試管理員可刪除他人公告的附件.
     */
    public function testAdminCanDeleteOthersAttachment(): void
    {
        $ownerToken = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $uuid = $this->uploadSuccessfully($ownerToken, 'admin-delete.txt', 'text/plain', '管理員刪除目標');

        $adminToken = $this->accessTokenOf($this->loginUser(self::ADMIN_EMAIL, ip: $this->uniqueClientIp()));
        $response = $this->deleteAttachment($adminToken, $uuid);

        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        $row = $this->fetchAttachmentRow($uuid);
        $this->assertNotNull($row);
        $this->assertNotNull($row['deleted_at'] ?? null, '管理員刪除後資料列應被標記 deleted_at');
    }

    /**
     * 測試無關使用者刪除他人附件回傳 400 且資料不受影響.
     */
    public function testUnrelatedUserDeleteReturns400(): void
    {
        $ownerToken = $this->accessTokenOf($this->loginUser(self::OWNER_EMAIL, ip: $this->uniqueClientIp()));
        $uuid = $this->uploadSuccessfully($ownerToken, 'keep-me.txt', 'text/plain', '不該被刪除的附件');

        $otherToken = $this->accessTokenOf($this->loginUser(self::OTHER_EMAIL, ip: $this->uniqueClientIp()));
        $response = $this->deleteAttachment($otherToken, $uuid);
        $data = $this->getJson($response);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('權限', $this->getStringValue($data, 'error'));
        $this->assertNotNull($this->fetchAttachmentRow($uuid), '附件不應被刪除');
        $this->assertFileExists($this->attachmentStoragePath($uuid));
    }

    /**
     * 測試管理員刪除不存在的附件回傳 404.
     */
    public function testAdminDeleteNonExistentAttachmentReturns404(): void
    {
        $adminToken = $this->accessTokenOf($this->loginUser(self::ADMIN_EMAIL, ip: $this->uniqueClientIp()));
        $response = $this->deleteAttachment($adminToken, 'f47ac10b-58cc-4372-a567-0e02b2c3d479');
        $data = $this->getJson($response);

        // 此端點的錯誤格式為 {error: ...}，不含 success 標記
        $this->assertSame(404, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotEmpty($this->getStringValue($data, 'error'));
    }

    /**
     * 以上傳檔案發送 POST /api/posts/{post_id}/attachments 請求.
     */
    private function uploadFile(?string $token, string $filename, string $clientMime, string $content): ResponseInterface
    {
        $headers = [];
        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        $request = new ServerRequest(
            'POST',
            new Uri('http://localhost/api/posts/' . $this->postId . '/attachments'),
            $headers,
            null,
            '1.1',
            ['REMOTE_ADDR' => self::CLIENT_IP],
        );

        return $this->app->run(
            $request->withUploadedFiles(['file' => $this->makeUploadedFile($filename, $clientMime, $content)]),
        );
    }

    /**
     * 成功上傳檔案並回傳附件 UUID.
     */
    private function uploadSuccessfully(string $token, string $filename, string $clientMime, string $content): string
    {
        $response = $this->uploadFile($token, $filename, $clientMime, $content);
        $data = $this->getJson($response);
        $this->assertSame(201, $response->getStatusCode(), '前置步驟：上傳附件應成功：' . $response->getBody());

        $uuid = $this->getStringValue($data, 'data', 'uuid');
        $this->assertNotSame('', $uuid);

        return $uuid;
    }

    /**
     * 發送 DELETE /api/attachments/{id} 請求.
     */
    private function deleteAttachment(string $token, string $uuid): ResponseInterface
    {
        $headers = ['Authorization' => 'Bearer ' . $token];
        $request = new ServerRequest(
            'DELETE',
            new Uri('http://localhost/api/attachments/' . $uuid),
            $headers,
            null,
            '1.1',
            ['REMOTE_ADDR' => self::CLIENT_IP],
        );

        return $this->app->run($request);
    }

    /**
     * 建構模擬 multipart 上傳的 UploadedFile.
     */
    private function makeUploadedFile(string $filename, string $clientMime, string $content): UploadedFileInterface
    {
        return new UploadedFile(
            new Stream($content),
            strlen($content),
            UPLOAD_ERR_OK,
            $filename,
            $clientMime,
        );
    }

    /**
     * 取得上傳目錄路徑（與 container.php 的定義一致）.
     */
    private function uploadDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads';
    }

    /**
     * 將資料庫中的相對儲存路徑組合為磁碟絕對路徑.
     */
    private function resolveUploadPath(string $relativePath): string
    {
        return $this->uploadDir() . '/' . ltrim($relativePath, '/');
    }

    /**
     * 由資料庫取得附件資料列.
     *
     * @return array<string, mixed>|null
     */
    private function fetchAttachmentRow(string $uuid): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM attachments WHERE uuid = :uuid');
        $stmt->execute(['uuid' => $uuid]);
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * 由資料庫取得附件的磁碟絕對路徑.
     */
    private function attachmentStoragePath(string $uuid): string
    {
        $row = $this->fetchAttachmentRow($uuid);
        $this->assertNotNull($row, '附件應存在：' . $uuid);

        return $this->resolveUploadPath(self::strOf($row['storage_path'] ?? null));
    }

    /**
     * 統計 attachments 資料列數量.
     *
     * @return list<array<string, mixed>>
     */
    private function attachmentRows(): array
    {
        $stmt = $this->db->query('SELECT * FROM attachments');
        if ($stmt === false) {
            return [];
        }
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return $rows;
    }

    /**
     * 遞迴刪除目錄及其內容.
     */
    private function removeDirectory(string $directory): void
    {
        try {
            if (!is_dir($directory)) {
                return;
            }
            $items = scandir($directory) ?: [];
            foreach (array_diff($items, ['.', '..']) as $item) {
                $path = $directory . '/' . $item;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    @unlink($path);
                }
            }
            @rmdir($directory);
        } catch (Throwable $e) {
            throw new RuntimeException('無法清理暫存目錄：' . $directory, 0, $e);
        }
    }

    /**
     * 將資料庫欄位值安全轉為字串.
     */
    private static function strOf(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * 將資料庫欄位值安全轉為整數.
     */
    private static function intOf(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }
}
