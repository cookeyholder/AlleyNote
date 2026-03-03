<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Helpers;

use App\Shared\Helpers\NetworkHelper;
use PHPUnit\Framework\Attributes\Test;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Http\Stream;
use Tests\SecureDDDTestCase;

/**
 * @covers \App\Shared\Helpers\NetworkHelper
 */
class NetworkHelperTest extends SecureDDDTestCase
{
    private function createNetworkRequest(string $method = 'GET', string $path = '/', array $headers = [], array $serverParams = []): ServerRequest
    {
        $uri = (new Uri())->withPath($path);
        // ServerRequest constructor: string $method, UriInterface $uri, array $headers, StreamInterface $body, string $version, array $serverParams
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
    public function returnsFirstIpFromXForwardedFor(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['5.6.7.8, 10.0.0.1']], ['REMOTE_ADDR' => '10.0.0.1']);

        $ip = NetworkHelper::getClientIp($request);

        $this->assertEquals('5.6.7.8', $ip);
    }

    #[Test]
    public function returnsXRealIpIfPresent(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Real-IP' => ['9.10.11.12']]);

        $ip = NetworkHelper::getClientIp($request);

        $this->assertEquals('9.10.11.12', $ip);
    }

    #[Test]
    public function returnsCfConnectingIpIfPresent(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['CF-Connecting-IP' => ['13.14.15.16']]);

        $ip = NetworkHelper::getClientIp($request);

        $this->assertEquals('13.14.15.16', $ip);
    }

    #[Test]
    public function validatesIpFormat(): void
    {
        $request = $this->createNetworkRequest('GET', '/', ['X-Forwarded-For' => ['invalid-ip, 1.2.3.4']], ['REMOTE_ADDR' => '1.2.3.4']);

        $ip = NetworkHelper::getClientIp($request);
        
        $this->assertEquals('1.2.3.4', $ip);
    }

    #[Test]
    public function defaultsToLocalhostWhenNoServerParams(): void
    {
        $request = $this->createNetworkRequest('GET', '/');
        
        $ip = NetworkHelper::getClientIp($request);

        $this->assertEquals('127.0.0.1', $ip);
    }
}
