<?php

declare(strict_types=1);

/**
 * AlleyNote 安全測試執行器
 * 
 * 此腳本用於執行完整的安全測試套件
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;
use App\Services\Security\SecurityTestService;
use App\Services\Security\SessionSecurityService;
use App\Services\Security\AuthorizationService;
use App\Services\Security\FileSecurityService;
use App\Services\Security\SecurityHeaderService;
use App\Services\Security\ErrorHandlerService;
use App\Services\Security\PasswordSecurityService;
use App\Services\Security\SecretsManager;
use App\Services\CacheService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// 解析命令列參數
$options = getopt('', ['format:', 'category:', 'verbose', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

$format = $options['format'] ?? 'text';
$category = $options['category'] ?? null;
$verbose = isset($options['verbose']);

// 設定錯誤報告
if ($verbose) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// 建立日誌記錄器
$logger = new Logger('security_test');
$logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

try {
    echo "AlleyNote 安全測試執行器\n";
    echo "========================\n\n";

    // 初始化服務
    echo "正在初始化服務...\n";

    $database = DatabaseConnection::getInstance();
    $cache = new CacheService();
    $secretsManager = new SecretsManager();

    // 載入環境設定
    $secretsManager->load();

    // 建立安全服務
    $sessionService = new SessionSecurityService();
    $authService = new AuthorizationService($database, $cache);
    $fileService = new FileSecurityService();
    $headerService = new SecurityHeaderService();
    $errorService = new ErrorHandlerService('php://stderr');
    $passwordService = new PasswordSecurityService();

    // 建立測試服務
    $securityTest = new SecurityTestService(
        $sessionService,
        $authService,
        $fileService,
        $headerService,
        $errorService,
        $passwordService,
        $secretsManager
    );

    echo "服務初始化完成。\n\n";

    // 執行測試
    if ($category) {
        echo "執行 {$category} 測試...\n";
        $results = runCategoryTest($securityTest, $category);
    } else {
        echo "執行完整安全測試...\n";
        $results = $securityTest->generateSecurityReport();
    }

    // 輸出結果
    switch ($format) {
        case 'json':
            outputJson($results);
            break;
        case 'xml':
            outputXml($results);
            break;
        default:
            outputText($results, $verbose);
            break;
    }

    // 設定退出代碼
    $exitCode = 0;
    if (isset($results['summary']) && $results['summary']['failed'] > 0) {
        $exitCode = 1;
    }

    exit($exitCode);
} catch (Exception $e) {
    $logger->error('測試執行失敗', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    echo "錯誤: " . $e->getMessage() . "\n";
    exit(2);
}

function showHelp(): void
{
    echo "AlleyNote 安全測試執行器\n\n";
    echo "用法: php security_test_runner.php [選項]\n\n";
    echo "選項:\n";
    echo "  --format=FORMAT    輸出格式 (text|json|xml) [預設: text]\n";
    echo "  --category=CAT     只執行特定類別的測試\n";
    echo "  --verbose          顯示詳細輸出\n";
    echo "  --help             顯示此說明\n\n";
    echo "測試類別:\n";
    echo "  session            Session 安全性測試\n";
    echo "  authorization      授權系統測試\n";
    echo "  file              檔案安全性測試\n";
    echo "  headers           安全標頭測試\n";
    echo "  errors            錯誤處理測試\n";
    echo "  password          密碼安全性測試\n";
    echo "  secrets           秘密管理測試\n";
    echo "  system            系統安全性測試\n\n";
    echo "範例:\n";
    echo "  php security_test_runner.php\n";
    echo "  php security_test_runner.php --format=json\n";
    echo "  php security_test_runner.php --category=session --verbose\n";
}

function runCategoryTest(SecurityTestService $testService, string $category): array
{
    switch ($category) {
        case 'session':
            return ['session_security' => $testService->testSessionSecurity()];
        case 'authorization':
            return ['authorization' => $testService->testAuthorization()];
        case 'file':
            return ['file_security' => $testService->testFileSecurity()];
        case 'headers':
            return ['security_headers' => $testService->testSecurityHeaders()];
        case 'errors':
            return ['error_handling' => $testService->testErrorHandling()];
        case 'password':
            return ['password_security' => $testService->testPasswordSecurity()];
        case 'secrets':
            return ['secrets_management' => $testService->testSecretsManagement()];
        case 'system':
            return ['system_security' => $testService->testSystemSecurity()];
        default:
            throw new InvalidArgumentException("未知的測試類別: {$category}");
    }
}

function outputText(array $results, bool $verbose = false): void
{
    if (isset($results['detailed_results'])) {
        // 完整報告
        foreach ($results['detailed_results'] as $category => $result) {
            echo "=== {$result['test_name']} ===\n";
            echo "通過: {$result['passed']}, 失敗: {$result['failed']}\n";

            if ($verbose || $result['failed'] > 0) {
                foreach ($result['tests'] as $test) {
                    $status = $test['status'] === 'PASS' ? '✓' : ($test['status'] === 'FAIL' ? '✗' : '!');
                    echo "  [{$status}] {$test['name']}: {$test['message']}\n";
                }
            }
            echo "\n";
        }

        echo "=== 總結 ===\n";
        echo "總測試數: {$results['summary']['total_tests']}\n";
        echo "通過: {$results['summary']['passed']}\n";
        echo "失敗: {$results['summary']['failed']}\n";
        echo "通過率: {$results['summary']['success_rate']}\n";
        echo "安全等級: {$results['summary']['security_level']}\n";

        if (!empty($results['critical_issues'])) {
            echo "\n=== 關鍵問題 ===\n";
            foreach ($results['critical_issues'] as $issue) {
                echo "- {$issue['category']}: {$issue['message']}\n";
            }
        }

        echo "\n=== 建議 ===\n";
        foreach ($results['recommendations'] as $recommendation) {
            echo "- {$recommendation}\n";
        }
    } else {
        // 單一類別結果
        foreach ($results as $category => $result) {
            echo "=== {$result['test_name']} ===\n";
            echo "通過: {$result['passed']}, 失敗: {$result['failed']}\n";

            foreach ($result['tests'] as $test) {
                $status = $test['status'] === 'PASS' ? '✓' : ($test['status'] === 'FAIL' ? '✗' : '!');
                echo "  [{$status}] {$test['name']}: {$test['message']}\n";
            }
        }
    }
}

function outputJson(array $results): void
{
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function outputXml(array $results): void
{
    $xml = new SimpleXMLElement('<security_test_results/>');
    arrayToXml($results, $xml);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    echo $dom->saveXML();
}

function arrayToXml(array $data, SimpleXMLElement $xml): void
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                $key = 'item';
            }
            $subNode = $xml->addChild($key);
            arrayToXml($value, $subNode);
        } else {
            $xml->addChild($key, htmlspecialchars((string) $value));
        }
    }
}
