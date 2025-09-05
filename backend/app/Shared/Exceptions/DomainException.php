<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Exception;
use Throwable;

/**
 * 領域例外基礎類別
 * 
 * 所有領域層的例外都應該繼承此類別，
 * 提供領域層統一的例外處理機制。
 */
abstract class DomainException extends Exception
{
    /**
     * 建立領域例外
     * 
     * @param string $message 例外訊息
     * @param int $code 例外代碼
     * @param Throwable|null $previous 前一個例外
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 取得領域名稱
     * 
     * 由子類別實作，回傳該例外所屬的領域名稱
     * 
     * @return string 領域名稱
     */
    abstract public function getDomainName(): string;

    /**
     * 取得格式化的例外訊息
     * 
     * @return string 格式化後的例外訊息
     */
    public function getFormattedMessage(): string
    {
        return sprintf(
            '[%s Domain] %s',
            $this->getDomainName(),
            $this->getMessage()
        );
    }

    /**
     * 轉換為陣列格式
     * 
     * @return array{domain: string, message: string, code: int, file: string, line: int}
     */
    public function toArray(): array
    {
        return [
            'domain' => $this->getDomainName(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
