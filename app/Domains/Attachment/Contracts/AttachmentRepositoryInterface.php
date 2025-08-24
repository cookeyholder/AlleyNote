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
     *
     * @param string $uuid 附件 UUID
     */
    public function findByUuid(string $uuid): ?Attachment;

    /**
     * 根據檔案雜湊值查找附件.
     *
     * @param string $hash 檔案雜湊值
     */
    public function findByHash(string $hash): ?Attachment;

    /**
     * 根據貼文 ID 取得附件列表.
     *
     * @param int $postId 貼文 ID
     * @param bool $includeDeleted 是否包含已刪除的附件
     */
    public function getByPostId(int $postId, bool $includeDeleted = false): array;

    /**
     * 根據使用者 ID 取得附件列表.
     *
     * @param int $userId 使用者 ID
     * @param int $limit 限制筆數
     */
    public function getByUserId(int $userId, int $limit = 50): array;

    /**
     * 建立新附件.
     *
     * @param array $data 附件資料
     */
    public function create(array $data): Attachment;

    /**
     * 更新附件資料.
     *
     * @param int $id 附件 ID
     * @param array $data 更新的資料
     */
    public function update(int $id, array $data): bool;

    /**
     * 軟刪除附件.
     *
     * @param int $id 附件 ID
     */
    public function delete(int $id): bool;

    /**
     * 永久刪除附件.
     *
     * @param int $id 附件 ID
     */
    public function forceDelete(int $id): bool;

    /**
     * 復原已軟刪除的附件.
     *
     * @param int $id 附件 ID
     */
    public function restore(int $id): bool;

    /**
     * 取得附件列表（分頁）.
     *
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array;

    /**
     * 取得已軟刪除的附件列表.
     *
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     */
    public function getTrashed(int $page = 1, int $perPage = 10): array;

    /**
     * 根據檔案類型取得附件.
     *
     * @param string $mimeType MIME 類型
     * @param int $limit 限制筆數
     */
    public function getByMimeType(string $mimeType, int $limit = 10): array;

    /**
     * 根據檔案大小範圍取得附件.
     *
     * @param int $minSize 最小檔案大小（位元組）
     * @param int $maxSize 最大檔案大小（位元組）
     * @param int $limit 限制筆數
     */
    public function getBySizeRange(int $minSize, int $maxSize, int $limit = 10): array;

    /**
     * 取得孤兒附件（沒有關聯貼文的附件）.
     *
     * @param int $olderThanDays 超過指定天數的附件
     */
    public function getOrphanedAttachments(int $olderThanDays = 7): array;

    /**
     * 統計附件資訊.
     *
     * @param array $conditions 統計條件
     * @return array 包含總數、總大小、各類型數量等
     */
    public function getStats(array $conditions = []): array;

    /**
     * 檢查檔案是否已存在.
     *
     * @param string $hash 檔案雜湊值
     * @param int|null $excludeId 排除的附件 ID
     */
    public function fileExists(string $hash, ?int $excludeId = null): bool;

    /**
     * 清理過期的臨時附件.
     *
     * @param int $olderThanHours 超過指定小時數的臨時附件
     * @return int 清理的附件數量
     */
    public function cleanupTempFiles(int $olderThanHours = 24): int;

    /**
     * 批次更新附件狀態.
     *
     * @param array $ids 附件 ID 陣列
     * @param string $status 新狀態
     * @return int 更新的數量
     */
    public function batchUpdateStatus(array $ids, string $status): int;

    /**
     * 搜尋附件.
     *
     * @param string $keyword 關鍵字
     * @param array $fields 搜尋欄位
     * @param int $limit 限制筆數
     */
    public function search(string $keyword, array $fields = ['original_name'], int $limit = 10): array;
}
