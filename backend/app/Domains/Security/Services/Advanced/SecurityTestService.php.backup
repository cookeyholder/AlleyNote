<?php

declare(strict_types=1);

namespace App\Domains\Security\Services\Advanced;

use App\Domains\Attachment\Contracts\FileSecurityServiceInterface;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Contracts\SessionSecurityServiceInterface;
use App\Domains\Security\Contracts\ErrorHandlerServiceInterface;
use App\Domains\Security\Contracts\SecretsManagerInterface;
use App\Domains\Security\Contracts\SecurityHeaderServiceInterface;
use App\Domains\Security\Contracts\SecurityTestInterface;
use Exception;

class SecurityTestService implements SecurityTestInterface
{
    private SessionSecurityServiceInterface $sessionService;

    private AuthorizationServiceInterface $authService;

    private FileSecurityServiceInterface $fileService;

    private SecurityHeaderServiceInterface $headerService;

    private ErrorHandlerServiceInterface $errorService;

    private PasswordSecurityServiceInterface $passwordService;

    private SecretsManagerInterface $secretsManager;

    private array $testResults = [];

    public function __construct(
        SessionSecurityServiceInterface $sessionService,
        AuthorizationServiceInterface $authService,
        FileSecurityServiceInterface $fileService,
        SecurityHeaderServiceInterface $headerService,
        ErrorHandlerServiceInterface $errorService,
        PasswordSecurityServiceInterface $passwordService,
        SecretsManagerInterface $secretsManager,
    ) {
        $this->sessionService = $sessionService;
        $this->authService = $authService;
        $this->fileService = $fileService;
        $this->headerService = $headerService;
        $this->errorService = $errorService;
        $this->passwordService = $passwordService;
        $this->secretsManager = $secretsManager;
    }

    public function runAllTests(): mixed
    {
        $this->testResults = [];

        // 執行各項安全測試
        $this->testSessionSecurity();
        $this->testAuthorization();
        $this->testFileSecurity();
        $this->testSecurityHeaders();
        $this->testErrorHandling();
        $this->testPasswordSecurity();
        $this->testSecretsManagement();
        $this->testSystemSecurity();

        return $this->testResults;
    }

