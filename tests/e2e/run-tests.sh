#!/bin/bash

# AlleyNote E2E 測試執行腳本

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 函數：印出帶顏色的訊息
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# 檢查 Docker 是否執行
check_docker() {
    print_info "檢查 Docker 狀態..."
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker 未執行，請先啟動 Docker"
        exit 1
    fi
    print_success "Docker 執行中"
}

# 檢查應用程式是否執行
check_app() {
    print_info "檢查應用程式狀態..."

    if curl -s http://localhost:8081 > /dev/null; then
        print_success "應用程式執行中"
        return 0
    else
        print_warning "應用程式未執行"
        return 1
    fi
}

# 啟動應用程式
start_app() {
    print_info "啟動應用程式..."
    cd ../.. # 回到專案根目錄
    docker compose up -d

    # 等待服務啟動
    print_info "等待服務啟動（最多 60 秒）..."
    for i in {1..60}; do
        if curl -s http://localhost:8081 > /dev/null; then
            print_success "應用程式啟動成功"
            cd - > /dev/null
            return 0
        fi
        sleep 1
    done

    print_error "應用程式啟動逾時"
    cd - > /dev/null
    exit 1
}

# 安裝依賴
install_deps() {
    print_info "檢查依賴..."

    if [ ! -d "node_modules" ]; then
        print_info "安裝 npm 依賴..."
        npm install
        print_success "依賴安裝完成"
    else
        print_success "依賴已安裝"
    fi

    print_info "安裝/確認 Playwright Chromium 瀏覽器..."
    npx playwright install chromium > /dev/null 2>&1
    print_success "Playwright Chromium 已就緒"
}

# 準備 JWT 測試金鑰
ensure_jwt_keys() {
    print_info "準備 JWT 測試金鑰..."
    ./tests/fixtures/generate-jwt-keys.sh
    print_success "JWT 測試金鑰已就緒"
}

# 執行測試
run_tests() {
    local mode="${1:-headless}"

    case $mode in
        headed)
            print_info "執行測試（有頭模式）..."
            npm run test:headed
            ;;
        ui)
            print_info "啟動 UI 模式..."
            npm run test:ui
            ;;
        debug)
            print_info "啟動除錯模式..."
            npm run test:debug
            ;;
        *)
            print_info "執行測試（無頭模式）..."
            npm test
            ;;
    esac
}

# 顯示測試報告
show_report() {
    if [ -d "playwright-report" ]; then
        print_info "開啟測試報告..."
        npm run test:report
    else
        print_warning "未找到測試報告"
    fi
}

# 清理
cleanup() {
    print_info "清理測試資料..."
    rm -rf test-results/
    rm -f test-results.json
    print_success "清理完成"
}

# 主要流程
main() {
    print_info "🚀 AlleyNote E2E 測試執行器"
    echo ""

    # 解析參數
    MODE="${1:-headless}"
    SKIP_SETUP="${2:-false}"

    # 檢查 Docker
    check_docker

    # 檢查並啟動應用程式
    if [ "$SKIP_SETUP" != "skip-setup" ]; then
        if ! check_app; then
            start_app
        fi

        # 安裝依賴
        install_deps

        # 準備測試 fixture
        ensure_jwt_keys
    fi

    # 執行測試
    echo ""
    run_tests "$MODE"

    # 測試結果
    echo ""
    if [ $? -eq 0 ]; then
        print_success "所有測試通過！"

        # 詢問是否查看報告
        read -p "是否開啟測試報告？(y/N) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            show_report
        fi
    else
        print_error "測試失敗"
        show_report
        exit 1
    fi
}

# 顯示使用說明
show_help() {
    echo "使用方式: $0 [模式] [選項]"
    echo ""
    echo "模式:"
    echo "  headless    無頭模式（預設）"
    echo "  headed      有頭模式（顯示瀏覽器）"
    echo "  ui          UI 模式（互動式）"
    echo "  debug       除錯模式"
    echo ""
    echo "選項:"
    echo "  skip-setup  跳過環境設定和依賴安裝"
    echo ""
    echo "範例:"
    echo "  $0                    # 執行無頭測試"
    echo "  $0 headed             # 執行有頭測試"
    echo "  $0 ui                 # 開啟 UI 模式"
    echo "  $0 headless skip-setup  # 快速執行（跳過設定）"
    echo ""
    echo "其他指令:"
    echo "  $0 clean              # 清理測試資料"
    echo "  $0 report             # 顯示測試報告"
    echo "  $0 help               # 顯示此說明"
}

# 處理指令
case "$1" in
    clean)
        cleanup
        ;;
    report)
        show_report
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        main "$@"
        ;;
esac
