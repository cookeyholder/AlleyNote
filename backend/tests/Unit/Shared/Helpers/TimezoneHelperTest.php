<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Helpers;

use App\Shared\Helpers\TimezoneHelper;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * TimezoneHelper 單元測試.
 *
 * 該類別以靜態狀態快取時區設定，測試需在每個案例前後重設，
 * 並透過暫存 SQLite 檔案驗證資料庫讀取路徑。
 */
final class TimezoneHelperTest extends UnitTestCase
{
    private string $originalDbEnv = ':memory:';

    private ?string $tempDbPath = null;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var mixed $dbEnv */
        $dbEnv = $_ENV['DB_DATABASE'] ?? null;
        $this->originalDbEnv = is_string($dbEnv) ? $dbEnv : ':memory:';
        TimezoneHelper::resetTimezoneCache();
    }

    protected function tearDown(): void
    {
        TimezoneHelper::resetTimezoneCache();
        $_ENV['DB_DATABASE'] = $this->originalDbEnv;
        if ($this->tempDbPath !== null && is_file($this->tempDbPath)) {
            unlink($this->tempDbPath);
            $this->tempDbPath = null;
        }
        parent::tearDown();
    }

    /**
     * 建立含 site_timezone 設定的暫存資料庫並指向它。
     */
    private function useTempDatabaseWithTimezone(string $timezone): void
    {
        $this->tempDbPath = sys_get_temp_dir() . '/alleynote_tz_' . uniqid() . '.sqlite3';
        $pdo = new PDO('sqlite:' . $this->tempDbPath);
        $pdo->exec('CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)');
        $stmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES ('site_timezone', :tz)");
        $stmt->execute(['tz' => $timezone]);
        $_ENV['DB_DATABASE'] = $this->tempDbPath;
        TimezoneHelper::resetTimezoneCache();
    }

    #[Test]
    public function getSiteTimezoneFallsBackWhenDatabaseUnavailable(): void
    {
        // :memory: 資料庫沒有 settings 資料表，應回退到預設值
        $_ENV['DB_DATABASE'] = ':memory:';
        $this->assertSame('Asia/Taipei', TimezoneHelper::getSiteTimezone());
    }

    #[Test]
    public function getSiteTimezoneReadsFromDatabaseAndCachesResult(): void
    {
        $this->useTempDatabaseWithTimezone('Europe/London');
        $this->assertSame('Europe/London', TimezoneHelper::getSiteTimezone());

        // 快取生效：即使移除資料庫檔案仍回傳快取值
        if ($this->tempDbPath !== null && is_file($this->tempDbPath)) {
            unlink($this->tempDbPath);
        }
        $this->assertSame('Europe/London', TimezoneHelper::getSiteTimezone());
        $this->tempDbPath = null;
    }

    #[Test]
    public function resetTimezoneCacheForcesReread(): void
    {
        $this->useTempDatabaseWithTimezone('America/New_York');
        $this->assertSame('America/New_York', TimezoneHelper::getSiteTimezone());

        TimezoneHelper::resetTimezoneCache();
        // 重設後再次讀取仍應取得相同值
        $this->assertSame('America/New_York', TimezoneHelper::getSiteTimezone());
    }

    #[Test]
    public function utcToSiteTimezoneConvertsWithOffset(): void
    {
        $this->assertSame(
            '2025-10-11T12:30:00+08:00',
            TimezoneHelper::utcToSiteTimezone('2025-10-11T04:30:00Z'),
        );

        // 無效時間原樣返回
        $this->assertSame('not-a-date', TimezoneHelper::utcToSiteTimezone('not-a-date'));
    }

    #[Test]
    public function siteTimezoneToUtcConvertsBack(): void
    {
        // 台灣時區 12:30 對應 UTC 04:30
        $this->assertSame(
            '2025-10-11T04:30:00Z',
            TimezoneHelper::siteTimezoneToUtc('2025-10-11 12:30:00'),
        );

        // 完全無效的輸入回退為當前 UTC 時間（格式正確即可）
        $fallback = TimezoneHelper::siteTimezoneToUtc('garbage-input');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $fallback);
    }

    #[Test]
    public function nowHelpersReturnRfc3339Strings(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', TimezoneHelper::nowUtc());

        $siteNow = TimezoneHelper::nowSiteTimezone();
        $this->assertMatchesRegularExpression('/^[+-]\d{2}:\d{2}$/', substr($siteNow, -6));
    }

    #[Test]
    public function isValidRfc3339AcceptsOnlyParsableDates(): void
    {
        $this->assertTrue(TimezoneHelper::isValidRfc3339('2025-10-11T04:30:00Z'));
        $this->assertTrue(TimezoneHelper::isValidRfc3339('2025-10-11'));
        $this->assertFalse(TimezoneHelper::isValidRfc3339('not-a-date'));
    }

    #[Test]
    public function formatForDisplayAppliesSiteTimezone(): void
    {
        $this->assertSame(
            '2025-10-11 12:30:00',
            TimezoneHelper::formatForDisplay('2025-10-11T04:30:00Z'),
        );
        // 自訂格式與無效輸入處理
        $this->assertSame('11/10/2025', TimezoneHelper::formatForDisplay('2025-10-11T04:30:00Z', 'd/m/Y'));
        $this->assertSame('bad', TimezoneHelper::formatForDisplay('bad'));
    }

    #[Test]
    public function getTimezoneOffsetMatchesTaipeiDefault(): void
    {
        $offset = TimezoneHelper::getTimezoneOffset();
        $this->assertMatchesRegularExpression('/^[+-]\d{2}:\d{2}$/', $offset);
        // 預設時區為 Asia/Taipei（UTC+8，無日光節約時間）
        $this->assertSame('+08:00', $offset);
    }

    #[Test]
    public function getAllTimezonesCoversCommonIdentifiers(): void
    {
        $timezones = TimezoneHelper::getAllTimezones();

        $this->assertArrayHasKey('Asia/Taipei', $timezones);
        $taipeiLabel = $timezones['Asia/Taipei'];
        $this->assertIsString($taipeiLabel);
        $this->assertStringContainsString('UTC+08:00', $taipeiLabel);
        $this->assertGreaterThan(300, count($timezones));
    }
}
