#!/bin/bash

# 測試分析與修復一站式工具
# 整合測試執行、失敗分析、自動修復的完整工作流程

PROJECT_ROOT="/home/cookey/projects/AlleyNote"
SCRIPT_DIR="$PROJECT_ROOT/scripts"

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 函式：印出彩色訊息
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# 函式：印出分隔線
print_separator() {
    echo -e "${CYAN}=================================================================================${NC}"
}

# 函式：執行並檢查指令
run_command() {
    local cmd=$1
    local description=$2
    
    print_message $YELLOW "➤ $description"
    
    if eval "$cmd"; then
        print_message $GREEN "  ✓ 成功"
        return 0
    else
        print_message $RED "  ✗ 失敗"
        return 1
    fi
}

# 顯示歡迎訊息
print_separator
print_message $CYAN "🔬 AlleyNote 現代化測試分析與修復工具"
print_message $CYAN "   利用最新 PHPUnit 11.5+ 和 Composer 功能進行智能診斷"
print_separator
echo ""

# 步驟 1: 檢查 Docker Compose 服務
print_message $BLUE "步驟 1: 檢查 Docker 服務狀態"
if ! sudo docker compose ps | grep -q "Up"; then
    print_message $YELLOW "啟動 Docker Compose 服務..."
    if ! sudo docker compose up -d; then
        print_message $RED "❌ 無法啟動 Docker 服務"
        exit 1
    fi
    sleep 5
fi
print_message $GREEN "✓ Docker 服務運行中"
echo ""

# 步驟 2: 執行現代化自動修復
print_separator
print_message $BLUE "步驟 2: 執行現代化自動修復程序 (含 Composer 審核)"
print_separator

if sudo docker compose exec -T web php scripts/auto-fix-tool.php; then
    print_message $GREEN "✓ 現代化自動修復完成"
else
    print_message $YELLOW "⚠ 自動修復發現問題，但繼續進行測試分析"
fi

# 執行 Composer 安全性審核
print_message $YELLOW "執行 Composer 安全性審核..."
if sudo docker compose exec -T web composer audit; then
    print_message $GREEN "✓ 沒有發現已知的安全性漏洞"
else
    print_message $YELLOW "⚠ 發現潛在安全性問題，請檢查 composer audit 輸出"
fi

echo ""

# 步驟 3: 執行現代化測試並即時分析
print_separator
print_message $BLUE "步驟 3: 執行現代化測試並進行智能分析 (PHPUnit 11.5+)"
print_separator

# 執行測試並捕獲輸出，使用現代化 PHPUnit 選項
TEST_OUTPUT_FILE="/tmp/alleynote_test_output.txt"
print_message $YELLOW "執行現代化測試套件（含詳細輸出和棄用警告）..."

# 在容器內執行測試，使用現代 PHPUnit 選項
sudo docker compose exec -T web bash -c "cd /var/www/html && ./vendor/bin/phpunit --testdox --display-deprecations --display-all-issues 2>&1" | tee "$TEST_OUTPUT_FILE"

echo ""
print_message $YELLOW "使用現代化分析工具處理測試結果..."
echo ""

# 使用現代化分析工具處理測試輸出
if sudo docker compose exec -T web php scripts/test-failure-analyzer.php --live; then
    print_message $GREEN "✓ 現代化測試分析完成"
else
    print_message $RED "❌ 測試分析失敗"
fi

echo ""

# 步驟 4: 提供後續建議
print_separator
print_message $BLUE "步驟 4: 後續行動建議"
print_separator

# 檢查測試結果
if grep -q "OK (" "$TEST_OUTPUT_FILE"; then
    print_message $GREEN "🎉 所有測試通過！"
    echo ""
    print_message $CYAN "建議下一步："
    echo "  • 繼續開發 JWT 中介軟體 (JwtAuthenticationMiddleware)"
    echo "  • 執行現代化程式碼品質檢查: sudo docker compose exec -T web composer ci"
    echo "  • 執行 PHPStan 靜態分析: sudo docker compose exec -T web ./vendor/bin/phpstan"
    echo "  • 定期執行安全性審核: sudo docker compose exec -T web composer audit"
    echo "  • 提交程式碼變更"
elif grep -q "FAILURES\|ERRORS" "$TEST_OUTPUT_FILE"; then
    print_message $YELLOW "⚠ 測試仍有失敗或錯誤"
    echo ""
    print_message $CYAN "建議修復步驟："
    echo "  1. 檢查上方分析報告中的高優先級問題 (影響分數 >= 8)"
    echo "  2. 優先處理 PHPUnit 棄用警告和 PHP 型別錯誤"
    echo "  3. 手動修復無法自動處理的問題"
    echo "  4. 重新執行本工具: ./scripts/test-analysis-workflow.sh"
    echo ""
    print_message $CYAN "現代化修復指令："
    echo "  • 重新安裝依賴: sudo docker compose exec -T web composer install --optimize-autoloader"
    echo "  • 執行資料庫遷移: sudo docker compose exec -T web php vendor/bin/phinx migrate -e testing"
    echo "  • 驗證 Composer 設定: sudo docker compose exec -T web composer validate --strict"
    echo "  • 執行安全性審核: sudo docker compose exec -T web composer audit"
    echo "  • 檢查 JWT 設定: sudo docker compose exec -T web php -r \"echo json_encode(\\$_ENV, JSON_PRETTY_PRINT);\"" 
else
    print_message $BLUE "ℹ 無法判斷測試狀態，請檢查上方輸出"
fi

echo ""

# 步驟 5: 清理和總結
print_separator
print_message $BLUE "現代化工具執行完成"
print_separator

print_message $CYAN "已生成的報告檔案："
echo "  • 現代化測試輸出: $TEST_OUTPUT_FILE"

if [ -f "$PROJECT_ROOT/test-analysis-report.json" ]; then
    echo "  • JSON 分析報告: $PROJECT_ROOT/test-analysis-report.json"
fi

echo ""
print_message $CYAN "可用的現代化工具指令："
echo "  • 重新執行現代化工具: ./scripts/test-analysis-workflow.sh"
echo "  • 單獨執行現代化測試分析: sudo docker compose exec -T web php scripts/test-failure-analyzer.php --live"
echo "  • 單獨執行現代化自動修復: sudo docker compose exec -T web php scripts/auto-fix-tool.php"
echo "  • 執行 Composer 審核: sudo docker compose exec -T web composer audit"
echo "  • 驗證 Composer 設定: sudo docker compose exec -T web composer validate --strict"

echo ""
print_separator
print_message $GREEN "🚀 現代化分析完成！工具已升級使用最新 PHPUnit 11.5+ 和 Composer 功能。"
print_separator