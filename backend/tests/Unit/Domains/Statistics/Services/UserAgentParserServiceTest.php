<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Services\UserAgentParserService;
use PHPUnit\Framework\TestCase;

/**
 * UserAgentParserService 單元測試
 */
class UserAgentParserServiceTest extends TestCase
{
    private UserAgentParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new UserAgentParserService();
    }

    public function testParseChromeUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $result = $this->parser->parse($userAgent);

        $this->assertEquals('Chrome', $result['browser']);
        $this->assertEquals('Desktop', $result['device_type']);
        $this->assertEquals('Windows 10', $result['os']);
    }

    public function testParseFirefoxUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0';
        $result = $this->parser->parse($userAgent);

        $this->assertEquals('Firefox', $result['browser']);
        $this->assertEquals('Desktop', $result['device_type']);
        $this->assertEquals('Windows 10', $result['os']);
    }

    public function testParseMobileUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1';
        $result = $this->parser->parse($userAgent);

        $this->assertEquals('Safari', $result['browser']);
        $this->assertEquals('Mobile', $result['device_type']);
        $this->assertEquals('iOS', $result['os']);
    }

    public function testParseTabletUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1';
        $result = $this->parser->parse($userAgent);

        $this->assertEquals('Safari', $result['browser']);
        $this->assertEquals('Tablet', $result['device_type']);
        $this->assertEquals('iOS', $result['os']);
    }

    public function testParseNullUserAgent(): void
    {
        $result = $this->parser->parse(null);

        $this->assertEquals('Unknown', $result['browser']);
        $this->assertEquals('Unknown', $result['device_type']);
        $this->assertEquals('Unknown', $result['os']);
    }

    public function testParseEmptyUserAgent(): void
    {
        $result = $this->parser->parse('');

        $this->assertEquals('Unknown', $result['browser']);
        $this->assertEquals('Unknown', $result['device_type']);
        $this->assertEquals('Unknown', $result['os']);
    }

    public function testParseBatch(): void
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            null,
        ];

        $results = $this->parser->parseBatch($userAgents);

        $this->assertCount(3, $results);
        $this->assertEquals('Chrome', $results[0]['browser']);
        $this->assertEquals('Safari', $results[1]['browser']);
        $this->assertEquals('Unknown', $results[2]['browser']);
    }
}
