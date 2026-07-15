<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Helpers;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use App\Shared\Helpers\NetworkHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\SecureDDDTestCase;

#[CoversClass(NetworkHelper::class)]
class NetworkHelperTest extends SecureDDDTestCase
{
    private function createNetworkRequest(string $method = 'GET', string $path = '/', array $headers = [], array $serverParams = []): ServerRequest
    {
        $uri = new Uri()->withPath($path);

        return new ServerRequest($method, $uri, $headers, new Stream(''), '1.1', $serverParams);
    }

    // ─── getClientIp (既有方法) ─────────────────────────────────

    #[Test]
    public function returnsRemoteAddrWhenNoProxyHeaders(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], ['REMOTE_ADDR' => '1.2.3.4']);

        $ip = NetworkHelper::getClientIp($request);

        $this->assertEquals('1.2.3.4', $ip);
    }

    #[Test]
    public function returnsFirstIpFromXForwardedForWhenTrusted(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['5.6.7.8, 10.0.0.1']], ['REMOTE_ADDR' => '10.0.0.1']);

        $ip = NetworkHelper::getClientIp($request, ['10.0.0.1']);

        $this->assertEquals('5.6.7.8', $ip);
    }

    #[Test]
    public function ignoresXForwardedForWhenNotTrusted(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['5.6.7.8']], ['REMOTE_ADDR' => '1.2.3.4']);

        $ip = NetworkHelper::getClientIp($request, ['10.0.0.1']);

        $this->assertEquals('1.2.3.4', $ip);
    }

    #[Test]
    public function ignoresProxyHeadersWhenTrustedProxyListIsEmpty(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['5.6.7.8']], ['REMOTE_ADDR' => '10.0.0.1']);

        $ip = NetworkHelper::getClientIp($request);

        $this->assertEquals('10.0.0.1', $ip);
    }

    #[Test]
    public function returnsXRealIpIfPresentAndTrusted(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Real-IP' => ['9.10.11.12']], ['REMOTE_ADDR' => '127.0.0.1']);

        $ip = NetworkHelper::getClientIp($request, ['127.0.0.1']);

        $this->assertEquals('9.10.11.12', $ip);
    }

    #[Test]
    public function returnsCfConnectingIpIfPresentAndTrusted(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['CF-Connecting-IP' => ['13.14.15.16']], ['REMOTE_ADDR' => '127.0.0.1']);

        $ip = NetworkHelper::getClientIp($request, ['127.0.0.1']);

        $this->assertEquals('13.14.15.16', $ip);
    }

    #[Test]
    public function validatesIpFormat(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['invalid-ip, 1.2.3.4']], ['REMOTE_ADDR' => '127.0.0.1']);

        $ip = NetworkHelper::getClientIp($request, ['127.0.0.1']);

        $this->assertEquals('127.0.0.1', $ip);
    }

    #[Test]
    public function supportsCidrRangesForTrustedProxies(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['5.6.7.8']], ['REMOTE_ADDR' => '192.168.1.50']);

        $ip = NetworkHelper::getClientIp($request, ['192.168.1.0/24']);

        $this->assertEquals('5.6.7.8', $ip);
    }

    #[Test]
    public function defaultsToLocalhostWhenNoServerParams(): void
    {
        $request = $this->createNetworkRequest('GET', '/');

        $ip = NetworkHelper::getClientIp($request);

        $this->assertEquals('127.0.0.1', $ip);
    }

    // ─── getClientIpFromServerParams ────────────────────────────

    #[Test]
    public function fromServerParamsReturnsFirstMatchingHeaderByPriority(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'HTTP_X_FORWARDED_FOR'  => '10.0.0.1',
        ]);

        $ip = NetworkHelper::getClientIpFromServerParams(
            $request,
            ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'],
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );

        $this->assertEquals('203.0.113.1', $ip);
    }

    #[Test]
    public function fromServerParamsSkipsPrivateIpsWithFilterFlag(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'HTTP_CF_CONNECTING_IP' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR'  => '203.0.113.5',
        ]);

        $ip = NetworkHelper::getClientIpFromServerParams(
            $request,
            ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'],
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );

        $this->assertEquals('203.0.113.5', $ip);
    }

    #[Test]
    public function fromServerParamsReturnsFallbackWhenNoValidIp(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
        ]);

        $ip = NetworkHelper::getClientIpFromServerParams(
            $request,
            ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'],
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            false,
            '0.0.0.0',
        );

        $this->assertEquals('0.0.0.0', $ip);
    }

    #[Test]
    public function fromServerParamsWithIterateAllIpsFindsFirstPublicIp(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 192.168.1.1, 203.0.113.5, 10.0.0.2',
        ]);

        $ip = NetworkHelper::getClientIpFromServerParams(
            $request,
            ['HTTP_X_FORWARDED_FOR'],
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            true,
        );

        $this->assertEquals('203.0.113.5', $ip);
    }

    #[Test]
    public function fromServerParamsWithoutIterateTakesFirstIpOnly(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 203.0.113.5',
        ]);

        $ip = NetworkHelper::getClientIpFromServerParams(
            $request,
            ['HTTP_X_FORWARDED_FOR'],
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            false,
        );

        $this->assertEquals('127.0.0.1', $ip);
    }

    // ─── getClientIpWithPrivateCheck ────────────────────────────

    #[Test]
    public function privateCheckTrustsHeadersWhenRemoteAddrIsPrivate(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'REMOTE_ADDR'          => '192.168.1.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
        ]);

        $ip = NetworkHelper::getClientIpWithPrivateCheck(
            $request,
            ['HTTP_X_FORWARDED_FOR'],
        );

        $this->assertEquals('203.0.113.5', $ip);
    }

    #[Test]
    public function privateCheckIgnoresHeadersWhenRemoteAddrIsPublic(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'REMOTE_ADDR'          => '8.8.8.8',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
        ]);

        $ip = NetworkHelper::getClientIpWithPrivateCheck(
            $request,
            ['HTTP_X_FORWARDED_FOR'],
        );

        $this->assertEquals('8.8.8.8', $ip);
    }

    #[Test]
    public function privateCheckFallsBackToRemoteAddrWhenAllHeadersPrivate(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'REMOTE_ADDR'          => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '192.168.1.1',
        ]);

        $ip = NetworkHelper::getClientIpWithPrivateCheck(
            $request,
            ['HTTP_X_FORWARDED_FOR'],
        );

        $this->assertEquals('10.0.0.1', $ip);
    }

    #[Test]
    public function privateCheckWithLocalhostRemoteAddrTrustsHeaders(): void
    {
        $request = $this->createNetworkRequest('GET', '/', [], [
            'REMOTE_ADDR'           => '127.0.0.1',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.5',
        ]);

        $ip = NetworkHelper::getClientIpWithPrivateCheck(
            $request,
            ['HTTP_CF_CONNECTING_IP'],
        );

        $this->assertEquals('203.0.113.5', $ip);
    }

    // ─── isIpInRanges ───────────────────────────────────────────

    #[Test]
    public function isIpInRangesExactMatch(): void
    {
        $this->assertTrue(NetworkHelper::isIpInRanges('192.168.1.1', ['192.168.1.1']));
    }

    #[Test]
    public function isIpInRangesExactNoMatch(): void
    {
        $this->assertFalse(NetworkHelper::isIpInRanges('192.168.1.2', ['192.168.1.1']));
    }

    #[Test]
    public function isIpInRangesCidrMatch(): void
    {
        $this->assertTrue(NetworkHelper::isIpInRanges('10.0.0.5', ['10.0.0.0/8']));
    }

    #[Test]
    public function isIpInRangesWildcardMatch(): void
    {
        $this->assertTrue(NetworkHelper::isIpInRanges('192.168.1.100', ['192.168.*']));
    }

    #[Test]
    public function isIpInRangesWildcardMatchMultiSegment(): void
    {
        $this->assertTrue(NetworkHelper::isIpInRanges('10.0.5.100', ['10.0.*.*']));
    }

    #[Test]
    public function isIpInRangesWildcardNoMatch(): void
    {
        $this->assertFalse(NetworkHelper::isIpInRanges('203.0.113.5', ['192.168.*']));
    }

    #[Test]
    public function isIpInRangesMixedListWithWildcard(): void
    {
        $this->assertTrue(NetworkHelper::isIpInRanges('10.0.0.1', ['192.168.1.0/24', '10.0.0.*']));
    }

    #[Test]
    public function isIpInRangesNoMatchInEmptyList(): void
    {
        $this->assertFalse(NetworkHelper::isIpInRanges('1.2.3.4', []));
    }

    // ─── ipInNetwork ────────────────────────────────────────────

    #[Test]
    public function ipInNetworkV4CorrectMatch(): void
    {
        $this->assertTrue(NetworkHelper::ipInNetwork('192.168.1.100', '192.168.1.0/24'));
    }

    #[Test]
    public function ipInNetworkV4CorrectNoMatch(): void
    {
        $this->assertFalse(NetworkHelper::ipInNetwork('10.0.0.5', '192.168.1.0/24'));
    }

    #[Test]
    public function ipInNetworkV4SlashZeroMatchesAll(): void
    {
        $this->assertTrue(NetworkHelper::ipInNetwork('8.8.8.8', '0.0.0.0/0'));
    }

    #[Test]
    public function ipInNetworkV4Slash32ExactMatch(): void
    {
        $this->assertTrue(NetworkHelper::ipInNetwork('1.2.3.4', '1.2.3.4/32'));
    }

    #[Test]
    public function ipInNetworkInvalidCidrReturnsFalse(): void
    {
        $this->assertFalse(NetworkHelper::ipInNetwork('1.2.3.4', 'invalid'));
    }

    #[Test]
    public function ipInNetworkOutOfRangeBitsReturnsFalse(): void
    {
        $this->assertFalse(NetworkHelper::ipInNetwork('1.2.3.4', '1.2.3.0/33'));
    }

    // ─── maskIpAddress ──────────────────────────────────────────

    #[Test]
    public function maskIpAddressIpv4(): void
    {
        $masked = NetworkHelper::maskIpAddress('192.168.1.100');

        $this->assertEquals('192.168.1.xxx', $masked);
    }

    #[Test]
    public function maskIpAddressIpv6Shorthand(): void
    {
        $masked = NetworkHelper::maskIpAddress('2001:db8::1');

        $this->assertEquals('2001:db8::xxxx', $masked);
    }

    #[Test]
    public function maskIpAddressIpv6Full(): void
    {
        $masked = NetworkHelper::maskIpAddress('2001:db8:85a3:8d3:1319:8a2e:370:7348');

        $this->assertEquals('2001:db8:85a3:8d3::xxxx', $masked);
    }

    #[Test]
    public function maskIpAddressInvalidIp(): void
    {
        $masked = NetworkHelper::maskIpAddress('not-an-ip');

        $this->assertEquals('not-axxxx', $masked);
    }
}
