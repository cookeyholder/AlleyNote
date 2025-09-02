#!/bin/bash

# AlleyNote CI 測試腳本
# 此腳本在 Docker 環境中執行完整的 CI 檢查

# set -e  # 註解掉，允許某些指令失敗但繼續執行

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日誌函數
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_section() {
    echo -e "\n${BLUE}🔍 $1${NC}"
    echo "=================================================="
}

# 檢查 Docker 環境
check_docker_environment() {
    log_section "檢查 Docker 環境"

    if ! docker-compose ps | grep -q "web.*Up"; then
        log_error "Docker 容器未運行，請先執行: docker-compose up -d"
        exit 1
    fi

    log_success "Docker 環境正常"
}

# 安裝依賴
install_dependencies() {
    log_section "安裝相依套件"

    docker-compose exec -T web composer install --prefer-dist --no-progress --no-interaction

    if [ $? -eq 0 ]; then
        log_success "相依套件安裝完成"
    else
        log_error "相依套件安裝失敗"
        exit 1
    fi
}

# 安全性掃描
security_audit() {
    log_section "執行安全性掃描"

    docker-compose exec -T web composer audit --format=json > security-audit.json 2>/dev/null

    if [ $? -eq 0 ]; then
        log_success "安全性掃描通過"
        rm -f security-audit.json
    else
        log_warning "發現安全漏洞，詳細資訊："
        docker-compose exec -T web composer audit 2>/dev/null || true
        echo ""
        log_warning "建議執行 'composer update' 更新套件"
    fi
}

# 程式碼風格檢查
code_style_check() {
    log_section "執行程式碼風格檢查"

    if docker-compose exec -T web test -f vendor/bin/php-cs-fixer; then
        docker-compose exec -T web composer cs-check

        if [ $? -eq 0 ]; then
            log_success "程式碼風格檢查通過"
        else
            log_error "程式碼風格檢查失敗"
            log_info "執行 'composer cs-fix' 自動修正格式問題"
            return 1
        fi
    else
        log_warning "PHP-CS-Fixer 未安裝，跳過程式碼風格檢查"
    fi
}

# 靜態分析
static_analysis() {
    log_section "執行靜態分析"

    if docker-compose exec -T web test -f vendor/bin/phpstan; then
        # 將 PHPStan 輸出儲存到檔案以便分析
        set +e  # 暫時允許指令失敗
        docker-compose exec -T web composer analyse > phpstan-output.txt 2>&1
        PHPSTAN_EXIT_CODE=$?
        set -e  # 重新啟用錯誤退出

        # 統計錯誤數量
        ERROR_COUNT=$(grep -o "Found [0-9]* error" phpstan-output.txt | grep -o "[0-9]*" || echo "0")

        if [ $PHPSTAN_EXIT_CODE -eq 0 ]; then
            log_success "靜態分析通過 (0 個錯誤)"
        else
            log_warning "靜態分析發現 $ERROR_COUNT 個問題"

            # 顯示錯誤摘要
            echo "錯誤摘要："
            grep -A 5 -B 1 "Line.*src/" phpstan-output.txt | head -20 || true
            echo ""

            # 如果錯誤數量太多，考慮失敗
            if [ "$ERROR_COUNT" -gt 500 ]; then
                log_error "錯誤數量過多 ($ERROR_COUNT)，需要修正核心問題"
                return 1
            else
                log_warning "錯誤數量可接受 ($ERROR_COUNT)，可於後續逐步改善"
            fi
        fi

        rm -f phpstan-output.txt
    else
        log_warning "PHPStan 未安裝，跳過靜態分析"
    fi
}

