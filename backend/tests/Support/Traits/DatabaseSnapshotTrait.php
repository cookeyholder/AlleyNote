<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use PDO;
use RuntimeException;

/**
 * 資料庫狀態快照測試功能 Trait.
 * 
 * 提供捕捉資料列狀態並進行差異比對的斷言工具。
 */
trait DatabaseSnapshotTrait
{
    /**
     * 擷取指定資料列的目前狀態快照.
     * 
     * @param string $table 資料表名稱
     * @param mixed $id 主鍵值
     * @return array<string, mixed> 快照數據
     */
    protected function captureRow(string $table, mixed $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException("無法擷取快照：在資料表 [{$table}] 中找不到 ID 為 [{$id}] 的資料。");
        }

        return [
            '__table' => $table,
            '__id' => $id,
            'data' => $row
        ];
    }

    /**
     * 斷言資料列狀態與快照完全一致（無變動）.
     * 
     * @param array $snapshot 先前擷取的快照
     */
    protected function assertRowUnchanged(array $snapshot): void
    {
        $this->assertRowChangedOnly($snapshot, []);
    }

    /**
     * 斷言資料列僅有指定欄位發生變動.
     * 
     * @param array $snapshot 先前擷取的快照
     * @param array<string> $allowedFields 允許變動的欄位清單
     */
    protected function assertRowChangedOnly(array $snapshot, array $allowedFields): void
    {
        $table = $snapshot['__table'];
        $id = $snapshot['__id'];
        $oldData = $snapshot['data'];

        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $newData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$newData) {
            $this->fail("資料列已不存在：資料表 [{$table}] ID [{$id}]");
        }

        $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));
        $diffs = [];

        foreach ($allKeys as $key) {
            $oldValue = $oldData[$key] ?? null;
            $newValue = $newData[$key] ?? null;

            if ($oldValue !== $newValue) {
                if (!in_array($key, $allowedFields)) {
                    $diffs[] = "欄位 [{$key}] 發生預期外的變動：原始值 [" . var_export($oldValue, true) . "] -> 目前值 [" . var_export($newValue, true) . "]";
                }
            }
        }

        if (!empty($diffs)) {
            $this->fail("資料庫狀態比對失敗 (資料表 [{$table}] ID [{$id}])：
" . implode("
", $diffs));
        }

        // 基本斷言確保方法結束
        $this->assertTrue(true);
    }
}
