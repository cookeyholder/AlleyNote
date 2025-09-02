#!/bin/bash

# =============================================================================
# CI/CD API 文件自動生成和驗證腳本
# =============================================================================
#
# 用途：
# 1. 在 CI/CD 流程中自動生成 OpenAPI 文件
# 2. 驗證 API 文件的正確性和完整性
# 3. 檢查文件版本一致性
# 4. 生成文件變更報告
#
# 使用方式：
# ./scripts/ci-generate-docs.sh [options]
#
# 選項：
#   --env=ENV          指定環境 (development|staging|production)
#   --output=DIR       指定輸出目錄
#   --validate         啟用驗證模式
#   --report           生成變更報告
#   --help             顯示幫助資訊
# =============================================================================

set -euo pipefail

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 預設設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DEFAULT_OUTPUT_DIR="${PROJECT_ROOT}/public"
DEFAULT_ENV="development"

# 命令列參數
ENV="$DEFAULT_ENV"
OUTPUT_DIR="$DEFAULT_OUTPUT_DIR"
VALIDATE_MODE=false
GENERATE_REPORT=false
HELP_MODE=false

# 解析命令列參數
parse_args() {
    for arg in "$@"; do
        case $arg in
            --env=*)
                ENV="${arg#*=}"
                shift
                ;;
            --output=*)
                OUTPUT_DIR="${arg#*=}"
                shift
                ;;
            --validate)
                VALIDATE_MODE=true
                shift
                ;;
            --report)
                GENERATE_REPORT=true
                shift
                ;;
            --help|-h)
                HELP_MODE=true
                shift
                ;;
            *)
                echo -e "${RED}錯誤：未知參數 $arg${NC}"
                exit 1
                ;;
        esac
    done
}

# 顯示幫助資訊
show_help() {
    cat << EOF
AlleyNote API 文件自動生成工具

用途：
  在 CI/CD 流程中自動生成和驗證 OpenAPI 文件

使用方式：
  $0 [選項]

選項：
  --env=ENV          指定環境 (development|staging|production)
                     預設：development
  --output=DIR       指定輸出目錄
                     預設：${DEFAULT_OUTPUT_DIR}
  --validate         啟用嚴格驗證模式
  --report           生成文件變更報告
  --help, -h         顯示此幫助資訊

範例：
  $0                                    # 使用預設設定
  $0 --env=production --validate        # 生產環境並啟用驗證
  $0 --output=/tmp/docs --report        # 自訂輸出目錄並生成報告

EOF
}

# 記錄函數
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 檢查相依性
check_dependencies() {
    log_info "檢查相依性..."

    local missing_deps=()

    # 檢查 PHP
    if ! command -v php &> /dev/null; then
        missing_deps+=("php")
    fi

    # 檢查 Composer
    if ! command -v composer &> /dev/null; then
        missing_deps+=("composer")
    fi

    # 檢查 jq (用於 JSON 處理)
    if ! command -v jq &> /dev/null; then
        missing_deps+=("jq")
    fi

    if [ ${#missing_deps[@]} -ne 0 ]; then
        log_error "缺少必要相依性: ${missing_deps[*]}"
        log_error "請安裝缺少的相依性後重新執行"
        exit 1
    fi

    log_success "相依性檢查完成"
}

# 檢查專案結構
check_project_structure() {
    log_info "檢查專案結構..."

    local required_dirs=(
        "src/Controllers"
        "src/Schemas"
        "src/OpenApi"
        "config"
    )

    local required_files=(
        "composer.json"
        "config/swagger.php"
        "scripts/generate-swagger-docs.php"
    )

    # 檢查目錄
    for dir in "${required_dirs[@]}"; do
        if [ ! -d "${PROJECT_ROOT}/${dir}" ]; then
            log_error "缺少必要目錄: ${dir}"
            exit 1
        fi
    done

    # 檢查檔案
    for file in "${required_files[@]}"; do
        if [ ! -f "${PROJECT_ROOT}/${file}" ]; then
            log_error "缺少必要檔案: ${file}"
            exit 1
        fi
    done

    log_success "專案結構檢查完成"
}

# 設定環境變數
setup_environment() {
    log_info "設定環境變數 (${ENV})..."

    export APP_ENV="$ENV"

    case "$ENV" in
        production)
            export API_BASE_URL="https://api.alleynote.example.com"
            export SWAGGER_VALIDATOR_URL="https://validator.swagger.io/validator"
            ;;
        staging)
            export API_BASE_URL="https://staging-api.alleynote.example.com"
            export SWAGGER_VALIDATOR_URL="https://validator.swagger.io/validator"
            ;;
        development|*)
            export API_BASE_URL="http://localhost"
            export SWAGGER_VALIDATOR_URL="null"
            ;;
    esac

    log_success "環境設定完成: $ENV"
}