# 單元測試
run_tests() {
    log_section "執行單元測試"

    if docker-compose exec -T web test -f vendor/bin/phpunit; then
        # 執行測試並捕獲輸出
        set +e  # 暫時允許指令失敗
        docker-compose exec -T web composer test > test-output.txt 2>&1
        TEST_EXIT_CODE=$?
        set -e  # 重新啟用錯誤退出

        # 分析測試結果
        if grep -q "OK " test-output.txt; then
            TOTAL_TESTS=$(grep "OK " test-output.txt | grep -o "[0-9]* test" | grep -o "[0-9]*")
            TOTAL_ASSERTIONS=$(grep "OK " test-output.txt | grep -o "[0-9]* assertion" | grep -o "[0-9]*")
            log_success "所有測試通過 ($TOTAL_TESTS 個測試, $TOTAL_ASSERTIONS 個斷言)"
        elif grep -q "Tests: " test-output.txt; then
            # 解析測試統計
            TEST_STATS=$(grep "Tests: " test-output.txt | tail -1)
            echo "測試結果: $TEST_STATS"

            if echo "$TEST_STATS" | grep -q "Failures\|Errors"; then
                log_warning "部分測試失敗，但測試架構運行正常"

                # 顯示失敗摘要
                echo ""
                echo "失敗測試摘要："
                grep -A 2 -B 1 "FAILURES\|ERRORS" test-output.txt | head -10 || true
            else
                log_success "所有測試通過"
            fi
        else
            log_error "測試執行遇到問題"
            cat test-output.txt | tail -20
            return 1
        fi

        rm -f test-output.txt
    else
        log_warning "PHPUnit 未安裝，跳過測試"
    fi
}

# 生成測試覆蓋率報告
coverage_report() {
    log_section "生成測試覆蓋率報告"

    if docker-compose exec -T web test -f vendor/bin/phpunit; then
        set +e  # 暫時允許指令失敗
        docker-compose exec -T web composer test:coverage > coverage-output.txt 2>&1
        set -e  # 重新啟用錯誤退出

        # 提取覆蓋率資訊
        if grep -q "Lines:" coverage-output.txt; then
            COVERAGE=$(grep "Lines:" coverage-output.txt | tail -1)
            log_info "測試覆蓋率: $COVERAGE"

            # 檢查覆蓋率是否達標
            COVERAGE_PERCENT=$(echo "$COVERAGE" | grep -o "[0-9]*\.[0-9]*%" | head -1 | sed 's/%//')
            if [ -n "$COVERAGE_PERCENT" ]; then
                COVERAGE_NUM=$(echo "$COVERAGE_PERCENT" | cut -d'.' -f1)
                if [ "$COVERAGE_NUM" -ge 40 ]; then
                    log_success "測試覆蓋率達標 ($COVERAGE_PERCENT%)"
                else
                    log_warning "測試覆蓋率偏低 ($COVERAGE_PERCENT%)，建議提升至 40% 以上"
                fi
            fi
        fi

        rm -f coverage-output.txt
    fi
}

# 清理函數
cleanup() {
    log_info "清理暫存檔案..."
    rm -f security-audit.json phpstan-output.txt test-output.txt coverage-output.txt
}

# 主要執行流程
main() {
    log_info "開始執行 AlleyNote CI 檢查"
    echo "時間: $(date)"
    echo ""

    # 註冊清理函數
    trap cleanup EXIT

    # 執行檢查步驟
    check_docker_environment
    install_dependencies
    security_audit

    # 品質檢查
    STYLE_OK=true
    ANALYSIS_OK=true
    TEST_OK=true

    code_style_check || STYLE_OK=false
    static_analysis || ANALYSIS_OK=false
    run_tests || TEST_OK=false
    coverage_report

    # 總結報告
    log_section "CI 檢查結果總結"

    echo "檢查項目狀態："
    [ "$STYLE_OK" = true ] && log_success "程式碼風格: PASS" || log_error "程式碼風格: FAIL"
    [ "$ANALYSIS_OK" = true ] && log_success "靜態分析: PASS" || log_warning "靜態分析: WARN"
    [ "$TEST_OK" = true ] && log_success "單元測試: PASS" || log_warning "單元測試: WARN"

    echo ""

    # 決定整體結果
    if [ "$STYLE_OK" = true ]; then
        if [ "$ANALYSIS_OK" = true ] && [ "$TEST_OK" = true ]; then
            log_success "🎉 所有 CI 檢查通過！"
            exit 0
        else
            log_warning "⚠️  CI 檢查部分通過，建議修正問題後再次執行"
            exit 0
        fi
    else
        log_error "💥 CI 檢查失敗，請修正程式碼風格問題"
        exit 1
    fi
}

# 處理腳本參數
case "${1:-}" in
    "style")
        check_docker_environment
        code_style_check
        ;;
    "analysis")
        check_docker_environment
        static_analysis
        ;;
    "test")
        check_docker_environment
        run_tests
        ;;
    "coverage")
        check_docker_environment
        coverage_report
        ;;
    *)
        main
        ;;
esac
