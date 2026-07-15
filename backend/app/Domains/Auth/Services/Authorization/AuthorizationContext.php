<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 授權上下文 DTO.
 *
 * 封裝授權評估所需的所有輸入參數，作為策略評估的標準輸入格式。
 */
final readonly class AuthorizationContext
{
    /**
     * @param int $userId 使用者 ID
     * @param string|null $userRole 使用者角色名稱
     * @param array<string> $userPermissions 使用者權限清單
     * @param string $resource 請求的資源名稱
     * @param string $action 請求的操作名稱
     * @param ServerRequestInterface $request HTTP 請求物件
     */
    public function __construct(
        public int $userId,
        public ?string $userRole,
        public array $userPermissions,
        public string $resource,
        public string $action,
        public ServerRequestInterface $request,
    ) {}
}