# 更新 Composer 相依性
update_dependencies() {
    log_info "更新 Composer 相依性..."

    cd "$PROJECT_ROOT"

    if [ "$ENV" = "production" ]; then
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        composer install --no-interaction
    fi

    log_success "相依性更新完成"
}

# 生成 API 文件
generate_docs() {
    log_info "生成 API 文件..."

    cd "$PROJECT_ROOT"

    # 確保輸出目錄存在
    mkdir -p "$OUTPUT_DIR"

    # 執行文件生成
    if php scripts/generate-swagger-docs.php --output="$OUTPUT_DIR" --env="$ENV"; then
        log_success "API 文件生成完成"
    else
        log_error "API 文件生成失敗"
        return 1
    fi

    # 檢查生成的檔案
    local json_file="${OUTPUT_DIR}/api-docs.json"
    local yaml_file="${OUTPUT_DIR}/api-docs.yaml"

    if [ ! -f "$json_file" ] || [ ! -f "$yaml_file" ]; then
        log_error "文件檔案生成失敗"
        return 1
    fi

    # 顯示檔案資訊
    log_info "文件檔案資訊:"
    echo "  JSON: $(du -h "$json_file" | cut -f1) ($(wc -l < "$json_file") 行)"
    echo "  YAML: $(du -h "$yaml_file" | cut -f1) ($(wc -l < "$yaml_file") 行)"
}

# 驗證 API 文件
validate_docs() {
    log_info "驗證 API 文件..."

    local json_file="${OUTPUT_DIR}/api-docs.json"
    local validation_errors=0

    # 檢查 JSON 語法
    if ! jq empty "$json_file" 2>/dev/null; then
        log_error "JSON 語法錯誤"
        ((validation_errors++))
    else
        log_success "JSON 語法驗證通過"
    fi

    # 檢查必要欄位
    local required_fields=("openapi" "info" "paths")
    for field in "${required_fields[@]}"; do
        if ! jq -e ".$field" "$json_file" >/dev/null 2>&1; then
            log_error "缺少必要欄位: $field"
            ((validation_errors++))
        fi
    done

    # 檢查版本資訊
    local api_version
    api_version=$(jq -r '.info.version' "$json_file")
    if [ "$api_version" = "null" ] || [ -z "$api_version" ]; then
        log_error "API 版本資訊缺失"
        ((validation_errors++))
    else
        log_info "API 版本: $api_version"
    fi

    # 檢查路徑數量
    local path_count
    path_count=$(jq '.paths | length' "$json_file")
    if [ "$path_count" -lt 1 ]; then
        log_error "API 路徑數量過少: $path_count"
        ((validation_errors++))
    else
        log_info "API 路徑數量: $path_count"
    fi

    # 檢查 Schema 數量
    local schema_count
    schema_count=$(jq '.components.schemas // {} | length' "$json_file")
    log_info "Schema 數量: $schema_count"

    # 驗證結果
    if [ $validation_errors -eq 0 ]; then
        log_success "文件驗證通過"
        return 0
    else
        log_error "文件驗證失敗 ($validation_errors 個錯誤)"
        return 1
    fi
}

