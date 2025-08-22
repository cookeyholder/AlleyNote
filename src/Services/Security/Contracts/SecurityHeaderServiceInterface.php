<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface SecurityHeaderServiceInterface
{
    /**
     * 設定所有安全性 HTTP 標頭
     */
    public function setSecurityHeaders(): void;

    /**
     * 移除可能洩漏伺服器資訊的標頭
     */
    public function removeServerSignature(): void;
}
