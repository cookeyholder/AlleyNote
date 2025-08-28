<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Exceptions;

use Exception;

/**
 * JWT 基礎例外類別.
 *
 * 所有 JWT 相關例外的基礎類別，提供統一的錯誤處理介面。
 * 支援錯誤碼、多語言錯誤訊息和額外的上下文資訊。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
abstract class JwtException extends Exception
{
    /**
     * 錯誤上下文資訊.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'jwt_error';

    /**
     * 建構 JWT 例外.
     *
     * @param string $message 錯誤訊息
     * @param int $code 錯誤碼
     * @param Exception|null $previous 前一個例外
     * @param array<string, mixed> $context 錯誤上下文
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * 取得錯誤上下文資訊.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 設定錯誤上下文資訊.
     *
     * @param array<string, mixed> $context 上下文資訊
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * 加入上下文資訊.
     *
     * @param string $key 鍵名
     * @param mixed $value 值
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    /**
     * 取得錯誤類型.
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * 取得錯誤詳細資訊（用於 API 回應）.
     *
     * @return array<string, mixed>
     */
    public function getErrorDetails(): array
    {
        return [
            'error_type' => $this->getErrorType(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    /**
     * 取得用戶友好的錯誤訊息.
     *
     * 子類別應該覆寫此方法提供適當的用戶錯誤訊息
     */
    public function getUserFriendlyMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * 檢查是否為特定類型的錯誤.
     *
     * @param string $type 錯誤類型
     */
    public function isType(string $type): bool
    {
        return $this->errorType === $type;
    }

    /**
     * 轉換為陣列格式（用於日誌記錄）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'exception' => static::class,
            'error_type' => $this->getErrorType(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * 轉換為字串.
     */
    public function __toString(): string
    {
        $context = empty($this->context) ? '' : ' Context: ' . json_encode($this->context);

        return sprintf(
            '[%s] %s (Code: %d)%s in %s:%d',
            $this->getErrorType(),
            $this->getMessage(),
            $this->getCode(),
            $context,
            $this->getFile(),
            $this->getLine(),
        );
    }
}
