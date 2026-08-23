<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Services\Headers;

use App\Domains\Security\Services\Headers\SecurityHeaderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * SecurityHeaderService 測試.
 *
 * 標頭組裝邏輯直接驗證；涉及 php://input 的 CSP 回報流程以子程序覆蓋。
 */
#[CoversClass(SecurityHeaderService::class)]
class SecurityHeaderServiceTest extends UnitTestCase
{
    /** @var array<string, mixed> 備份的 項目 */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['HTTPS', 'SERVER_PORT', 'HTTP_X_FORWARDED_PROTO', 'REQUEST_METHOD', 'CONTENT_TYPE'] as $key) {
            $this->serverBackup[$key] = $_SERVER[$key] ?? null;
            unset($_SERVER[$key]);
        }
        // 生產碼 isHTTPS() 未防護 SERVER_PORT 缺失，測試中提供預設埠號
        if (!isset($_SERVER['SERVER_PORT'])) {
            $_SERVER['SERVER_PORT'] = '80';
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        parent::tearDown();
    }

    #[Test]
    public function it_generates_default_headers_without_hsts_over_http(): void
    {
        $service = new SecurityHeaderService();
        $headers = $service->generateHeaders();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertSame('DENY', $headers['X-Frame-Options']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('1; mode=block', $headers['X-XSS-Protection']);
        $this->assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
        $this->assertArrayHasKey('Permissions-Policy', $headers);
        $this->assertSame('same-origin', $headers['Cross-Origin-Opener-Policy']);
        $this->assertSame('same-origin', $headers['Cross-Origin-Resource-Policy']);
        $this->assertStringContainsString('no-cache', (string) ($headers['Cache-Control'] ?? ''));

        // HTTP 環境下不送出 HSTS；server_signature 預設關閉
        $this->assertArrayNotHasKey('Strict-Transport-Security', $headers);
        $this->assertArrayNotHasKey('Server', $headers);
        $this->assertFalse($service->isServerSignatureEnabled());
    }

    #[Test]
    public function it_builds_csp_with_nonce_and_report_uri(): void
    {
        $csp = new SecurityHeaderService()->generateHeaders()['Content-Security-Policy'] ?? '';

        $this->assertIsString($csp);
        // script-src 移除 unsafe-inline 並加入 nonce
        $this->assertStringNotContainsString("'unsafe-inline'", $this->extractDirective($csp, 'script-src'));
        $this->assertMatchesRegularExpression(
            '/nonce-[A-Za-z0-9+\/]{22}==/',
            $this->extractDirective($csp, 'script-src'),
        );
        $this->assertStringContainsString('report-uri /api/csp-report', $csp);
        // 空來源清單的指令僅輸出名稱
        $this->assertSame('upgrade-insecure-requests', $this->extractDirective($csp, 'upgrade-insecure-requests'));
    }

    #[Test]
    public function it_includes_hsts_when_request_is_https(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $headers = new SecurityHeaderService()->generateHeaders();

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $headers['Strict-Transport-Security'] ?? '',
        );
    }

    #[Test]
    public function it_detects_https_via_server_port_and_supports_preload(): void
    {
        $_SERVER['SERVER_PORT'] = 443;

        $service = new SecurityHeaderService([
            'hsts' => [
                'enabled'            => true,
                'max_age'            => 999,
                'include_subdomains' => false,
                'preload'            => true,
            ],
        ]);

        $headers = $service->generateHeaders();

        $this->assertSame('max-age=999; preload', $headers['Strict-Transport-Security'] ?? '');
    }

    #[Test]
    public function it_falls_back_to_default_max_age_for_non_integer_value(): void
    {
        $_SERVER['HTTPS'] = 'on';

        /** @var array<string, mixed> $hsts */
        $hsts = [
            'enabled'            => true,
            'max_age'            => 'not-a-number',
            'include_subdomains' => false,
            'preload'            => false,
        ];

        $headers = new SecurityHeaderService(['hsts' => $hsts])->generateHeaders();

        $this->assertSame('max-age=31536000', $headers['Strict-Transport-Security'] ?? '');
    }

    #[Test]
    public function it_can_disable_every_optional_header(): void
    {
        $config = [
            'frame_options'        => ['enabled' => true, 'value' => 'SAMEORIGIN'],
            'csp'                  => ['enabled' => false],
            'hsts'                 => ['enabled' => false],
            'content_type_options' => ['enabled' => false],
            'xss_protection'       => ['enabled' => false],
            'referrer_policy'      => ['enabled' => false],
            'permissions_policy'   => ['enabled' => false],
            'coep'                 => ['enabled' => false],
            'coop'                 => ['enabled' => false],
            'corp'                 => ['enabled' => false],
            'cache_control'        => ['enabled' => false],
            'server_signature'     => ['enabled' => false],
        ];

        $headers = new SecurityHeaderService($config)->generateHeaders();

        $this->assertSame(['X-Frame-Options' => 'SAMEORIGIN'], $headers);
    }

    #[Test]
    public function it_exposes_server_signature_setting(): void
    {
        $enabled = new SecurityHeaderService([
            'server_signature' => ['enabled' => true, 'value' => 'AlleyNote/1.0'],
        ]);

        $this->assertTrue($enabled->isServerSignatureEnabled());
        $headers = $enabled->generateHeaders();
        $this->assertSame('AlleyNote/1.0', $headers['Server'] ?? '');

        // removeServerSignature 在 CLI 環境下應可安全呼叫
        $enabled->removeServerSignature();
        new SecurityHeaderService()->removeServerSignature();

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function generate_nonce_is_stable_per_instance_and_unique_across_instances(): void
    {
        $first = new SecurityHeaderService();
        $second = new SecurityHeaderService();

        $this->assertNull($first->getCurrentNonce());

        $nonceA1 = $first->generateNonce();
        $nonceA2 = $first->generateNonce();

        $this->assertSame(24, strlen($nonceA1));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]{22}==$/', $nonceA1);
        $this->assertSame($nonceA1, $nonceA2);
        $this->assertSame($nonceA1, $first->getCurrentNonce());
        $this->assertNotSame($nonceA1, $second->generateNonce());
    }

    #[Test]
    public function build_permissions_policy_merges_scalar_and_list_directives(): void
    {
        $policy = new SecurityHeaderService()->generateHeaders()['Permissions-Policy'] ?? '';

        $this->assertIsString($policy);
        $this->assertStringContainsString('geolocation=()', $policy);
        $this->assertStringContainsString('fullscreen=(self)', $policy);
    }

    #[Test]
    public function csp_report_rejects_non_post_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $service = new SecurityHeaderService();
        $service->handleCSPReport();

        $this->assertSame(405, http_response_code());
    }

    #[Test]
    public function csp_report_rejects_unsupported_content_type(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'text/plain';

        $service = new SecurityHeaderService();
        $service->handleCSPReport();

        $this->assertSame(400, http_response_code());
    }

    #[Test]
    public function csp_report_rejects_malformed_json_body(): void
    {
        // CLI 下 php://input 為空字串，等同於無效 JSON
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/csp-report';

        $service = new SecurityHeaderService();
        $service->handleCSPReport();

        $this->assertSame(400, http_response_code());
    }

    #[Test]
    public function csp_report_accepts_valid_report_via_builtin_server(): void
    {
        $report = '{"csp-report":{"document-uri":"https://example.test/","violated-directive":"script-src"}}';

        $this->assertSame('CODE=204', $this->probeCspEndpoint($report, []));
    }

    #[Test]
    public function csp_report_still_succeeds_when_monitoring_endpoint_is_unreachable(): void
    {
        $report = '{"csp-report":{"document-uri":"https://example.test/"}}';
        $output = $this->probeCspEndpoint($report, [
            'csp' => [
                'enabled'             => true,
                'monitoring_endpoint' => 'http://127.0.0.1:9/collect',
            ],
        ]);

        $this->assertSame('CODE=204', $output);
    }

    /**
     * 透過 PHP 內建伺服器發送真實 POST，覆蓋 CSP 回報成功路徑.
     *
     * @param array<string, mixed> $config
     */
    private function probeCspEndpoint(string $reportBody, array $config): string
    {
        $encodedConfig = var_export($config, true);
        // 以動態路徑解析 autoload，避免子進程腳本依賴特定掛載位置
        $autoloadPath = var_export(dirname(__DIR__, 6) . '/vendor/autoload.php', true);
        $router = <<<PHP
            <?php
            declare(strict_types=1);
            ini_set('display_errors', '0');
            require {$autoloadPath};
            // 將警告轉為例外，使 sendToMonitoring 的 catch(Throwable) 可被觸發
            set_error_handler(static function (int \$severity, string \$message, string \$file, int \$line): bool {
                throw new ErrorException(\$message, 0, \$severity, \$file, \$line);
            });
            if (\$_SERVER['REQUEST_METHOD'] === 'POST' && \$_SERVER['REQUEST_URI'] === '/csp') {
                \$service = new App\Domains\Security\Services\Headers\SecurityHeaderService((array) {$encodedConfig});
                \$service->handleCSPReport();
                echo 'CODE=' . http_response_code();
                return true;
            }
            http_response_code(404);
            return false;
            PHP;

        $routerPath = tempnam(sys_get_temp_dir(), 'csp_router_');
        if ($routerPath === false) {
            throw new RuntimeException('無法建立暫存路由腳本');
        }
        file_put_contents($routerPath, $router);

        $port = 18000 + random_int(0, 1999);
        $process = proc_open(
            'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($routerPath),
            [],
            $pipes,
        );
        if (!is_resource($process)) {
            @unlink($routerPath);

            throw new RuntimeException('無法啟動內建伺服器');
        }

        try {
            // 等待伺服器就緒
            $context = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/csp-report\r\n",
                'content' => $reportBody,
                'timeout' => 5,
            ]]);
            $response = false;
            for ($i = 0; $i < 20; $i++) {
                usleep(100_000);
                $response = @file_get_contents("http://127.0.0.1:{$port}/csp", false, $context);
                if ($response !== false && str_contains((string) $response, 'CODE=')) {
                    break;
                }
            }

            return trim((string) $response);
        } finally {
            proc_terminate($process);
            proc_close($process);
            @unlink($routerPath);
        }
    }

    /**
     * 從 CSP 字串抽出指定指令內容.
     */
    private function extractDirective(string $csp, string $name): string
    {
        foreach (explode('; ', $csp) as $directive) {
            if (str_starts_with((string) $directive, $name . ' ') || (string) $directive === $name) {
                return (string) $directive;
            }
        }

        return '';
    }
}
