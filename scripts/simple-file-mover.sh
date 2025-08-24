#!/bin/zsh

# 簡化的 DDD 檔案移動腳本
# 使用簡單的文字檔案格式來移動檔案

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 全域變數
DRY_RUN=false
EXECUTE=false
LOG_FILE="logs/simple-file-mover.log"
MOVE_LIST_FILE="scripts/file-move-list.txt"
MOVED_FILES=()
FAILED_MOVES=()

# 日誌函數
log() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $message" | tee -a "$LOG_FILE"
}

error() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${RED}[$timestamp] ERROR: $message${NC}" | tee -a "$LOG_FILE"
}

success() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${GREEN}[$timestamp] SUCCESS: $message${NC}" | tee -a "$LOG_FILE"
}

info() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${BLUE}[$timestamp] INFO: $message${NC}" | tee -a "$LOG_FILE"
}

# 解析命令列參數
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --execute)
                EXECUTE=true
                shift
                ;;
            -h|--help)
                show_help
                exit 0
                ;;
            *)
                error "未知參數: $1"
                show_help
                exit 1
                ;;
        esac
    done

    if [[ "$DRY_RUN" == "false" && "$EXECUTE" == "false" ]]; then
        error "必須指定 --dry-run 或 --execute 模式"
        show_help
        exit 1
    fi
}

# 顯示幫助
show_help() {
    echo "簡化的 DDD 檔案移動腳本"
    echo ""
    echo "使用方式:"
    echo "  $0 --dry-run     預覽模式"
    echo "  $0 --execute     執行模式"
    echo "  $0 --help        顯示此幫助"
}

# 檢查前置條件
check_prerequisites() {
    info "檢查前置條件..."

    if [[ ! -f "$MOVE_LIST_FILE" ]]; then
        error "找不到移動列表檔案: $MOVE_LIST_FILE"
        exit 1
    fi

    mkdir -p "$(dirname "$LOG_FILE")"
    success "前置條件檢查完成"
}

# 讀取移動列表
read_move_list() {
    info "讀取檔案移動列表..."

    local count=0
    while IFS='|' read -r source dest; do
        # 跳過註解和空行
        if [[ "$source" =~ ^#.* ]] || [[ -z "$source" ]]; then
            continue
        fi

        # 移除前後空白
        source=$(echo "$source" | xargs)
        dest=$(echo "$dest" | xargs)

        if [[ -n "$source" && -n "$dest" ]]; then
            FILE_MAPPINGS+=("$source|$dest")
            ((count++))
        fi
    done < "$MOVE_LIST_FILE"

    info "讀取完成，共 $count 個移動項目"
}

# 驗證檔案
validate_files() {
    info "驗證檔案..."

    local missing=0
    for mapping in "${FILE_MAPPINGS[@]}"; do
        IFS='|' read -r source dest <<< "$mapping"

        if [[ ! -f "$source" ]]; then
            error "檔案不存在: $source"
            ((missing++))
        fi
    done

    if [[ $missing -gt 0 ]]; then
        error "發現 $missing 個缺失檔案"
        exit 1
    fi

    success "檔案驗證通過"
}

# 創建目錄
create_directories() {
    info "創建目標目錄..."

    local directories=()
    for mapping in "${FILE_MAPPINGS[@]}"; do
        IFS='|' read -r source dest <<< "$mapping"
        local dest_dir=$(dirname "$dest")

        if [[ ! " ${directories[@]} " =~ " ${dest_dir} " ]]; then
            directories+=("$dest_dir")
        fi
    done

    for dir in "${directories[@]}"; do
        if [[ "$DRY_RUN" == "true" ]]; then
            info "[DRY-RUN] 將創建目錄: $dir"
        else
            if [[ ! -d "$dir" ]]; then
                mkdir -p "$dir"
                info "創建目錄: $dir"
            fi
        fi
    done

    success "目錄創建完成"
}

# 移動檔案
move_files() {
    info "開始移動檔案..."

    local total=${#FILE_MAPPINGS[@]}
    local current=0

    for mapping in "${FILE_MAPPINGS[@]}"; do
        ((current++))
        IFS='|' read -r source dest <<< "$mapping"

        info "[$current/$total] 處理: $source -> $dest"

        if [[ "$DRY_RUN" == "true" ]]; then
            info "[DRY-RUN] 將移動: $source -> $dest"
        else
            # 確保目標目錄存在
            mkdir -p "$(dirname "$dest")"

            # 使用 Git mv 移動檔案
            if git mv "$source" "$dest" 2>/dev/null; then
                success "已移動: $source -> $dest"
                MOVED_FILES+=("$source -> $dest")
            else
                # 如果 git mv 失敗，使用普通 mv
                if mv "$source" "$dest" 2>/dev/null; then
                    git add "$dest" 2>/dev/null || true
                    success "已移動 (手動): $source -> $dest"
                    MOVED_FILES+=("$source -> $dest")
                else
                    error "移動失敗: $source -> $dest"
                    FAILED_MOVES+=("$source -> $dest")
                fi
            fi
        fi
    done

    success "檔案移動完成"
}

# 生成報告
generate_report() {
    info "生成移動報告..."

    local report_file="docs/simple-file-move-report.md"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    cat > "$report_file" << EOF
# 簡化檔案移動報告

**執行時間**: $timestamp
**模式**: $([ "$DRY_RUN" == "true" ] && echo "預覽模式" || echo "執行模式")

## 統計資訊

- **總檔案數**: ${#FILE_MAPPINGS[@]}
- **成功移動**: ${#MOVED_FILES[@]}
- **失敗項目**: ${#FAILED_MOVES[@]}

## 成功移動的檔案

EOF

    if [[ ${#MOVED_FILES[@]} -gt 0 ]]; then
        for move in "${MOVED_FILES[@]}"; do
            echo "- $move" >> "$report_file"
        done
    else
        echo "無" >> "$report_file"
    fi

    cat >> "$report_file" << EOF

## 失敗的項目

EOF

    if [[ ${#FAILED_MOVES[@]} -gt 0 ]]; then
        for failure in "${FAILED_MOVES[@]}"; do
            echo "- $failure" >> "$report_file"
        done
    else
        echo "無" >> "$report_file"
    fi

    success "報告已生成: $report_file"
}

# 主函數
main() {
    local start_time=$(date '+%s')

    info "開始簡化檔案移動腳本"
    if [[ "$DRY_RUN" == "true" ]]; then
        info "模式: 預覽模式"
    else
        info "模式: 執行模式"
    fi

    check_prerequisites

    # 初始化陣列
    FILE_MAPPINGS=()

    read_move_list
    validate_files
    create_directories
    move_files
    generate_report

    local end_time=$(date '+%s')
    local duration=$((end_time - start_time))

    success "移動完成，耗時 ${duration} 秒"

    echo ""
    echo "==================== 執行摘要 ===================="
    echo "總檔案數: ${#FILE_MAPPINGS[@]}"
    echo "成功移動: ${#MOVED_FILES[@]}"
    echo "失敗項目: ${#FAILED_MOVES[@]}"
    echo "執行時間: ${duration} 秒"
    echo "=================================================="

    if [[ ${#FAILED_MOVES[@]} -gt 0 ]]; then
        exit 1
    fi
}

# 解析參數並執行
parse_arguments "$@"
main
