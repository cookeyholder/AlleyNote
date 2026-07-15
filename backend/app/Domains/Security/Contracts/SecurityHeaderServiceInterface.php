<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

interface SecurityHeaderServiceInterface
{
    /**
     * 設定所有安全性 HTTP 標頭.
     */
    public function setSecurityHeaders(): void;

    /**
     * 移除可能洩漏伺服器資訊的標頭.
     */
    public function removeServerSignature(): void;

    /**
     * 產生安全性 HTTP 標頭陣列 (PSR-7 相容).
     *
     * @return array<string, string>
     */
    public function generateHeaders(): array;

    /**
     * 檢查是否啟用伺服器簽章.
     */
    public function isServerSignatureEnabled(): bool;
}
