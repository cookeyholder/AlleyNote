<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Contracts;

use App\Domains\Attachment\Models\Attachment;
use App\Shared\Contracts\RepositoryInterface;

/**
 * 附件資料庫介面.
 *
 * 定義附件相關的資料庫操作標準介面
 */
interface AttachmentRepositoryInterface extends RepositoryInterface
{
    /**
     * 根據 UUID 查找附件.
     * @param string $uuid 附件 UUID
     */
    public function findByUuid(string $uuid): ?Attachment;

    /**
     * 根據檔案雜湊值查找附件.
     * @param string $hash 檔案雜湊值
     */
    public function findByHash(string $hash): ?Attachment;

    /**
     * 根據貼文 ID 取得附件列表.
     * @param int $postId 貼文 ID
     */
    /**
     * @return array<Attachment>
     */
    public function getByPostId(int $postId, bool $includeDeleted = false): array;

    /**
     * 根據使用者 ID 取得附件列表.
     * @param int $userId 使用者 ID
     */
    /**
     * @return array<Attachment>
     */
    public function getByUserId(int $userId, int $limit = 50): array;

    /**
     * 建立新附件.
     * @param array $data 附件資料
     */
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Attachment;

    /**
     * 更新附件資料.
     * @param int $id 附件 ID
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): object;

    /**
     * 軟刪除附件.
     * @param int $id 附件 ID
     */
    public function delete(int $id): bool;

    /**
     * 永久刪除附件.
     * @param int $id 附件 ID
     */
    public function forceDelete(int $id): bool;

    /**
     * 復原已軟刪除的附件.
     * @param int $id 附件 ID
     */
    public function restore(int $id): bool;

    /**
     * 取得附件列表（分頁）.
     * @param int $page 頁碼
     * @param array $filters 篩選條件
     */
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array;

    /**
     * 取得已軟刪除的附件列表.
     * @param int $page 頁碼
     */
    /**
     * @return array<Attachment>
     */
    public function getTrashed(int $page = 1, int $perPage = 10): array;

    /**
     * 根據檔案類型取得附件.
     * @param string $mimeType MIME 類型
     */
    /**
     * @return array<Attachment>
     */
    public function getByMimeType(string $mimeType, int $limit = 10): array;

    /**
     * 根據檔案大小範圍取得附件.
     * @param int $minSize 最小檔案大小（位元組）
     * @param int $limit 限制筆數
     */
    /**
     * @return array<Attachment>
     */
    public function getBySizeRange(int $minSize, int $maxSize, int $limit = 10): array;

    /**
     * 取得孤兒附件（沒有關聯貼文的附件）.
     * @param int $olderThanDays 超過指定天數的附件
     */
    /**
     * @return array<Attachment>
     */
    public function getOrphanedAttachments(int $olderThanDays = 7): array;

    /**
     * 統計附件資訊.
     * @param array $conditions 統計條件
     */
    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>
     */
    public function getStats(array $conditions = []): array;

    /**
     * 檢查檔案是否已存在.
     * @param string $hash 檔案雜湊值
     */
    public function fileExists(string $hash, ?int $excludeId = null): bool;

    /**
     * 清理過期的臨時附件.
     * @param int $olderThanHours 超過指定小時數的臨時附件
     * @return int 清理的附件數量
     */
    public function cleanupTempFiles(int $olderThanHours = 24): int;

    /**
     * 批次更新附件狀態.
     * @param array $ids 附件 ID 陣列
     * @return int 更新的數量
     */
    /**
     * @param array<int> $ids
     */
    public function batchUpdateStatus(array $ids, string $status): int;

    /**
     * 搜尋附件.
     * @param string $keyword 關鍵字
     * @param int $limit 限制筆數
     */
    /**
     * @param array<string> $fields
     * @return array<Attachment>
     */
    public function search(string $keyword, array $fields = [], int $limit = 10): array;
}
