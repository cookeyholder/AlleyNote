<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\UnauthorizedException;
use Tests\Support\UnitTestCase;

/**
 * 未授權例外單元測試.
 */
final class UnauthorizedExceptionTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $default = new UnauthorizedException();
        $this->assertSame('未授權存取，請先登入', $default->getMessage());
        $this->assertSame(401, $default->getCode());

        $custom = new UnauthorizedException('自訂未授權訊息', 4001);
        $this->assertSame('自訂未授權訊息', $custom->getMessage());
        $this->assertSame(4001, $custom->getCode());
    }

    public function testStaticFactories(): void
    {
        $this->assertSame('請先登入後再進行此操作', UnauthorizedException::notLoggedIn()->getMessage());
        $this->assertSame('帳號或密碼錯誤', UnauthorizedException::invalidCredentials()->getMessage());
        $this->assertSame('登入權杖已過期，請重新登入', UnauthorizedException::tokenExpired()->getMessage());
        $this->assertSame('無效的登入權杖', UnauthorizedException::tokenInvalid()->getMessage());
        $this->assertSame('登入會話已過期，請重新登入', UnauthorizedException::sessionExpired()->getMessage());
        $this->assertSame('此帳號已被停用，請聯繫管理員', UnauthorizedException::accountDisabled()->getMessage());
        $this->assertSame('此帳號因多次登入失敗已被暫時鎖定', UnauthorizedException::accountLocked()->getMessage());
        $this->assertSame('請先驗證您的電子郵件地址', UnauthorizedException::emailNotVerified()->getMessage());
    }
}
