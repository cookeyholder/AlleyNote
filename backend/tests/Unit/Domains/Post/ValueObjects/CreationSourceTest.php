<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\ValueObjects;

use App\Domains\Post\ValueObjects\CreationSource;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * CreationSource 值物件單元測試.
 */
#[CoversClass(CreationSource::class)]
final class CreationSourceTest extends UnitTestCase
{
    #[Test]
    public function test_建構子接受有效來源並正規化為小寫(): void
    {
        $source = new CreationSource('WEB', '  登入首頁  ');

        $this->assertSame('web', $source->getSource());
        $this->assertSame('登入首頁', $source->getDetail());
    }

    #[Test]
    public function test_建構子拒絕空來源(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('建立來源不能為空');

        new CreationSource('   ');
    }

    #[Test]
    public function test_建構子拒絕無效來源(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的建立來源');

        new CreationSource('hacker');
    }

    #[Test]
    public function test_fromString建立實例(): void
    {
        $source = CreationSource::fromString('api', '行動裝置同步');

        $this->assertSame('api', $source->getSource());
        $this->assertSame('行動裝置同步', $source->getDetail());
    }

    #[Test]
    public function test_工廠方法回傳對應來源(): void
    {
        $this->assertTrue(CreationSource::web()->isWeb());
        $this->assertTrue(CreationSource::api()->isApi());
        $this->assertTrue(CreationSource::mobile()->isMobile());
        $this->assertTrue(CreationSource::import('批次匯入')->is('import'));
        $this->assertTrue(CreationSource::unknown()->is('unknown'));
    }

    #[Test]
    public function test_hasDetail檢查詳細資訊(): void
    {
        $withDetail = new CreationSource('web', '公告頁面');
        $withoutDetail = new CreationSource('web');
        $emptyDetail = new CreationSource('web', '   ');

        $this->assertTrue($withDetail->hasDetail());
        $this->assertFalse($withoutDetail->hasDetail());
        $this->assertFalse($emptyDetail->hasDetail());
    }

    #[Test]
    public function test_is支援不分大小寫比對(): void
    {
        $source = new CreationSource('mobile');

        $this->assertTrue($source->is('MOBILE'));
        $this->assertFalse($source->is('web'));
    }

    #[Test]
    public function test_equals比較兩個實例(): void
    {
        $a = new CreationSource('web', 'detail');
        $b = new CreationSource('web', 'detail');
        $c = new CreationSource('web');
        $d = new CreationSource('api', 'detail');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
        $this->assertFalse($a->equals($d));
    }

    #[Test]
    public function test_toString含詳細資訊時使用冒號串接(): void
    {
        $withDetail = new CreationSource('web', 'admin-panel');
        $withoutDetail = new CreationSource('web');

        $this->assertSame('web:admin-panel', $withDetail->toString());
        $this->assertSame('web', (string) $withoutDetail);
    }

    #[Test]
    public function test_jsonSerialize回傳來源與詳細資訊(): void
    {
        $source = new CreationSource('import', '舊系統');

        $this->assertSame(
            ['source' => 'import', 'detail' => '舊系統'],
            $source->jsonSerialize(),
        );
    }

    #[Test]
    public function test_toArray回傳完整資訊(): void
    {
        $source = new CreationSource('cli');

        $this->assertSame(
            [
                'source'     => 'cli',
                'detail'     => '',
                'has_detail' => false,
            ],
            $source->toArray(),
        );
    }
}
