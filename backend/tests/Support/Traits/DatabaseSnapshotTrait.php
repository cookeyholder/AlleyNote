<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use PDO;
use RuntimeException;

/**
 * 資料庫狀態快照測試功能 Trait.
 */
trait DatabaseSnapshotTrait
{
    /**
     * 擷取指定資料列的目前狀態快照.
     */
    protected function captureRowSnapshot(string $table, int|string $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException("無法擷取資料表 {$table} 中 ID 為 {$id} 的資料列快照");
        }

        return $row;
    }

    /**
     * 斷言資料列除指定欄位外，其餘皆未變動.
     */
    protected function assertRowChangedOnly(array $oldData, array $allowedFields): void
    {
        $newData = $this->captureRowSnapshot($oldData['table_name'] ?? 'posts', $oldData['id']);
        $diffs = [];

        foreach ($oldData as $key => $oldValue) {
            if ($key === 'table_name') continue;
            
            $newValue = $newData[$key] ?? null;
            if ($oldValue !== $newValue && !in_array($key, $allowedFields, true)) {
                $diffs[] = "欄位 [{$key}] 發生預期外的變動";
            }
        }

        if (!empty($diffs)) {
            $this->fail(implode("
", $diffs));
        }
        
        $this->assertTrue(true);
    }
}
