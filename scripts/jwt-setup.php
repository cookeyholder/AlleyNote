<?php

declare(strict_types=1);

/**
 * JWT 密鑰生成和配置工具
 * 
 * 自動生成 RSA 密鑰對並配置測試環境
 * 提供金鑰輪替、健康檢查和開發工具功能
 */

class JwtSetupTool
{
    private const ENV_FILE = __DIR__ . '/../.env';
    private const BACKUP_DIR = __DIR__ . '/../storage/jwt-keys-backup';
    private const DEFAULT_KEY_SIZE = 2048;
    private const LARGE_KEY_SIZE = 4096;

    /**
     * 主要設定流程
     */
    public static function setup(): void
    {
        self::info("🚀 JWT 密鑰生成和配置工具");
        self::info("=====================================");

        try {
            // 檢查 OpenSSL 擴充
            if (!extension_loaded('openssl')) {
                throw new RuntimeException('需要 OpenSSL 擴充');
            }

            // 生成密鑰對
            $keys = self::generateKeyPair(self::DEFAULT_KEY_SIZE);

            // 驗證密鑰對
            if (!self::validateKeyPair($keys['private'], $keys['public'])) {
                throw new RuntimeException('密鑰對驗證失敗');
            }

            // 顯示密鑰資訊
            self::displayKeysInfo($keys);

            // 設定測試環境變數
            self::setTestEnvironmentVariables($keys);

            // 載入自動載入器以測試配置
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';

                // 測試 JWT 配置
                if (self::testJwtConfiguration()) {
                    self::success("🎉 JWT 配置完全成功！");
                }
            }

            // 保存到 .env 檔案
            if (file_exists(self::ENV_FILE)) {
                self::saveKeysToEnvFile($keys, self::ENV_FILE);
            } else {
                self::warning("⚠️  未找到 .env 檔案，請手動設定環境變數");
            }

            self::success("✅ JWT 設定完成！現在可以執行測試了。");
            self::info("\n� 建議下一步：");
            self::info("  docker compose exec -T web ./vendor/bin/phpunit --filter='Jwt|JWT|Auth'");
        } catch (Exception $e) {
            self::error("❌ 錯誤: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * JWT 金鑰輪替功能
     */
    public static function rotateKeys(): void
    {
        self::info("🔄 開始執行 JWT 金鑰輪替...");

        try {
            // 1. 備份現有 .env 檔案和金鑰
            self::backupEnv();
            self::backupCurrentKeys();

            // 2. 產生新的金鑰對（使用更大的金鑰長度以增強安全性）
            $keyPair = self::generateKeyPair(self::LARGE_KEY_SIZE);

            // 3. 驗證新金鑰
            if (!self::validateKeyPair($keyPair['private'], $keyPair['public'])) {
                throw new RuntimeException('新金鑰驗證失敗');
            }

            // 4. 更新環境變數
            self::saveKeysToEnvFile($keyPair, self::ENV_FILE);

            self::success("🎉 JWT 金鑰輪替完成！");
            self::info("🔐 舊金鑰已備份至: " . self::BACKUP_DIR);
            self::warning("⚠️  注意：所有現有 JWT Token 將失效，用戶需重新登入");
        } catch (Exception $e) {
            self::error("金鑰輪替失敗: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * JWT 健康檢查
     */
    public static function healthCheck(): void
    {
        self::info("🏥 JWT 系統健康檢查");
        self::info("=====================================");

        $issues = [];

        // 1. 檢查 .env 檔案存在
        if (!file_exists(self::ENV_FILE)) {
            $issues[] = "❌ .env 檔案不存在";
        } else {
            self::success("✅ .env 檔案存在");
        }

        // 2. 檢查金鑰配置
        $envContent = file_get_contents(self::ENV_FILE);
        $hasPrivateKey = strpos($envContent, 'JWT_PRIVATE_KEY=') !== false;
        $hasPublicKey = strpos($envContent, 'JWT_PUBLIC_KEY=') !== false;

        if (!$hasPrivateKey) {
            $issues[] = "❌ JWT_PRIVATE_KEY 未配置";
        } else {
            self::success("✅ JWT_PRIVATE_KEY 已配置");
        }

        if (!$hasPublicKey) {
            $issues[] = "❌ JWT_PUBLIC_KEY 未配置";
        } else {
            self::success("✅ JWT_PUBLIC_KEY 已配置");
        }

        // 3. 檢查金鑰有效性
        if ($hasPrivateKey && $hasPublicKey) {
            preg_match('/JWT_PRIVATE_KEY="([^"]+)"/', $envContent, $privateMatches);
            preg_match('/JWT_PUBLIC_KEY="([^"]+)"/', $envContent, $publicMatches);

            if (!empty($privateMatches[1]) && !empty($publicMatches[1])) {
                $privateKey = str_replace('\\n', "\n", $privateMatches[1]);
                $publicKey = str_replace('\\n', "\n", $publicMatches[1]);

                if (self::validateKeyPair($privateKey, $publicKey)) {
                    self::success("✅ 金鑰對驗證通過");
                } else {
                    $issues[] = "❌ 金鑰對驗證失敗";
                }

                // 檢查金鑰強度
                $keyStrength = self::analyzeKeyStrength($privateKey);
                if ($keyStrength['bits'] < 2048) {
                    $issues[] = "⚠️  金鑰強度不足 ({$keyStrength['bits']} bits)，建議至少 2048 bits";
                } else {
                    self::success("✅ 金鑰強度足夠 ({$keyStrength['bits']} bits)");
                }
            }
        }

        // 4. 檢查 JWT 配置完整性
        $requiredConfigs = [
            'JWT_ALGORITHM',
            'JWT_ISSUER',
            'JWT_AUDIENCE',
            'JWT_ACCESS_TOKEN_TTL',
            'JWT_REFRESH_TOKEN_TTL'
        ];

        foreach ($requiredConfigs as $config) {
            if (strpos($envContent, $config . '=') === false) {
                $issues[] = "❌ {$config} 未配置";
            } else {
                self::success("✅ {$config} 已配置");
            }
        }

        // 5. 檢查權限
        if (!is_writable(self::ENV_FILE)) {
            $issues[] = "❌ .env 檔案不可寫入";
        } else {
            self::success("✅ .env 檔案可寫入");
        }

        // 6. 檢查備份目錄
        if (!is_dir(dirname(self::BACKUP_DIR))) {
            $issues[] = "⚠️  儲存目錄不存在，金鑰輪替功能可能無法正常運作";
        }

        // 總結
        self::info("\n📊 健康檢查總結:");
        if (empty($issues)) {
            self::success("🎉 所有檢查項目均通過！JWT 系統狀態良好。");
        } else {
            self::warning("發現 " . count($issues) . " 個問題:");
            foreach ($issues as $issue) {
                self::error("  " . $issue);
            }

            self::info("\n💡 建議修復方案:");
            self::info("  php scripts/jwt-setup.php setup  # 重新設定 JWT");
            self::info("  php scripts/jwt-setup.php rotate # 輪替金鑰");
        }
    }

    /**
     * 開發人員工具 - 產生測試金鑰
     */
    public static function generateDevKeys(): void
    {
        self::info("🛠️  產生開發測試金鑰");

        try {
            $keys = self::generateKeyPair(self::DEFAULT_KEY_SIZE);

            self::info("📋 開發用金鑰 (請勿用於生產環境):");
            self::info("=====================================");
            self::info("Private Key:");
            echo $keys['private'] . "\n\n";
            self::info("Public Key:");
            echo $keys['public'] . "\n\n";

            self::info("📝 .env 格式:");
            self::info("JWT_PRIVATE_KEY=\"" . str_replace("\n", "\\n", $keys['private']) . "\"");
            self::info("JWT_PUBLIC_KEY=\"" . str_replace("\n", "\\n", $keys['public']) . "\"");
        } catch (Exception $e) {
            self::error("產生開發金鑰失敗: " . $e->getMessage());
        }
    }

    /**
     * 生成 RSA 金鑰對
     */
    private static function generateKeyPair(int $keySize = self::DEFAULT_KEY_SIZE): array
    {
        self::info("🔧 生成 RSA 密鑰對 ({$keySize} bits)...");

        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => $keySize,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKeyResource = openssl_pkey_new($config);
        if (!$privateKeyResource) {
            throw new RuntimeException('生成私鑰失敗: ' . openssl_error_string());
        }

        // 導出私鑰
        if (!openssl_pkey_export($privateKeyResource, $privateKey)) {
            throw new RuntimeException('導出私鑰失敗: ' . openssl_error_string());
        }

        // 導出公鑰
        $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);
        if (!$publicKeyDetails) {
            throw new RuntimeException('獲取公鑰失敗: ' . openssl_error_string());
        }

        self::success("✅ RSA 密鑰對生成成功");

        return [
            'private' => $privateKey,
            'public' => $publicKeyDetails['key']
        ];
    }

    /**
     * 驗證金鑰對
     */
    private static function validateKeyPair(string $privateKey, string $publicKey): bool
    {
        self::info("🔍 驗證密鑰對...");

        $testData = 'jwt-setup-validation-test-' . time();
        $signature = '';

        $privateKeyResource = openssl_pkey_get_private($privateKey);
        $publicKeyResource = openssl_pkey_get_public($publicKey);

        if (!$privateKeyResource || !$publicKeyResource) {
            return false;
        }

        // 簽名測試
        if (!openssl_sign($testData, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            return false;
        }

        // 驗證測試
        $result = openssl_verify($testData, $signature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        if ($result === 1) {
            self::success("✅ 密鑰對驗證成功");
        } else {
            self::error("❌ 密鑰對驗證失敗");
        }

        return $result === 1;
    }

    /**
     * 分析金鑰強度
     */
    private static function analyzeKeyStrength(string $privateKey): array
    {
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if (!$privateKeyResource) {
            return ['bits' => 0, 'type' => 'unknown'];
        }

        $details = openssl_pkey_get_details($privateKeyResource);
        return [
            'bits' => $details['bits'] ?? 0,
            'type' => $details['type'] === OPENSSL_KEYTYPE_RSA ? 'RSA' : 'Unknown'
        ];
    }

    /**
     * 備份現有 .env 檔案
     */
    private static function backupEnv(): void
    {
        $backupFile = self::ENV_FILE . '.backup.' . date('Y-m-d_H-i-s');

        if (file_exists(self::ENV_FILE)) {
            copy(self::ENV_FILE, $backupFile);
            self::info("📁 已備份 .env 檔案至: {$backupFile}");
        }
    }

    /**
     * 備份當前金鑰
     */
    private static function backupCurrentKeys(): void
    {
        if (!file_exists(self::ENV_FILE)) {
            return;
        }

        $envContent = file_get_contents(self::ENV_FILE);

        // 提取當前金鑰
        preg_match('/JWT_PRIVATE_KEY="([^"]+)"/', $envContent, $privateMatches);
        preg_match('/JWT_PUBLIC_KEY="([^"]+)"/', $envContent, $publicMatches);

        if (!empty($privateMatches[1]) && !empty($publicMatches[1])) {
            if (!is_dir(self::BACKUP_DIR)) {
                mkdir(self::BACKUP_DIR, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $privateKeyFile = self::BACKUP_DIR . "/private_key_{$timestamp}.pem";
            $publicKeyFile = self::BACKUP_DIR . "/public_key_{$timestamp}.pem";

            // 解碼並儲存金鑰
            file_put_contents($privateKeyFile, str_replace('\\n', "\n", $privateMatches[1]));
            file_put_contents($publicKeyFile, str_replace('\\n', "\n", $publicMatches[1]));

            self::info("🔐 已備份舊金鑰至: " . self::BACKUP_DIR);
        }
    }

    /**
     * 保存金鑰到 .env 檔案
     */
    private static function saveKeysToEnvFile(array $keys, string $envFile): void
    {
        self::info("💾 保存密鑰到環境配置...");

        $envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

        // 準備環境變數格式的密鑰
        $privateKeyEnv = str_replace("\n", "\\n", $keys['private']);
        $publicKeyEnv = str_replace("\n", "\\n", $keys['public']);

        // 移除舊的 JWT 配置
        $envContent = preg_replace('/^JWT_PRIVATE_KEY=.*$/m', '', $envContent);
        $envContent = preg_replace('/^JWT_PUBLIC_KEY=.*$/m', '', $envContent);
        $envContent = preg_replace('/^JWT_ALGORITHM=.*$/m', '', $envContent);
        $envContent = preg_replace('/^JWT_ISSUER=.*$/m', '', $envContent);
        $envContent = preg_replace('/^JWT_AUDIENCE=.*$/m', '', $envContent);
        $envContent = preg_replace('/^JWT_ACCESS_TOKEN_TTL=.*$/m', '', $envContent);
        $envContent = preg_replace('/^JWT_REFRESH_TOKEN_TTL=.*$/m', '', $envContent);

        // 移除重複的 JWT Configuration 註解
        $envContent = preg_replace('/^# JWT Configuration\s*$/m', '', $envContent);

        // 清理多餘的空行
        $envContent = preg_replace('/\n\s*\n/', "\n", $envContent);
        $envContent = trim($envContent);

        // 添加 JWT 配置區塊
        $jwtConfig = "\n\n# JWT Configuration\n";
        $jwtConfig .= "JWT_ALGORITHM=RS256\n";
        $jwtConfig .= "JWT_ISSUER=alleynote-api\n";
        $jwtConfig .= "JWT_AUDIENCE=alleynote-client\n";
        $jwtConfig .= "JWT_ACCESS_TOKEN_TTL=3600\n";
        $jwtConfig .= "JWT_REFRESH_TOKEN_TTL=2592000\n";
        $jwtConfig .= "JWT_PRIVATE_KEY=\"{$privateKeyEnv}\"\n";
        $jwtConfig .= "JWT_PUBLIC_KEY=\"{$publicKeyEnv}\"\n";

        $newContent = $envContent . $jwtConfig;

        if (file_put_contents($envFile, $newContent)) {
            self::success("✅ 環境配置保存成功: $envFile");
        } else {
            self::error("❌ 環境配置保存失敗: $envFile");
            throw new RuntimeException("無法寫入環境配置檔案");
        }
    }

    /**
     * 設定測試環境變數
     */
    private static function setTestEnvironmentVariables(array $keys): void
    {
        self::info("🧪 設定測試環境變數...");

        $_ENV['JWT_ALGORITHM'] = 'RS256';
        $_ENV['JWT_ISSUER'] = 'alleynote-api';
        $_ENV['JWT_AUDIENCE'] = 'alleynote-client';
        $_ENV['JWT_ACCESS_TOKEN_TTL'] = '3600';
        $_ENV['JWT_REFRESH_TOKEN_TTL'] = '2592000';
        $_ENV['JWT_PRIVATE_KEY'] = str_replace("\n", "\\n", $keys['private']);
        $_ENV['JWT_PUBLIC_KEY'] = str_replace("\n", "\\n", $keys['public']);

        self::success("✅ 測試環境變數設定完成");
    }

    /**
     * 測試 JWT 配置
     */
    private static function testJwtConfiguration(): bool
    {
        self::info("🧪 測試 JWT 配置...");

        try {
            $jwtConfig = new \App\Shared\Config\JwtConfig();

            if ($jwtConfig->isConfigured()) {
                self::success("✅ JWT 配置測試成功");

                $summary = $jwtConfig->getConfigSummary();
                self::info("📊 配置摘要:");
                foreach ($summary as $key => $value) {
                    $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                    self::info("  - {$key}: {$displayValue}");
                }
                return true;
            } else {
                self::error("❌ JWT 配置測試失敗");
                return false;
            }
        } catch (Exception $e) {
            self::error("❌ JWT 配置錯誤: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 顯示金鑰資訊
     */
    private static function displayKeysInfo(array $keys): void
    {
        self::info("\n📋 密鑰資訊:");
        self::info("=====================================");
        self::info("私鑰長度: " . strlen($keys['private']) . " bytes");
        self::info("公鑰長度: " . strlen($keys['public']) . " bytes");

        // 顯示密鑰詳細資訊
        $keyInfo = self::analyzeKeyStrength($keys['private']);
        self::info("密鑰位數: " . $keyInfo['bits'] . " bits");
        self::info("密鑰類型: " . $keyInfo['type']);

        self::info("\n🔑 公鑰預覽 (前50字元):");
        echo substr($keys['public'], 0, 50) . "...\n";
        self::info("=====================================\n");
    }

    // 輸出格式化方法
    private static function info(string $message): void
    {
        echo $message . "\n";
    }

    private static function success(string $message): void
    {
        echo "\033[0;32m" . $message . "\033[0m\n";
    }

    private static function warning(string $message): void
    {
        echo "\033[0;33m" . $message . "\033[0m\n";
    }

    private static function error(string $message): void
    {
        echo "\033[0;31m" . $message . "\033[0m\n";
    }
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'setup';

    switch ($command) {
        case 'setup':
            JwtSetupTool::setup();
            break;

        case 'rotate':
            JwtSetupTool::rotateKeys();
            break;

        case 'health':
        case 'check':
            JwtSetupTool::healthCheck();
            break;

        case 'dev-keys':
        case 'dev':
            JwtSetupTool::generateDevKeys();
            break;

        case 'help':
        case '--help':
        case '-h':
        default:
            echo "JWT 設定工具 - 使用說明\n";
            echo "=====================================\n\n";
            echo "用法: php jwt-setup.php [command]\n\n";
            echo "可用指令:\n";
            echo "  setup      - 初始設定 JWT 金鑰 (預設)\n";
            echo "  rotate     - 輪替 JWT 金鑰 (安全更新)\n";
            echo "  health     - 健康檢查 JWT 配置\n";
            echo "  dev-keys   - 產生開發用金鑰 (僅顯示，不儲存)\n";
            echo "  help       - 顯示此說明\n\n";
            echo "範例:\n";
            echo "  php scripts/jwt-setup.php setup    # 初始設定\n";
            echo "  php scripts/jwt-setup.php rotate   # 金鑰輪替\n";
            echo "  php scripts/jwt-setup.php health   # 健康檢查\n\n";
            break;
    }
}
