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
    /**
     * 測試結果儲存.
     * @var array<array<string, mixed>>
     */
    private array $testResults = [];

    public function __construct(
        private readonly SessionSecurityServiceInterface $sessionService,
        private readonly AuthorizationServiceInterface $authService,
        private readonly FileSecurityServiceInterface $fileService,
        private readonly SecurityHeaderServiceInterface $headerService,
        private readonly ErrorHandlerServiceInterface $errorService,
        private readonly PasswordSecurityServiceInterface $passwordService,
        private readonly SecretsManagerInterface $secretsManager,
    ) {}

    /**
     * 執行所有安全測試.
     * @return array<array<string, mixed>>
     */
    public function runAllTests(): array
    {
        $this->testResults = [];

        // 執行各項安全測試
        $this->testResults[] = $this->testSessionSecurity();
        $this->testResults[] = $this->testAuthorization();
        $this->testResults[] = $this->testFileSecurity();
        $this->testResults[] = $this->testSecurityHeaders();
        $this->testResults[] = $this->testErrorHandling();
        $this->testResults[] = $this->testPasswordSecurity();
        $this->testResults[] = $this->testSecretsManagement();
        $this->testResults[] = $this->testSystemSecurity();

        return $this->testResults;
    }

    /**
     * 測試 Session 安全性.
     * @return array<string, mixed>
     */
    public function testSessionSecurity(): array
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
            $results['tests'][] = [
                'name' => 'Session 安全初始化',
                'status' => 'PASS',
                'message' => 'Session 成功初始化',
            ];
            $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => 'Session 安全初始化',
                'status' => 'FAIL',
                'message' => 'Session 初始化失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        // 測試 Session ID 重新產生
        try {
            $oldSessionId = session_id();
            $this->sessionService->regenerateSessionId();
            $newSessionId = session_id();

            if ($oldSessionId !== $newSessionId) {
                $results['tests'][] = [
                    'name' => 'Session ID 重新產生',
                    'status' => 'PASS',
                    'message' => 'Session ID 成功重新產生',
                ];
                $results['passed']++;
            } else {
                $results['tests'][] = [
                    'name' => 'Session ID 重新產生',
                    'status' => 'FAIL',
                    'message' => 'Session ID 未變更',
                ];
                $results['failed']++;
            }
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => 'Session ID 重新產生',
                'status' => 'FAIL',
                'message' => 'Session ID 重新產生失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        return $results;
    }

    /**
     * 測試授權安全性.
     * @return array<string, mixed>
     */
    public function testAuthorization(): array
    {
        $results = [
            'test_name' => 'Authorization System',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試權限檢查
        try {
            $hasPermission = $this->authService->hasPermission(1, 'test_permission');
            $results['tests'][] = [
                'name' => '權限檢查',
                'status' => 'PASS',
                'message' => '權限檢查功能正常',
            ];
            $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '權限檢查',
                'status' => 'FAIL',
                'message' => '權限檢查失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        // 測試角色驗證
        try {
            $hasRole = $this->authService->hasRole(1, 'admin');
            $results['tests'][] = [
                'name' => '角色驗證',
                'status' => 'PASS',
                'message' => '角色驗證功能正常',
            ];
            $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '角色驗證',
                'status' => 'FAIL',
                'message' => '角色驗證失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        return $results;
    }

    /**
     * 測試檔案安全性.
     * @return array<string, mixed>
     */
    public function testFileSecurity(): array
    {
        $results = [
            'test_name' => 'File Security',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試檔案名稱清理
        try {
            $sanitized = $this->fileService->sanitizeFileName('../../dangerous.php');
            $results['tests'][] = [
                'name' => '檔案名稱清理',
                'status' => str_contains($sanitized, '..') ? 'FAIL' : 'PASS',
                'message' => '檔案名稱清理功能正常',
            ];
            str_contains($sanitized, '..') ? $results['failed']++ : $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '檔案名稱清理',
                'status' => 'FAIL',
                'message' => '檔案名稱清理失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        // 測試安全檔名生成
        try {
            $secureName = $this->fileService->generateSecureFileName('test.txt', 'upload_');
            $results['tests'][] = [
                'name' => '安全檔名生成',
                'status' => !empty($secureName) ? 'PASS' : 'FAIL',
                'message' => '安全檔名生成功能正常',
            ];
            !empty($secureName) ? $results['passed']++ : $results['failed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '安全檔名生成',
                'status' => 'FAIL',
                'message' => '安全檔名生成測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        return $results;
    }

    /**
     * 測試安全標頭.
     * @return array<string, mixed>
     */
    public function testSecurityHeaders(): array
    {
        $results = [
            'test_name' => 'Security Headers',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試安全標頭設定
        try {
            $this->headerService->setSecurityHeaders();
            $results['tests'][] = [
                'name' => '安全標頭設定',
                'status' => 'PASS',
                'message' => '安全標頭設定正常',
            ];
            $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '安全標頭設定',
                'status' => 'FAIL',
                'message' => '安全標頭測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        // 測試伺服器簽章移除
        try {
            $this->headerService->removeServerSignature();
            $results['tests'][] = [
                'name' => '伺服器簽章移除',
                'status' => 'PASS',
                'message' => '伺服器簽章移除正常',
            ];
            $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '伺服器簽章移除',
                'status' => 'FAIL',
                'message' => '伺服器簽章移除測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        return $results;
    }

    /**
     * 測試錯誤處理.
     * @return array<string, mixed>
     */
    public function testErrorHandling(): array
    {
        $results = [
            'test_name' => 'Error Handling',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試錯誤處理
        try {
            $this->errorService->handleException(new Exception('Test error'));
            $results['tests'][] = [
                'name' => '錯誤處理',
                'status' => 'PASS',
                'message' => '錯誤處理功能正常',
            ];
            $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '錯誤處理',
                'status' => 'FAIL',
                'message' => '錯誤處理測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        return $results;
    }

    /**
     * 測試密碼安全性.
     * @return array<string, mixed>
     */
    public function testPasswordSecurity(): array
    {
        $results = [
            'test_name' => 'Password Security',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試密碼強度評分
        try {
            $strength = $this->passwordService->calculatePasswordStrength('TestPassword123!');
            $results['tests'][] = [
                'name' => '密碼強度評分',
                'status' => isset($strength['score']) ? 'PASS' : 'FAIL',
                'message' => isset($strength['score']) ? '密碼強度評分正常' : '密碼強度評分失敗',
            ];
            if (isset($strength['score'])) {
                $results['passed']++;
            } else {
                $results['failed']++;
            }
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '密碼強度評分',
                'status' => 'FAIL',
                'message' => '密碼強度評分測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        // 測試密碼雜湊
        try {
            $hash = $this->passwordService->hashPassword('TestPassword123!');
            $isValid = $this->passwordService->verifyPassword('TestPassword123!', $hash);
            $results['tests'][] = [
                'name' => '密碼雜湊驗證',
                'status' => $isValid ? 'PASS' : 'FAIL',
                'message' => $isValid ? '密碼雜湊驗證正常' : '密碼雜湊驗證失敗',
            ];
            if ($isValid) {
                $results['passed']++;
            } else {
                $results['failed']++;
            }
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '密碼雜湊驗證',
                'status' => 'FAIL',
                'message' => '密碼雜湊驗證測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        return $results;
    }

    /**
     * 測試秘密管理.
     * @return array<string, mixed>
     */
    public function testSecretsManagement(): array
    {
        $results = [
            'test_name' => 'Secrets Management',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試機密管理
        try {
            $validation = $this->secretsManager->validateEnvFile();
            $results['tests'][] = [
                'name' => '環境檔案驗證',
                'status' => empty($validation) ? 'PASS' : 'FAIL',
                'message' => empty($validation) ? '環境檔案驗證正常' : '環境檔案驗證發現問題',
            ];
            empty($validation) ? $results['passed']++ : $results['failed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '環境檔案驗證',
                'status' => 'FAIL',
                'message' => '環境檔案驗證測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        // 測試機密檢查
        try {
            $requiredSecrets = ['APP_ENV', 'JWT_SECRET'];
            $this->secretsManager->validateRequiredSecrets($requiredSecrets);
            $results['tests'][] = [
                'name' => '必需環境變數檢查',
                'status' => 'PASS',
                'message' => '必需環境變數檢查正常',
            ];
            $results['passed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '必需環境變數檢查',
                'status' => 'FAIL',
                'message' => '必需環境變數檢查失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        // 測試環境檢測
        try {
            $isProd = $this->secretsManager->isProduction();
            $isDev = $this->secretsManager->isDevelopment();
            $results['tests'][] = [
                'name' => '環境檢測',
                'status' => ($isProd || $isDev) ? 'PASS' : 'FAIL',
                'message' => ($isProd || $isDev) ? '環境檢測正常' : '環境檢測失敗',
            ];
            ($isProd || $isDev) ? $results['passed']++ : $results['failed']++;
        } catch (Exception $e) {
            $results['tests'][] = [
                'name' => '環境檢測',
                'status' => 'FAIL',
                'message' => '環境檢測測試失敗: ' . $e->getMessage(),
            ];
            $results['failed']++;
        }

        return $results;
    }

    /**
     * 測試系統安全性.
     * @return array<string, mixed>
     */
    public function testSystemSecurity(): array
    {
        $results = [
            'test_name' => 'System Security',
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
        ];

        // 測試 PHP 版本安全性
        $phpVersion = PHP_VERSION;
        $isSecure = version_compare($phpVersion, '8.0.0', '>=');

        $results['tests'][] = [
            'name' => 'PHP 版本安全性',
            'status' => $isSecure ? 'PASS' : 'FAIL',
            'message' => $isSecure
                ? "PHP 版本 {$phpVersion} 安全"
                : "PHP 版本 {$phpVersion} 過舊，建議升級",
        ];

        if ($isSecure) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }

        // 測試擴充功能安全性
        $requiredExtensions = ['openssl', 'filter', 'hash'];
        foreach ($requiredExtensions as $extension) {
            $isLoaded = extension_loaded($extension);
            $results['tests'][] = [
                'name' => "擴充功能 {$extension}",
                'status' => $isLoaded ? 'PASS' : 'FAIL',
                'message' => $isLoaded
                    ? "擴充功能 {$extension} 已載入"
                    : "擴充功能 {$extension} 未載入",
            ];

            if ($isLoaded) {
                $results['passed']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * 產生安全報告.
     * @return array<string, mixed>
     */
    public function generateSecurityReport(): array
    {
        $allTestResults = $this->runAllTests();

        $totalPassed = 0;
        $totalFailed = 0;
        $criticalIssues = [];

        foreach ($allTestResults as $testResult) {
            $totalPassed += (int) $testResult['passed'];
            $totalFailed += (int) $testResult['failed'];

            // 檢查關鍵問題
            foreach ($testResult['tests'] as $test) {
                if ($test['status'] === 'FAIL') {
                    $criticalIssues[] = [
                        'category' => $testResult['test_name'],
                        'test' => $test['name'],
                        'message' => $test['message'],
                    ];
                }
            }
        }

        $totalTests = $totalPassed + $totalFailed;
        $successRate = $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0;

        return [
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $totalPassed,
                'failed' => $totalFailed,
                'success_rate' => round($successRate, 2),
                'security_level' => $this->getSecurityLevel($successRate),
            ],
            'test_results' => $allTestResults,
            'critical_issues' => $criticalIssues,
            'recommendations' => $this->getRecommendations($criticalIssues),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
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

    /**
     * 根據關鍵問題產生建議.
     * @param array<array<string, mixed>> $criticalIssues 關鍵問題清單
     * @return array<string>
     */
    private function getRecommendations(array $criticalIssues): array
    {
        $recommendations = [];

        if (empty($criticalIssues)) {
            $recommendations[] = '目前的安全配置良好，建議定期進行安全審查';
        } else {
            $recommendations[] = '發現 ' . count($criticalIssues) . ' 個安全問題，建議立即處理';

            foreach ($criticalIssues as $issue) {
                $recommendations[] = (string) $issue['category'] . ': ' . (string) $issue['message'];
            }
        }

        $recommendations[] = '定期更新相依套件和安全補丁';
        $recommendations[] = '實施定期的滲透測試和漏洞掃描';
        $recommendations[] = '確保所有開發人員接受安全培訓';

        return $recommendations;
    }
}
