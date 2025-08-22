#!/bin/bash

# AlleyNote 安全測試自動化腳本

set -e

echo "======================================="
echo "AlleyNote 安全測試自動化執行"
echo "======================================="

# 檢查 Docker 是否運行
if ! docker info > /dev/null 2>&1; then
    echo "錯誤: Docker 未運行，請啟動 Docker 服務"
    exit 1
fi

# 檢查 docker-compose
if ! command -v docker-compose &> /dev/null; then
    echo "錯誤: 找不到 docker-compose 命令"
    exit 1
fi

# 確保容器運行
echo "正在啟動 Docker 容器..."
docker-compose up -d

# 等待容器就緒
echo "等待容器啟動完成..."
sleep 5

# 檢查容器狀態
echo "檢查容器狀態..."
docker-compose ps

# 設定測試環境
echo "設定測試環境..."
docker-compose exec -T php mkdir -p storage/logs
docker-compose exec -T php mkdir -p storage/app
docker-compose exec -T php mkdir -p storage/backups

# 設定檔案權限
echo "設定檔案權限..."
docker-compose exec -T php chmod 755 storage
docker-compose exec -T php chmod 755 storage/logs
docker-compose exec -T php chmod 755 storage/app
docker-compose exec -T php chmod 755 storage/backups

# 執行資料庫遷移
echo "執行資料庫遷移..."
docker-compose exec -T php php -r "
require_once 'vendor/autoload.php';
use App\Database\DatabaseConnection;

try {
    \$db = DatabaseConnection::getInstance();
    
    // 執行基本資料表遷移
    \$db->exec('
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            author_id INTEGER,
            status TEXT DEFAULT \"draft\",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // 執行 RBAC 遷移
    if (file_exists('database/migrations/002_create_rbac_tables.sql')) {
        \$sql = file_get_contents('database/migrations/002_create_rbac_tables.sql');
        \$db->exec(\$sql);
    }
    
    echo \"資料庫遷移完成\n\";
} catch (Exception \$e) {
    echo \"資料庫遷移失敗: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"

# 執行基本系統檢查
echo "執行基本系統檢查..."
docker-compose exec -T php php -r "
echo 'PHP 版本: ' . PHP_VERSION . \"\n\";
echo 'SQLite 支援: ' . (extension_loaded('sqlite3') ? '是' : '否') . \"\n\";
echo 'PDO 支援: ' . (extension_loaded('pdo') ? '是' : '否') . \"\n\";
echo 'JSON 支援: ' . (extension_loaded('json') ? '是' : '否') . \"\n\";
echo 'OpenSSL 支援: ' . (extension_loaded('openssl') ? '是' : '否') . \"\n\";
"

# 執行完整安全測試
echo "======================================="
echo "執行完整安全測試"
echo "======================================="

# 文字格式輸出
echo "1. 執行安全測試 (文字格式)..."
docker-compose exec -T php php tests/security_test_runner.php --verbose

# JSON 格式輸出
echo -e "\n2. 產生 JSON 報告..."
docker-compose exec -T php php tests/security_test_runner.php --format=json > security_report.json
echo "JSON 報告已儲存至 security_report.json"

# XML 格式輸出
echo -e "\n3. 產生 XML 報告..."
docker-compose exec -T php php tests/security_test_runner.php --format=xml > security_report.xml
echo "XML 報告已儲存至 security_report.xml"

# 執行各別類別測試
echo -e "\n======================================="
echo "執行各別類別安全測試"
echo "======================================="

categories=("session" "authorization" "file" "headers" "errors" "password" "secrets" "system")

for category in "${categories[@]}"; do
    echo -e "\n--- $category 測試 ---"
    if docker-compose exec -T php php tests/security_test_runner.php --category="$category"; then
        echo "✓ $category 測試完成"
    else
        echo "✗ $category 測試失敗"
    fi
done

# 產生安全檢查清單
echo -e "\n======================================="
echo "產生安全檢查清單"
echo "======================================="

docker-compose exec -T php php -r "
require_once 'vendor/autoload.php';
use App\Services\Security\SecretsManager;

\$secretsManager = new SecretsManager();

echo \"環境設定檢查:\n\";
echo \"================\n\";

// 檢查 .env 檔案
if (file_exists('.env')) {
    \$issues = \$secretsManager->validateEnvFile();
    if (empty(\$issues)) {
        echo \"✓ .env 檔案驗證通過\n\";
    } else {
        echo \"⚠ .env 檔案發現問題:\n\";
        foreach (\$issues as \$issue) {
            echo \"  - \$issue\n\";
        }
    }
} else {
    echo \"⚠ .env 檔案不存在\n\";
}

// 檢查關鍵目錄
\$directories = [
    'storage' => 'storage',
    'public' => 'public',
    'vendor' => 'vendor'
];

echo \"\n目錄權限檢查:\n\";
echo \"================\n\";

foreach (\$directories as \$name => \$path) {
    if (is_dir(\$path)) {
        \$perms = fileperms(\$path) & 0777;
        if (\$perms <= 0755) {
            echo \"✓ \$name 目錄權限正確 (\" . sprintf('%o', \$perms) . \")\n\";
        } else {
            echo \"⚠ \$name 目錄權限過於寬鬆 (\" . sprintf('%o', \$perms) . \")\n\";
        }
    } else {
        echo \"✗ \$name 目錄不存在\n\";
    }
}
"

# 清理測試資料
echo -e "\n======================================="
echo "清理測試資料"
echo "======================================="

docker-compose exec -T php php -r "
// 清理測試 session 檔案
if (is_dir('/tmp')) {
    \$files = glob('/tmp/sess_*');
    foreach (\$files as \$file) {
        if (is_file(\$file)) {
            unlink(\$file);
        }
    }
}

echo \"測試資料清理完成\n\";
"

# 顯示測試總結
echo -e "\n======================================="
echo "測試總結"
echo "======================================="

echo "✓ 安全測試執行完成"
echo "✓ 報告檔案已產生："
echo "  - security_report.json"
echo "  - security_report.xml"
echo ""
echo "建議："
echo "1. 檢查生成的報告檔案以了解詳細結果"
echo "2. 修正任何發現的安全問題"
echo "3. 定期執行此測試以確保持續安全性"
echo "4. 在部署到正式環境前確保所有測試通過"
echo ""
echo "如需詳細資訊，請參閱 SECURITY_TESTING.md"

exit 0
