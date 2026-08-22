<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\RoleChangedException;
use Tests\Support\UnitTestCase;

/**
 * 角色變更例外單元測試.
 */
final class RoleChangedExceptionTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $default = new RoleChangedException();
        $this->assertSame('角色權限已變更，請重新登入', $default->getMessage());
        $this->assertSame(RoleChangedException::ERROR_CODE, $default->getCode());

        $custom = new RoleChangedException('自訂角色變更訊息');
        $this->assertSame('自訂角色變更訊息', $custom->getMessage());
        $this->assertSame(RoleChangedException::ERROR_CODE, $custom->getCode());
    }
}
