<?php

declare(strict_types=1);

namespace App\Domains\Setting\Repositories;

use PDO;

/**
 * 設定 Repository.
 */
class SettingRepository
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    /**
     * 取得所有設定.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $sql = 'SELECT * FROM settings ORDER BY key ASC';
        $stmt = $this->db->query($sql);
        if ($stmt === false) {
            return [];
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = is_string($row['type'] ?? null) ? $row['type'] : 'string';
            $valueStr = is_string($row['value'] ?? null) ? $row['value'] : null;
            $value = $this->castValue($valueStr, $type);

            $result[] = [
                'id' => isset($row['id']) && (is_int($row['id']) || is_string($row['id'])) ? (int) $row['id'] : 0,
                'key' => is_string($row['key'] ?? null) ? $row['key'] : '',
                'value' => $value,
                'type' => $type,
                'description' => is_string($row['description'] ?? null) ? $row['description'] : null,
                'created_at' => is_string($row['created_at'] ?? null) ? $row['created_at'] : '',
                'updated_at' => is_string($row['updated_at'] ?? null) ? $row['updated_at'] : '',
            ];
        }

        return $result;
    }

    /**
     * 根據 key 取得設定.
     *
     * @return array<string, mixed>|null
     */
    public function findByKey(string $key): ?array
    {
        $sql = 'SELECT * FROM settings WHERE key = :key';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        $type = is_string($row['type'] ?? null) ? $row['type'] : 'string';
        $valueStr = is_string($row['value'] ?? null) ? $row['value'] : null;
        $value = $this->castValue($valueStr, $type);

        return [
            'id' => isset($row['id']) && (is_int($row['id']) || is_string($row['id'])) ? (int) $row['id'] : 0,
            'key' => is_string($row['key'] ?? null) ? $row['key'] : '',
            'value' => $value,
            'type' => $type,
            'description' => is_string($row['description'] ?? null) ? $row['description'] : null,
            'created_at' => is_string($row['created_at'] ?? null) ? $row['created_at'] : '',
            'updated_at' => is_string($row['updated_at'] ?? null) ? $row['updated_at'] : '',
        ];
    }

    /**
     * 更新設定值.
     *
     * @return array<string, mixed>|null
     */
    public function updateValue(string $key, mixed $value, string $type): ?array
    {
        $storedValue = $this->prepareValue($value, $type);

        $sql = 'UPDATE settings SET value = :value, updated_at = CURRENT_TIMESTAMP WHERE key = :key';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $storedValue, 'key' => $key]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return $this->findByKey($key);
    }

    /**
     * 建立設定.
     *
     * @return array<string, mixed>
     */
    public function create(string $key, mixed $value, string $type, ?string $description = null): array
    {
        $storedValue = $this->prepareValue($value, $type);

        $sql = 'INSERT INTO settings (key, value, type, description, created_at, updated_at) 
                VALUES (:key, :value, :type, :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'key' => $key,
            'value' => $storedValue,
            'type' => $type,
            'description' => $description,
        ]);

        $result = $this->findByKey($key);

        return $result ?? [];
    }

    /**
     * 刪除設定.
     */
    public function delete(string $key): bool
    {
        $sql = 'DELETE FROM settings WHERE key = :key';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['key' => $key]);

        return $stmt->rowCount() > 0;
    }

    /**
     * 根據類型轉換值.
     */
    private function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array', 'json' => json_decode($value ?? '[]', true),
            default => $value,
        };
    }

    /**
     * 準備要儲存的值.
     */
    private function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => is_int($value) || is_string($value) || is_float($value) ? (string) ((int) $value) : '0',
            'float' => is_int($value) || is_string($value) || is_float($value) ? (string) ((float) $value) : '0.0',
            'array', 'json' => json_encode($value) ?: '[]',
            default => is_string($value) ? $value : (is_int($value) || is_float($value) ? (string) $value : ''),
        };
    }
}
