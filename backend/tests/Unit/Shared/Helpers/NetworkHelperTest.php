<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Helpers;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use App\Shared\Helpers\NetworkHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\SecureDDDTestCase;

/**
 * @covers \App\Shared\Helpers\NetworkHelper
 */
class NetworkHelperTest extends SecureDDDTestCase
{
    private function createNetworkRequest(string $method = 'GET', string $path = '/', array $headers = [], array $serverParams = []): ServerRequest
    {
        $uri = new Uri()->withPath($path);

        return new ServerRequest($method, $uri, $headers, new Stream(''), '1.1', $serverParams);
    }

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

        // 模擬 10.0.0.1 是信任的代理
        $ip = NetworkHelper::getClientIp($request, ['10.0.0.1']);

        $this->assertEquals('5.6.7.8', $ip);
    }

    #[Test]
    public function ignoresXForwardedForWhenNotTrusted(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['5.6.7.8']], ['REMOTE_ADDR' => '1.2.3.4']);

        // remote_addr 1.2.3.4 不在信任清單中，應無視標頭並回傳 1.2.3.4
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

        // 雖然 remote_addr 被信任，但標頭內容無效，應回傳 remote_addr
        $this->assertEquals('127.0.0.1', $ip);
    }

    #[Test]
    public function supportsCidrRangesForTrustedProxies(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['5.6.7.8']], ['REMOTE_ADDR' => '192.168.1.50']);

        // 信任 192.168.1.0/24 整個網段
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
}