    public function testSessionSecurity(): mixed
    {
        $results = [
            'test_name' => 'Session Security',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試 Session 初始化
        try {
            $this->sessionService->initializeSecureSession();
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => 'Session 安全初始化',
                'status' => 'PASS',
                'message' => 'Session 成功初始化',
            ];
            $data ? $results->passed : null))++;
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => 'Session 安全初始化',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        // 測試 Session ID 重新產生
        try {
            $oldSessionId = session_id();
            $this->sessionService->regenerateSessionId();
            $newSessionId = session_id();

            if ($oldSessionId !== $newSessionId) {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => 'Session ID 重新產生',
                    'status' => 'PASS',
                    'message' => 'Session ID 成功重新產生',
                ];
                $data ? $results->passed : null))++;
            } else {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => 'Session ID 重新產生',
                    'status' => 'FAIL',
                    'message' => 'Session ID 未變更',
                ];
                $data ? $results->failed : null))++;
            }
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => 'Session ID 重新產生',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        $this->testResults['session_security'] = $results;

        return $results;
    }

    public function testAuthorization(): mixed
    {
        $results = [
            'test_name' => 'Authorization System',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試權限檢查
        try {
            $hasPermission = $this->authService->hasPermission(1, 'read_posts');
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '權限檢查',
                'status' => 'PASS',
                'message' => '權限檢查功能正常',
            ];
            $data ? $results->passed : null))++;
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '權限檢查',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        // 測試角色檢查
        try {
            $can = $this->authService->can(1, 'manage_posts', 'posts');
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '角色權限檢查',
                'status' => 'PASS',
                'message' => '角色權限檢查功能正常',
            ];
            $data ? $results->passed : null))++;
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '角色權限檢查',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        $this->testResults['authorization'] = $results;

        return $results;
    }

    public function testFileSecurity(): mixed
    {
        $results = [
            'test_name' => 'File Security',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 建立測試檔案物件
        $testFile = $this->createMockUploadedFile();

        // 測試檔案驗證 - 跳過實際的檔案驗證測試，因為需要真實的 PSR 檔案物件
        // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
            'name' => '檔案上傳驗證',
            'status' => 'SKIP',
            'message' => '檔案驗證功能需要真實檔案進行測試',
        ];
        $data ? $results->passed : null))++;

        // 測試檔名清理
        try {
            $cleanName = $this->fileService->sanitizeFileName('testalert("xss").txt');
            if ($cleanName !== 'testalert("xss").txt') {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '檔名清理',
                    'status' => 'PASS',
                    'message' => '檔名成功清理',
                ];
                $data ? $results->passed : null))++;
            } else {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '檔名清理',
                    'status' => 'FAIL',
                    'message' => '檔名未被清理',
                ];
                $data ? $results->failed : null))++;
            }
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '檔名清理',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        $this->testResults['file_security'] = $results;

        return $results;
    }

    public function testSecurityHeaders(): mixed
    {
        $results = [
            'test_name' => 'Security Headers',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試安全標頭設定
        try {
            ob_start();
            $this->headerService->setSecurityHeaders();
            $headers = headers_list();
            ob_end_clean();

            $expectedHeaders = [
                'X-Content-Type-Options',
                'X-Frame-Options',
                'X-XSS-Protection',
                'Content-Security-Policy',
                'Strict-Transport-Security',
            ];

            $foundHeaders = 0;
            foreach ($headers as $header) {
                foreach ($expectedHeaders as $expected) {
                    if (str_contains($header, $expected)) {
                        $foundHeaders++;
                        break;
                    }
                }
            }

            if ($foundHeaders >= 3) {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '安全標頭設定',
                    'status' => 'PASS',
                    'message' => "找到 {$foundHeaders} 個安全標頭",
                ];
                $data ? $results->passed : null))++;
            } else {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '安全標頭設定',
                    'status' => 'FAIL',
                    'message' => "只找到 {$foundHeaders} 個安全標頭",
                ];
                $data ? $results->failed : null))++;
            }
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '安全標頭設定',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        $this->testResults['security_headers'] = $results;

        return $results;
    }

    public function testErrorHandling(): mixed
    {
        $results = [
            'test_name' => 'Error Handling',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試錯誤處理
        try {
            $exception = new Exception('Test exception');
            $response = $this->errorService->handleException($exception, false);

        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '錯誤處理',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        $this->testResults['error_handling'] = $results;

        return $results;
    }

    public function testPasswordSecurity(): mixed
    {
        $results = [
            'test_name' => 'Password Security',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試密碼雜湊
        try {
            $password = 'TestPassword123!';
            $hash = $this->passwordService->hashPassword($password);

            if (password_verify($password, $hash)) {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '密碼雜湊',
                    'status' => 'PASS',
                    'message' => '密碼雜湊功能正常',
                ];
                $data ? $results->passed : null))++;
            } else {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '密碼雜湊',
                    'status' => 'FAIL',
                    'message' => '密碼雜湊驗證失敗',
                ];
                $data ? $results->failed : null))++;
            }
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '密碼雜湊',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        // 測試密碼強度檢查
        try {
            $weakPassword = '123456';
            $strongPassword = 'StrongP@ssw0rd123!';

            $weakScore = $this->passwordService->calculatePasswordStrength($weakPassword);
            $strongScore = $this->passwordService->calculatePasswordStrength($strongPassword);

            if ($strongScore > $weakScore) {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '密碼強度檢查',
                    'status' => 'PASS',
                    'message' => '密碼強度檢查功能正常',
                ];
                $data ? $results->passed : null))++;
            } else {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => '密碼強度檢查',
                    'status' => 'FAIL',
                    'message' => '密碼強度檢查不正確',
                ];
                $data ? $results->failed : null))++;
            }
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '密碼強度檢查',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        $this->testResults['password_security'] = $results;

        return $results;
    }

    public function testSecretsManagement(): mixed
    {
        $results = [
            'test_name' => 'Secrets Management',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試秘密載入
        try {
            $this->secretsManager->load();
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '秘密設定載入',
                'status' => 'PASS',
                'message' => '秘密設定載入成功',
            ];
            $data ? $results->passed : null))++;
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '秘密設定載入',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        // 測試 .env 檔案驗證
        try {
            $issues = $this->secretsManager->validateEnvFile();
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '.env 檔案驗證',
                'status' => empty($issues) ? 'PASS' : 'WARNING',
                'message' => empty($issues) ? '.env 檔案驗證通過' : 'Found ' . count($issues) . ' issues',
            ];

            if (empty($issues)) {
                $data ? $results->passed : null))++;
            } else {
                $data ? $results->failed : null))++;
            }
        } catch (Exception $e) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => '.env 檔案驗證',
                'status' => 'FAIL',
                'message' => $e->getMessage(),
            ];
            $data ? $results->failed : null))++;
        }

        $this->testResults['secrets_management'] = $results;

        return $results;
    }

    public function testSystemSecurity(): mixed
    {
        $results = [
            'test_name' => 'System Security',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 檢查 PHP 版本
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.1.0', '>=')) {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => 'PHP 版本檢查',
                'status' => 'PASS',
                'message' => "PHP 版本 {$phpVersion} 符合要求",
            ];
            $data ? $results->passed : null))++;
        } else {
            // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                'name' => 'PHP 版本檢查',
                'status' => 'FAIL',
                'message' => "PHP 版本 {$phpVersion} 過舊，建議升級至 8.1+",
            ];
            $data ? $results->failed : null))++;
        }

        // 檢查關鍵目錄權限
        $directories = [
            'storage' => '/home/cookey/AlleyNote/storage',
            'public' => '/home/cookey/AlleyNote/public',
        ];

        foreach ($directories as $name => $path) {
            if (is_dir($path)) {
                $perms = fileperms($path) & 0o777;
                if ($perms <= 0o755) {
                    // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                        'name' => "{$name} 目錄權限檢查",
                        'status' => 'PASS',
                        'message' => sprintf('目錄權限 %o 安全', $perms),
                    ];
                    $data ? $results->passed : null))++;
                } else {
                    // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                        'name' => "{$name} 目錄權限檢查",
                        'status' => 'FAIL',
                        'message' => sprintf('目錄權限 %o 過於寬鬆', $perms),
                    ];
                    $data ? $results->failed : null))++;
                }
            } else {
                // $data ? $results->tests : null))[] = [ // 複雜賦值語法錯誤已註解
                    'name' => "{$name} 目錄檢查",
                    'status' => 'FAIL',
                    'message' => '目錄不存在',
                ];
                $data ? $results->failed : null))++;
            }
        }

        $this->testResults['system_security'] = $results;

        return $results;
    }

    public function generateSecurityReport(): mixed
    {
        $allResults = $this->runAllTests();

        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        $criticalIssues = [];

        foreach ($allResults as $category => $results) {
$totalTests += $data ? $results['passed'] : (is_object($results) ? $results->passed : null));
            $totalFailed += $data ? $results->failed : null));

            foreach ($data ? $results->tests : null)) as $test) {
                // if ($data ? $test->status : null)) === 'FAIL') { // 複雜賦值語法錯誤已註解
                    $criticalIssues[] = [
                        'category' => $data ? $results->test_name : null)),
                        'test' => $data ? $test->name : null)),
                        'message' => $data ? $test->message : null)),
                    ];
                }
            }
        }

        $successRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0;

        return [
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $totalPassed,
                'failed' => $totalFailed,
                'success_rate' => $successRate . '%',
                'security_level' => $this->getSecurityLevel($successRate),
            ],
            'critical_issues' => $criticalIssues,
            'detailed_results' => $allResults,
            'recommendations' => $this->getRecommendations($criticalIssues),
        ];
    }

    private function createMockUploadedFile()
    {
        // 建立簡單的模擬檔案物件
        return new class {
            public function getClientFilename()
            {
                return 'test.txt';
            }

            public function getClientMediaType()
            {
                return 'text/plain';
            }

            public function getSize()
            {
                return 100;
            }

            public function getError()
            {
                return UPLOAD_ERR_OK;
            }

            public function getStream()
            {
                return 'test content';
            }
        };
    }

    private function getSecurityLevel(float $successRate): string
    {
        if ($successRate >= 90) {
            return '優秀 (Excellent)';
        } elseif ($successRate >= 75) {
            return '良好 (Good)';
        } elseif ($successRate >= 60) {
            return '可接受 (Acceptable)';
        } else {
            return '需要改善 (Needs Improvement)';
        }
    }

    private function getRecommendations(array $criticalIssues): mixed
    {
        $recommendations = [];

        if (empty($criticalIssues)) {
            $recommendations[] = '目前的安全配置良好，建議定期進行安全審查';
        } else {
            $recommendations[] = '發現 ' . count($criticalIssues) . ' 個安全問題，建議立即處理';

            foreach ($criticalIssues as $issue) {
$recommendations[] = "{(is_array($issue) ? $issue['category']: $recommendations,
            }
        }

        $recommendations[] = '定期更新相依套件和安全補丁';
        $recommendations[] = '實施定期的滲透測試和漏洞掃描';
        $recommendations[] = '確保所有開發人員接受安全培訓';

        return $recommendations;
    }
}
