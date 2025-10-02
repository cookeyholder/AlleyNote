<?php

declare(strict_types=1);

namespace App\Domains\Post\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * CreationSource 值物件.
 *
 * 表示文章的建立來源
 */
final readonly class CreationSource implements JsonSerializable, Stringable
{
    private string $source;

    private string $detail;

    private const VALID_SOURCES = [
        'web', 'api', 'mobile', 'import', 'migration', 'admin', 'cli', 'unknown',
    ];

    public function __construct(string $source, string $detail = '')
    {
        $trimmedSource = trim(strtolower($source));

        if (empty($trimmedSource)) {
            throw new InvalidArgumentException('建立來源不能為空');
        }

        if (!in_array($trimmedSource, self::VALID_SOURCES, true)) {
            throw new InvalidArgumentException(
                sprintf('無效的建立來源：%s。有效值：%s', $source, implode(', ', self::VALID_SOURCES)),
            );
        }

        $this->source = $trimmedSource;
        $this->detail = trim($detail);
    }

    /**
     * 從字串建立 CreationSource.
     */
    public static function fromString(string $source, string $detail = ''): self
    {
        return new self($source, $detail);
    }

    /**
     * 建立 Web 來源.
     */
    public static function web(string $detail = ''): self
    {
        return new self('web', $detail);
    }

    /**
     * 建立 API 來源.
     */
    public static function api(string $detail = ''): self
    {
        return new self('api', $detail);
    }

    /**
     * 建立 Mobile 來源.
     */
    public static function mobile(string $detail = ''): self
    {
        return new self('mobile', $detail);
    }

    /**
     * 建立 Import 來源.
     */
    public static function import(string $detail = ''): self
    {
        return new self('import', $detail);
    }

    /**
     * 建立 Unknown 來源.
     */
    public static function unknown(): self
    {
        return new self('unknown');
    }

    /**
     * 取得來源.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * 取得詳細資訊.
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * 檢查是否有詳細資訊.
     */
    public function hasDetail(): bool
    {
        return !empty($this->detail);
    }

    /**
     * 檢查是否為指定來源.
     */
    public function is(string $source): bool
    {
        return $this->source === strtolower($source);
    }

    /**
     * 檢查是否為 Web 來源.
     */
    public function isWeb(): bool
    {
        return $this->is('web');
    }

    /**
     * 檢查是否為 API 來源.
     */
    public function isApi(): bool
    {
        return $this->is('api');
    }

    /**
     * 檢查是否為 Mobile 來源.
     */
    public function isMobile(): bool
    {
        return $this->is('mobile');
    }

    /**
     * 檢查是否與另一個 CreationSource 相等.
     */
    public function equals(CreationSource $other): bool
    {
        return $this->source === $other->source && $this->detail === $other->detail;
    }

    /**
     * 轉換為字串.
     */
    public function toString(): string
    {
        return $this->hasDetail() ? "{$this->source}:{$this->detail}" : $this->source;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * JSON 序列化.
     */
    public function jsonSerialize(): array
    {
        return [
            'source' => $this->source,
            'detail' => $this->detail,
        ];
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'detail' => $this->detail,
            'has_detail' => $this->hasDetail(),
        ];
    }
}
