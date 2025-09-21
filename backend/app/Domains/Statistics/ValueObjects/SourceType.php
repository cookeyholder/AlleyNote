<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * 來源類型值物件.
 *
 * 表示文章的建立來源，用於分析文章的來源分布。
 * 此值物件是 immutable 的，一旦建立就不能修改。
 *
 * @psalm-immutable
 */
final readonly class SourceType implements JsonSerializable
{
    private const VALID_CODES = ['web', 'api', 'import', 'migration'];

    /**
     * @param string $code 類型代碼
     * @param string $name 類型名稱
     * @param string $description 類型描述（可選）
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $description = '',
    ) {
        $this->validate();
    }

    /**
     * 從陣列建立來源類型物件.
     *
     * @param array{code: string, name: string, description?: string} $data
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['code'], $data['name'])) {
            throw new InvalidArgumentException('Missing required fields: code, name');
        }

        return new self(
            code: $data['code'],
            name: $data['name'],
            description: $data['description'] ?? '',
        );
    }

    /**
     * 建立網頁來源類型.
     */
    public static function createWeb(): self
    {
        return new self('web', '網頁介面', '透過網頁界面建立的文章');
    }

    /**
     * 建立 API 來源類型.
     */
    public static function createApi(): self
    {
        return new self('api', 'API 介面', '透過 REST API 建立的文章');
    }

    /**
     * 建立匯入來源類型.
     */
    public static function createImport(): self
    {
        return new self('import', '資料匯入', '透過批量匯入功能建立的文章');
    }

    /**
     * 建立遷移來源類型.
     */
    public static function createMigration(): self
    {
        return new self('migration', '資料遷移', '系統遷移過程中建立的文章');
    }

    /**
     * 從代碼建立來源類型.
     *
     * @throws InvalidArgumentException
     */
    public static function fromCode(string $code): self
    {
        return match (strtolower($code)) {
            'web' => self::createWeb(),
            'api' => self::createApi(),
            'import' => self::createImport(),
            'migration' => self::createMigration(),
            default => throw new InvalidArgumentException("Invalid source code: {$code}"),
        };
    }

    /**
     * 檢查是否為網頁來源.
     */
    public function isWeb(): bool
    {
        return $this->code === 'web';
    }

    /**
     * 檢查是否為 API 來源.
     */
    public function isApi(): bool
    {
        return $this->code === 'api';
    }

    /**
     * 檢查是否為匯入來源.
     */
    public function isImport(): bool
    {
        return $this->code === 'import';
    }

    /**
     * 檢查是否為遷移來源.
     */
    public function isMigration(): bool
    {
        return $this->code === 'migration';
    }

    /**
     * 取得來源類型的優先級（用於排序）.
     */
    public function getPriority(): int
    {
        return match ($this->code) {
            'web' => 1,
            'api' => 2,
            'import' => 3,
            'migration' => 4,
            default => 999,
        };
    }

    /**
     * 轉換為陣列.
     *
     * @return array{code: string, name: string, description: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    /**
     * JSON 序列化.
     *
     * @return array{code: string, name: string, description: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查兩個來源類型是否相等.
     */
    public function equals(SourceType $other): bool
    {
        return $this->code === $other->code
            && $this->name === $other->name
            && $this->description === $other->description;
    }

    /**
     * 轉換為字串表示.
     */
    public function __toString(): string
    {
        return $this->description ? "{$this->name} ({$this->description})" : $this->name;
    }

    /**
     * 取得所有有效的來源代碼.
     *
     * @return array<string>
     */
    public static function getValidCodes(): array
    {
        return self::VALID_CODES;
    }

    /**
     * 取得所有預定義的來源類型.
     *
     * @return array<SourceType>
     */
    public static function getAllTypes(): array
    {
        return [
            self::createWeb(),
            self::createApi(),
            self::createImport(),
            self::createMigration(),
        ];
    }

    /**
     * 驗證來源類型的有效性.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        // 檢查代碼必須是預定義的枚舉值
        if (!in_array(strtolower($this->code), self::VALID_CODES, true)) {
            throw new InvalidArgumentException(
                "Invalid source code: {$this->code}. Valid codes: " . implode(', ', self::VALID_CODES),
            );
        }

        // 檢查名稱不能為空
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Source name cannot be empty');
        }

        // 檢查描述不能是無意義字串
        if (in_array($this->description, ['N/A', 'null', 'undefined'], true)) {
            throw new InvalidArgumentException('Description cannot be meaningless string');
        }
    }
}