# 生成變更報告
generate_change_report() {
    log_info "生成變更報告..."

    local json_file="${OUTPUT_DIR}/api-docs.json"
    local report_file="${OUTPUT_DIR}/api-change-report.md"
    local previous_file="${OUTPUT_DIR}/api-docs.prev.json"

    # 如果存在先前版本，進行比較
    if [ -f "$previous_file" ]; then
        log_info "比較 API 變更..."

        # 創建報告標頭
        cat > "$report_file" << EOF
# API 變更報告

**生成時間**: $(date '+%Y-%m-%d %H:%M:%S %Z')
**環境**: $ENV
**版本**: $(jq -r '.info.version' "$json_file")

## 變更摘要

EOF

        # 比較路徑數量
        local old_paths new_paths
        old_paths=$(jq '.paths | length' "$previous_file")
        new_paths=$(jq '.paths | length' "$json_file")

        echo "- **API 路徑**: $old_paths → $new_paths" >> "$report_file"

        # 比較 Schema 數量
        local old_schemas new_schemas
        old_schemas=$(jq '.components.schemas // {} | length' "$previous_file")
        new_schemas=$(jq '.components.schemas // {} | length' "$json_file")

        echo "- **Schema 數量**: $old_schemas → $new_schemas" >> "$report_file"

        # 新增的路徑
        local new_paths_list
        new_paths_list=$(jq -r --slurpfile old "$previous_file" '.paths | keys[] as $k | select($old[0].paths | has($k) | not) | $k' "$json_file")

        if [ -n "$new_paths_list" ]; then
            echo -e "\n## 新增的 API 路徑\n" >> "$report_file"
            echo "$new_paths_list" | while read -r path; do
                echo "- \`$path\`" >> "$report_file"
            done
        fi

        # 移除的路徑
        local removed_paths_list
        removed_paths_list=$(jq -r --slurpfile new "$json_file" '.paths | keys[] as $k | select($new[0].paths | has($k) | not) | $k' "$previous_file")

        if [ -n "$removed_paths_list" ]; then
            echo -e "\n## 移除的 API 路徑\n" >> "$report_file"
            echo "$removed_paths_list" | while read -r path; do
                echo "- \`$path\`" >> "$report_file"
            done
        fi

        echo -e "\n## 詳細資訊\n" >> "$report_file"
        echo "請查看完整的 API 文件以了解詳細變更。" >> "$report_file"

        log_success "變更報告已生成: $report_file"
    else
        log_warning "沒有找到先前版本，跳過變更比較"
    fi

    # 備份目前版本
    cp "$json_file" "$previous_file"
}

# 清理函數
cleanup() {
    log_info "執行清理..."
    # 在這裡可以添加清理邏輯
}

# 主要執行函數
main() {
    echo "=================================="
    echo "AlleyNote API 文件自動生成工具"
    echo "=================================="
    echo

    # 註冊清理函數
    trap cleanup EXIT

    # 解析參數
    parse_args "$@"

    # 顯示幫助
    if [ "$HELP_MODE" = true ]; then
        show_help
        exit 0
    fi

    log_info "開始執行 API 文件生成流程..."
    log_info "環境: $ENV"
    log_info "輸出目錄: $OUTPUT_DIR"

    # 執行檢查和生成流程
    check_dependencies
    check_project_structure
    setup_environment
    update_dependencies

    if ! generate_docs; then
        log_error "文件生成失敗"
        exit 1
    fi

    # 驗證模式
    if [ "$VALIDATE_MODE" = true ]; then
        if ! validate_docs; then
            log_error "文件驗證失敗"
            exit 1
        fi
    fi

    # 生成報告
    if [ "$GENERATE_REPORT" = true ]; then
        generate_change_report
    fi

    log_success "所有任務完成！"
    echo
    echo "生成的文件："
    echo "  📄 JSON: ${OUTPUT_DIR}/api-docs.json"
    echo "  📄 YAML: ${OUTPUT_DIR}/api-docs.yaml"

    if [ "$GENERATE_REPORT" = true ] && [ -f "${OUTPUT_DIR}/api-change-report.md" ]; then
        echo "  📊 報告: ${OUTPUT_DIR}/api-change-report.md"
    fi

    echo
    echo "下一步："
    echo "  🌐 查看 Swagger UI: http://localhost/api/docs/ui"
    echo "  📖 查看 API 文件: http://localhost/api/docs"
}

# 如果直接執行此腳本，則執行主函數
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
