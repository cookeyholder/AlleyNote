<?php

declare(strict_types=1);

namespace Tests\Integration\Settings;

use App\Domains\Setting\Repositories\SettingRepository;
use App\Domains\Setting\Services\SettingManagementService;
use App\Shared\Helpers\TimezoneHelper;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

#[Group('integration')]
#[Group('settings')]
final class SettingTimezoneIntegrationTest extends IntegrationTestCase
{
    private string $tempDbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->exec('CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT NOT NULL UNIQUE,
            value TEXT,
            type TEXT NOT NULL DEFAULT "string",
            description TEXT,
            created_at TEXT,
            updated_at TEXT
        )');

        $this->tempDbPath = sys_get_temp_dir() . '/alleynote_tz_' . bin2hex(random_bytes(8)) . '.sqlite3';
        $tempPdo = new PDO('sqlite:' . $this->tempDbPath);
        $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $tempPdo->exec('CREATE TABLE settings (id INTEGER PRIMARY KEY AUTOINCREMENT, key TEXT NOT NULL UNIQUE, value TEXT, type TEXT NOT NULL DEFAULT "string", description TEXT, created_at TEXT, updated_at TEXT)');
        $tempPdo->exec("INSERT INTO settings (key, value, type, created_at, updated_at) VALUES ('site_timezone', 'Asia/Tokyo', 'string', datetime('now'), datetime('now'))");

        $_ENV['DB_DATABASE'] = $this->tempDbPath;
        TimezoneHelper::resetTimezoneCache();
    }

    protected function tearDown(): void
    {
        TimezoneHelper::resetTimezoneCache();
        if (isset($this->tempDbPath) && is_file($this->tempDbPath)) {
            @unlink($this->tempDbPath);
        }

        parent::tearDown();
    }

    public function testSettingManagementServicePersistsAndReadsTypedValues(): void
    {
        $repository = new SettingRepository($this->db);
        $service = new SettingManagementService($repository);

        $service->upsertSetting('site_name', 'AlleyNote', 'string', '網站名稱');
        $service->upsertSetting('max_attachments_per_post', 12, 'integer', '附件數上限');
        $service->upsertSetting('allow_comments', true, 'boolean', '是否開放留言');

        $service->updateSetting('site_name', 'AlleyNote 2');

        $siteName = $service->getSetting('site_name', true);
        $maxAttachments = $service->getSetting('max_attachments_per_post', true);
        $allowComments = $service->getSetting('allow_comments', true);

        $this->assertSame('AlleyNote 2', $siteName['value']);
        $this->assertSame(12, $maxAttachments['value']);
        $this->assertTrue($allowComments['value']);
    }

    public function testTimezoneHelperReadsSiteTimezoneAndConvertsRoundTrip(): void
    {
        $this->assertSame('Asia/Tokyo', TimezoneHelper::getSiteTimezone());

        $siteTime = '2025-10-11 15:30:00';
        $utc = TimezoneHelper::siteTimezoneToUtc($siteTime);

        $expectedUtc = new DateTimeImmutable($siteTime, new DateTimeZone('Asia/Tokyo'))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');

        $this->assertSame($expectedUtc, $utc);

        $convertedBack = TimezoneHelper::utcToSiteTimezone($utc);
        $this->assertStringContainsString('+09:00', $convertedBack);
        $this->assertStringStartsWith('2025-10-11T15:30:00', $convertedBack);
    }
}
