<?php

declare(strict_types=1);

/**
 * 登入功能診斷腳本
 * 直接測試登入邏輯並顯示詳細錯誤
 */

// 啟用錯誤顯示
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "=== AlleyNote 登入診斷腳本 ===\n\n";

// 設定自動載入
require_once __DIR__ . '/vendor/autoload.php';

try {
    echo "1. 檢查資料庫連線...\n";
    $dbPath = __DIR__ . '/database/alleynote.sqlite3';
    if (!file_exists($dbPath)) {
        die("錯誤：資料庫檔案不存在：$dbPath\n");
    }
    echo "   ✓ 資料庫檔案存在\n";
    
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✓ 資料庫連線成功\n\n";
    
    echo "2. 檢查使用者...\n";
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("錯誤：找不到使用者 admin@example.com\n");
    }
    echo "   ✓ 使用者存在\n";
    echo "   - ID: {$user['id']}\n";
    echo "   - 使用者名稱: {$user['username']}\n";
    echo "   - Email: {$user['email']}\n";
    echo "   - Password Hash: " . substr($user['password_hash'], 0, 20) . "...\n\n";
    
    echo "3. 測試密碼驗證...\n";
    $password = 'password';
    $isValid = password_verify($password, $user['password_hash']);
    
    if ($isValid) {
        echo "   ✓ 密碼驗證成功\n\n";
    } else {
        echo "   ✗ 密碼驗證失敗\n\n";
        die("錯誤：密碼不正確\n");
    }
    
    echo "4. 檢查 JWT 配置...\n";
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        echo "   ✓ .env 檔案存在\n";
        $envContent = file_get_contents($envFile);
        if (strpos($envContent, 'JWT_ALGORITHM') !== false) {
            echo "   ✓ JWT 配置存在\n";
        } else {
            echo "   ⚠ .env 中未找到 JWT 配置\n";
        }
    } else {
        echo "   ⚠ .env 檔案不存在，使用環境變數\n";
    }
    
    // 檢查環境變數
    $jwtAlgo = getenv('JWT_ALGORITHM');
    if ($jwtAlgo) {
        echo "   ✓ JWT_ALGORITHM: $jwtAlgo\n";
    } else {
        echo "   ⚠ JWT_ALGORITHM 環境變數未設定\n";
    }
    
    $jwtPrivateKey = getenv('JWT_PRIVATE_KEY');
    if ($jwtPrivateKey) {
        echo "   ✓ JWT_PRIVATE_KEY 已設定\n";
    } else {
        echo "   ⚠ JWT_PRIVATE_KEY 環境變數未設定\n";
    }
    
    echo "\n5. 嘗試載入應用程式...\n";
    
    // 檢查 Application 類別
    if (class_exists('App\Application')) {
        echo "   ✓ Application 類別存在\n";
    } else {
        echo "   ✗ Application 類別不存在\n";
    }
    
    // 檢查 AuthService
    if (class_exists('App\Domains\Auth\Services\AuthService')) {
        echo "   ✓ AuthService 類別存在\n";
    } else {
        echo "   ✗ AuthService 類別不存在\n";
    }
    
    echo "\n6. 模擬登入請求...\n";
    echo "   請求資料：\n";
    echo "   - email: admin@example.com\n";
    echo "   - password: password\n\n";
    
    // 嘗試載入並執行登入
    try {
        // 這裡需要完整的 DI 容器設置才能執行
        echo "   ⚠ 需要完整的應用程式上下文才能測試登入邏輯\n";
        echo "   ⚠ 建議：檢查應用程式日誌以獲取詳細錯誤\n";
    } catch (Exception $e) {
        echo "   ✗ 錯誤：" . $e->getMessage() . "\n";
        echo "   堆疊追蹤：\n";
        echo $e->getTraceAsString() . "\n";
    }
    
    echo "\n=== 診斷完成 ===\n";
    echo "\n基礎檢查都通過了，問題可能在於：\n";
    echo "1. JWT Token 生成邏輯\n";
    echo "2. 服務容器配置\n";
    echo "3. 資料庫架構與 ORM 映射\n";
    echo "4. 中間件或路由配置\n";
    echo "\n建議下一步：\n";
    echo "- 檢查 storage/logs/ 目錄下的日誌檔案\n";
    echo "- 啟用 PHP 錯誤日誌記錄\n";
    echo "- 檢查 Docker 容器的 PHP-FPM 日誌\n";
    
} catch (Exception $e) {
    echo "\n❌ 發生錯誤：\n";
    echo "錯誤訊息：" . $e->getMessage() . "\n";
    echo "檔案：" . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n堆疊追蹤：\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
