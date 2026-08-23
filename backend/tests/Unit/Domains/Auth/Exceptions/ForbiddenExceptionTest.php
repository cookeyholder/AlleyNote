<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\ForbiddenException;
use Tests\Support\UnitTestCase;

/**
 * 存取禁止例外單元測試.
 */
final class ForbiddenExceptionTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $default = new ForbiddenException();
        $this->assertSame('權限不足，無法執行此操作', $default->getMessage());
        $this->assertSame(403, $default->getCode());

        $custom = new ForbiddenException('拒絕存取', 4003);
        $this->assertSame('拒絕存取', $custom->getMessage());
        $this->assertSame(4003, $custom->getCode());
    }

    public function testStaticFactories(): void
    {
        $this->assertSame('您沒有足夠的權限執行此操作', ForbiddenException::insufficientPermissions()->getMessage());
        $this->assertSame('只有資源擁有者才能執行此操作', ForbiddenException::notOwner()->getMessage());
        $this->assertSame('此操作需要管理員權限', ForbiddenException::adminRequired()->getMessage());
        $this->assertSame('此操作需要版主或管理員權限', ForbiddenException::moderatorRequired()->getMessage());
        $this->assertSame('CSRF 權杖驗證失敗，請重新整理頁面後再試', ForbiddenException::csrfTokenMismatch()->getMessage());
        $this->assertSame('您的 IP 位址已被封鎖', ForbiddenException::ipBlocked()->getMessage());
        $this->assertSame('操作過於頻繁，請稍後再試', ForbiddenException::rateLimitExceeded()->getMessage());
        $this->assertSame('系統維護中，暫時無法使用此功能', ForbiddenException::maintenanceMode()->getMessage());
        $this->assertSame('此資源已被鎖定，無法進行操作', ForbiddenException::resourceLocked()->getMessage());
        $this->assertSame('此功能目前已停用', ForbiddenException::featureDisabled()->getMessage());
    }
}
