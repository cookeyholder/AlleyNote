<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Shared\ValueObjects;

use App\Domains\Shared\ValueObjects\IPAddress;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * IPAddress 值物件測試.
 */
class IPAddressTest extends TestCase
{
    public function test_can_create_valid_ipv4(): void
    {
        $ip = new IPAddress('192.168.1.1');

        $this->assertInstanceOf(IPAddress::class, $ip);
        $this->assertEquals('192.168.1.1', $ip->getValue());
    }

    public function test_can_create_valid_ipv6(): void
    {
        $ip = new IPAddress('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        $this->assertInstanceOf(IPAddress::class, $ip);
    }

    public function test_can_create_from_string(): void
    {
        $ip = IPAddress::fromString('10.0.0.1');

        $this->assertInstanceOf(IPAddress::class, $ip);
    }

    public function test_throws_exception_for_empty_ip(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IP 地址不能為空');

        new IPAddress('');
    }

    public function test_throws_exception_for_invalid_ip(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的 IP 地址格式');

        new IPAddress('999.999.999.999');
    }

    public function test_can_detect_ipv4(): void
    {
        $ip = new IPAddress('192.168.1.1');

        $this->assertTrue($ip->isIPv4());
        $this->assertFalse($ip->isIPv6());
        $this->assertEquals('ipv4', $ip->getVersion());
    }

    public function test_can_detect_ipv6(): void
    {
        $ip = new IPAddress('::1');

        $this->assertTrue($ip->isIPv6());
        $this->assertFalse($ip->isIPv4());
        $this->assertEquals('ipv6', $ip->getVersion());
    }

    public function test_can_check_localhost_ipv4(): void
    {
        $localhost = new IPAddress('127.0.0.1');

        $this->assertTrue($localhost->isLocalhost());
    }

    public function test_can_check_localhost_ipv6(): void
    {
        $localhost = new IPAddress('::1');

        $this->assertTrue($localhost->isLocalhost());
    }

    public function test_can_check_private_ip(): void
    {
        $privateIp = new IPAddress('192.168.1.1');

        $this->assertTrue($privateIp->isPrivate());
    }

    public function test_can_check_public_ip(): void
    {
        $publicIp = new IPAddress('8.8.8.8');

        $this->assertFalse($publicIp->isPrivate());
    }

    public function test_can_mask_ipv4(): void
    {
        $ip = new IPAddress('192.168.1.100');

        $masked = $ip->mask();
        $this->assertEquals('192.168.1.xxx', $masked);
    }

    public function test_can_mask_ipv6(): void
    {
        $ip = new IPAddress('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        $masked = $ip->mask();
        $this->assertStringContainsString('xxxx', $masked);
    }

    public function test_can_check_equality(): void
    {
        $ip1 = new IPAddress('192.168.1.1');
        $ip2 = new IPAddress('192.168.1.1');
        $ip3 = new IPAddress('192.168.1.2');

        $this->assertTrue($ip1->equals($ip2));
        $this->assertFalse($ip1->equals($ip3));
    }

    public function test_can_convert_to_string(): void
    {
        $ip = new IPAddress('192.168.1.1');

        $this->assertEquals('192.168.1.1', $ip->toString());
        $this->assertEquals('192.168.1.1', (string) $ip);
    }

    public function test_can_json_serialize(): void
    {
        $ip = new IPAddress('192.168.1.1');

        $this->assertEquals('"192.168.1.1"', json_encode($ip));
    }

    public function test_can_convert_to_array(): void
    {
        $ip = new IPAddress('192.168.1.1');

        $array = $ip->toArray();
        $this->assertEquals('192.168.1.1', $array['ip_address']);
        $this->assertEquals('ipv4', $array['version']);
        $this->assertTrue($array['is_private']);
        $this->assertFalse($array['is_localhost']);
    }
}
