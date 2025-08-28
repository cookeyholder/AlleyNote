<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\ValueObjects;

use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Device Info Value Object 單元測試.
 */
final class DeviceInfoTest extends TestCase
{
    private string $validDeviceId;

    private string $validDeviceName;

    private string $validUserAgent;

    private string $validIpAddress;

    protected function setUp(): void
    {
        $this->validDeviceId = 'dev_1234567890abcdef';
        $this->validDeviceName = 'Windows Desktop (Chrome)';
        $this->validUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $this->validIpAddress = '192.168.1.100';
    }

    public function testConstructorWithValidData(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
            browser: 'Chrome',
            browserVersion: '120.0.0.0',
            osVersion: '10.0',
            isMobile: false,
            isTablet: false,
            isDesktop: true,
        );

        $this->assertSame($this->validDeviceId, $deviceInfo->getDeviceId());
        $this->assertSame($this->validDeviceName, $deviceInfo->getDeviceName());
        $this->assertSame($this->validUserAgent, $deviceInfo->getUserAgent());
        $this->assertSame($this->validIpAddress, $deviceInfo->getIpAddress());
        $this->assertSame('Windows', $deviceInfo->getPlatform());
        $this->assertSame('Chrome', $deviceInfo->getBrowser());
        $this->assertSame('120.0.0.0', $deviceInfo->getBrowserVersion());
        $this->assertSame('10.0', $deviceInfo->getOsVersion());
        $this->assertFalse($deviceInfo->isMobile());
        $this->assertFalse($deviceInfo->isTablet());
        $this->assertTrue($deviceInfo->isDesktop());
    }

    public function testConstructorWithMinimalData(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );

        $this->assertNull($deviceInfo->getPlatform());
        $this->assertNull($deviceInfo->getBrowser());
        $this->assertNull($deviceInfo->getBrowserVersion());
        $this->assertNull($deviceInfo->getOsVersion());
        $this->assertFalse($deviceInfo->isMobile());
        $this->assertFalse($deviceInfo->isTablet());
        $this->assertTrue($deviceInfo->isDesktop()); // 預設為桌面
    }

    public function testFromUserAgentWithWindowsChrome(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $ipAddress = '192.168.1.100';

        $deviceInfo = DeviceInfo::fromUserAgent($userAgent, $ipAddress);

        $this->assertSame('Windows', $deviceInfo->getPlatform());
        $this->assertSame('Chrome', $deviceInfo->getBrowser());
        $this->assertSame('120.0.0.0', $deviceInfo->getBrowserVersion());
        $this->assertSame('10.0', $deviceInfo->getOsVersion());
        $this->assertFalse($deviceInfo->isMobile());
        $this->assertFalse($deviceInfo->isTablet());
        $this->assertTrue($deviceInfo->isDesktop());
        $this->assertStringContainsString('Windows Desktop (Chrome)', $deviceInfo->getDeviceName());
    }

    public function testFromUserAgentWithMacSafari(): void
    {
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15';
        $ipAddress = '10.0.0.1';

        $deviceInfo = DeviceInfo::fromUserAgent($userAgent, $ipAddress);

        $this->assertSame('macOS', $deviceInfo->getPlatform());
        $this->assertSame('Safari', $deviceInfo->getBrowser());
        $this->assertSame('10.15.7', $deviceInfo->getOsVersion());
        $this->assertFalse($deviceInfo->isMobile());
        $this->assertTrue($deviceInfo->isDesktop());
    }

    public function testFromUserAgentWithAndroidMobile(): void
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 13; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
        $ipAddress = '203.0.113.1';

        $deviceInfo = DeviceInfo::fromUserAgent($userAgent, $ipAddress);

        $this->assertSame('Android', $deviceInfo->getPlatform());
        $this->assertSame('Chrome', $deviceInfo->getBrowser());
        $this->assertSame('13', $deviceInfo->getOsVersion());
        $this->assertTrue($deviceInfo->isMobile());
        $this->assertFalse($deviceInfo->isTablet());
        $this->assertFalse($deviceInfo->isDesktop());
    }

    public function testFromUserAgentWithiPad(): void
    {
        $userAgent = 'Mozilla/5.0 (iPad; CPU OS 16_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Mobile/15E148 Safari/604.1';
        $ipAddress = '198.51.100.1';

        $deviceInfo = DeviceInfo::fromUserAgent($userAgent, $ipAddress);

        $this->assertSame('iOS', $deviceInfo->getPlatform());
        $this->assertSame('Safari', $deviceInfo->getBrowser());
        $this->assertFalse($deviceInfo->isMobile());
        $this->assertTrue($deviceInfo->isTablet());
        $this->assertFalse($deviceInfo->isDesktop());
        $this->assertStringContainsString('iOS Tablet (Safari)', $deviceInfo->getDeviceName());
    }

    public function testFromUserAgentWithFirefox(): void
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0';
        $ipAddress = '172.16.0.1';

        $deviceInfo = DeviceInfo::fromUserAgent($userAgent, $ipAddress);

        $this->assertSame('Linux', $deviceInfo->getPlatform());
        $this->assertSame('Firefox', $deviceInfo->getBrowser());
        $this->assertSame('115.0', $deviceInfo->getBrowserVersion());
        $this->assertFalse($deviceInfo->isMobile());
        $this->assertTrue($deviceInfo->isDesktop());
    }

    public function testFromUserAgentWithCustomDeviceName(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $ipAddress = '192.168.1.1';
        $customName = 'My Work Computer';

        $deviceInfo = DeviceInfo::fromUserAgent($userAgent, $ipAddress, $customName);

        $this->assertSame($customName, $deviceInfo->getDeviceName());
    }

    public function testFromArray(): void
    {
        $data = [
            'device_id' => $this->validDeviceId,
            'device_name' => $this->validDeviceName,
            'user_agent' => $this->validUserAgent,
            'ip_address' => $this->validIpAddress,
            'platform' => 'Windows',
            'browser' => 'Chrome',
            'browser_version' => '120.0.0.0',
            'os_version' => '10.0',
            'is_mobile' => false,
            'is_tablet' => false,
            'is_desktop' => true,
        ];

        $deviceInfo = DeviceInfo::fromArray($data);

        $this->assertSame($this->validDeviceId, $deviceInfo->getDeviceId());
        $this->assertSame($this->validDeviceName, $deviceInfo->getDeviceName());
        $this->assertSame('Windows', $deviceInfo->getPlatform());
        $this->assertSame('Chrome', $deviceInfo->getBrowser());
        $this->assertTrue($deviceInfo->isDesktop());
    }

    public function testGetDeviceType(): void
    {
        $mobileDevice = new DeviceInfo(
            deviceId: 'mobile_device',
            deviceName: 'Mobile',
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            isMobile: true,
            isTablet: false,
            isDesktop: false,
        );

        $tabletDevice = new DeviceInfo(
            deviceId: 'tablet_device',
            deviceName: 'Tablet',
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            isMobile: false,
            isTablet: true,
            isDesktop: false,
        );

        $desktopDevice = new DeviceInfo(
            deviceId: 'desktop_device',
            deviceName: 'Desktop',
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            isMobile: false,
            isTablet: false,
            isDesktop: true,
        );

        $this->assertSame('mobile', $mobileDevice->getDeviceType());
        $this->assertSame('tablet', $tabletDevice->getDeviceType());
        $this->assertSame('desktop', $desktopDevice->getDeviceType());
    }

    public function testGetFingerprint(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
            browser: 'Chrome',
            isMobile: false,
            isTablet: false,
            isDesktop: true,
        );

        $fingerprint = $deviceInfo->getFingerprint();

        $this->assertStringContainsString('Windows', $fingerprint);
        $this->assertStringContainsString('Chrome', $fingerprint);
        $this->assertStringContainsString('desktop', $fingerprint);
        $this->assertIsString($fingerprint);
    }

    public function testGetFullBrowserInfo(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            browser: 'Chrome',
            browserVersion: '120.0.0.0',
        );

        $this->assertSame('Chrome 120.0.0.0', $deviceInfo->getFullBrowserInfo());
    }

    public function testGetFullBrowserInfoWithoutBrowser(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );

        $this->assertSame('Unknown Browser', $deviceInfo->getFullBrowserInfo());
    }

    public function testGetFullPlatformInfo(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
            osVersion: '10.0',
        );

        $this->assertSame('Windows 10.0', $deviceInfo->getFullPlatformInfo());
    }

    public function testGetFullPlatformInfoWithoutPlatform(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );

        $this->assertSame('Unknown Platform', $deviceInfo->getFullPlatformInfo());
    }

    public function testMatches(): void
    {
        $device1 = new DeviceInfo(
            deviceId: 'device1',
            deviceName: 'Device 1',
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
            browser: 'Chrome',
            isDesktop: true,
        );

        $device2 = new DeviceInfo(
            deviceId: 'device2',
            deviceName: 'Device 2',
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
            browser: 'Chrome',
            isDesktop: true,
        );

        $device3 = new DeviceInfo(
            deviceId: 'device3',
            deviceName: 'Device 3',
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Linux',
            browser: 'Firefox',
            isDesktop: true,
        );

        $this->assertTrue($device1->matches($device2)); // 相同指紋
        $this->assertFalse($device1->matches($device3)); // 不同指紋
    }

    public function testToArray(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
            browser: 'Chrome',
            browserVersion: '120.0.0.0',
            osVersion: '10.0',
            isMobile: false,
            isTablet: false,
            isDesktop: true,
        );

        $array = $deviceInfo->toArray();

        $this->assertSame($this->validDeviceId, $array['device_id']);
        $this->assertSame($this->validDeviceName, $array['device_name']);
        $this->assertSame($this->validUserAgent, $array['user_agent']);
        $this->assertSame($this->validIpAddress, $array['ip_address']);
        $this->assertSame('Windows', $array['platform']);
        $this->assertSame('Chrome', $array['browser']);
        $this->assertSame('120.0.0.0', $array['browser_version']);
        $this->assertSame('10.0', $array['os_version']);
        $this->assertFalse($array['is_mobile']);
        $this->assertFalse($array['is_tablet']);
        $this->assertTrue($array['is_desktop']);
        $this->assertSame('desktop', $array['device_type']);
        $this->assertIsString($array['fingerprint']);
    }

    public function testToSummary(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: '192.168.1.100',
            platform: 'Windows',
            browser: 'Chrome',
            browserVersion: '120.0.0.0',
            osVersion: '10.0',
        );

        $summary = $deviceInfo->toSummary();

        $this->assertSame($this->validDeviceId, $summary['device_id']);
        $this->assertSame($this->validDeviceName, $summary['device_name']);
        $this->assertSame('Windows 10.0', $summary['platform']);
        $this->assertSame('Chrome 120.0.0.0', $summary['browser']);
        $this->assertSame('desktop', $summary['device_type']);
        $this->assertSame('192.168.1.xxx', $summary['ip_address_masked']);
        $this->assertArrayNotHasKey('user_agent', $summary);
    }

    public function testJsonSerialize(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );

        $this->assertEquals($deviceInfo->toArray(), $deviceInfo->jsonSerialize());
    }

    public function testEquals(): void
    {
        $device1 = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
        );

        $device2 = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
        );

        $device3 = new DeviceInfo(
            deviceId: 'different_id',
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'Windows',
        );

        $this->assertTrue($device1->equals($device2));
        $this->assertFalse($device1->equals($device3));
    }

    public function testToString(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: '192.168.1.100',
            platform: 'Windows',
            browser: 'Chrome',
        );

        $string = $deviceInfo->toString();

        $this->assertStringContainsString('DeviceInfo(', $string);
        $this->assertStringContainsString($this->validDeviceId, $string);
        $this->assertStringContainsString($this->validDeviceName, $string);
        $this->assertStringContainsString('desktop', $string);
        $this->assertStringContainsString('Windows', $string);
        $this->assertStringContainsString('Chrome', $string);
        $this->assertStringContainsString('192.168.1.xxx', $string);

        $this->assertSame($string, (string) $deviceInfo);
    }

    public function testMaskIpAddressIPv4(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: '192.168.1.100',
        );

        $summary = $deviceInfo->toSummary();
        $this->assertSame('192.168.1.xxx', $summary['ip_address_masked']);
    }

    public function testMaskIpAddressIPv6(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: '2001:db8::1',
        );

        $summary = $deviceInfo->toSummary();
        $this->assertSame('2001:db8::xxxx', $summary['ip_address_masked']);
    }

    public function testConstructorWithEmptyDeviceId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device ID cannot be empty');

        new DeviceInfo(
            deviceId: '',
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );
    }

    public function testConstructorWithTooLongDeviceId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device ID cannot exceed 255 characters');

        new DeviceInfo(
            deviceId: str_repeat('a', 256),
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );
    }

    public function testConstructorWithInvalidDeviceIdCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device ID can only contain letters, numbers, underscores and hyphens');

        new DeviceInfo(
            deviceId: 'invalid@device#id',
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );
    }

    public function testConstructorWithEmptyDeviceName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device name cannot be empty');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: '',
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );
    }

    public function testConstructorWithTooLongDeviceName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device name cannot exceed 255 characters');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: str_repeat('a', 256),
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
        );
    }

    public function testConstructorWithEmptyUserAgent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User agent cannot be empty');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: '',
            ipAddress: $this->validIpAddress,
        );
    }

    public function testConstructorWithTooLongUserAgent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User agent cannot exceed 1000 characters');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: str_repeat('a', 1001),
            ipAddress: $this->validIpAddress,
        );
    }

    public function testConstructorWithEmptyIpAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IP address cannot be empty');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: '',
        );
    }

    public function testConstructorWithInvalidIpAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IP address format is invalid');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: 'invalid-ip',
        );
    }

    public function testConstructorWithInvalidPlatform(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Platform must be one of: Windows, macOS, Linux, Android, iOS, Unix, Other');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            platform: 'InvalidPlatform',
        );
    }

    public function testConstructorWithInvalidBrowser(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Browser must be one of: Chrome, Firefox, Safari, Edge, Opera, Internet Explorer, Chromium, Other');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            browser: 'InvalidBrowser',
        );
    }

    public function testConstructorWithMultipleDeviceTypesTrue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one device type (mobile, tablet, desktop) must be true');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            isMobile: true,
            isTablet: true,
            isDesktop: false,
        );
    }

    public function testConstructorWithNoDeviceTypeTrue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exactly one device type (mobile, tablet, desktop) must be true');

        new DeviceInfo(
            deviceId: $this->validDeviceId,
            deviceName: $this->validDeviceName,
            userAgent: $this->validUserAgent,
            ipAddress: $this->validIpAddress,
            isMobile: false,
            isTablet: false,
            isDesktop: false,
        );
    }

    public function testFromArrayWithMissingRequiredField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: device_name');

        DeviceInfo::fromArray([
            'device_id' => $this->validDeviceId,
            'user_agent' => $this->validUserAgent,
            'ip_address' => $this->validIpAddress,
        ]);
    }
}
