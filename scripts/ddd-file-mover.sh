#!/bin/zsh

# DDD 檔案移動腳本
#
# 此腳本用於安全地移動檔案到新的 DDD 結構
# 支援預覽模式和實際執行模式
#
# 使用方式:
# zsh scripts/ddd-file-mover.sh --dry-run    # 預覽模式
# zsh scripts/ddd-file-mover.sh --execute    # 執行移動

set -e  # 遇到錯誤立即退出

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 全域變數
DRY_RUN=false
EXECUTE=false
LOG_FILE="logs/ddd-file-mover.log"
MIGRATION_MAPPING_FILE="docs/ddd-migration-mapping.md"
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

warning() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${YELLOW}[$timestamp] WARNING: $message${NC}" | tee -a "$LOG_FILE"
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

    if [[ "$DRY_RUN" == "true" && "$EXECUTE" == "true" ]]; then
        error "不能同時指定 --dry-run 和 --execute"
        show_help
        exit 1
    fi
}

# 顯示幫助
show_help() {
    echo "DDD 檔案移動腳本"
    echo ""
    echo "使用方式:"
    echo "  $0 --dry-run     預覽模式，只顯示將要執行的操作"
    echo "  $0 --execute     執行模式，實際移動檔案"
    echo "  $0 --help        顯示此幫助訊息"
    echo ""
    echo "注意事項:"
    echo "  - 執行前請確保 Git 工作目錄乾淨"
    echo "  - 建議先使用 --dry-run 預覽操作"
    echo "  - 移動過程中會自動創建必要的目錄"
}

# 檢查前置條件
check_prerequisites() {
    info "檢查前置條件..."

    # 檢查必要檔案
    if [[ ! -f "$MIGRATION_MAPPING_FILE" ]]; then
        error "找不到移動對照表檔案: $MIGRATION_MAPPING_FILE"
        exit 1
    fi

    # 檢查 Git 狀態
    if ! git status --porcelain | grep -q "^$"; then
        warning "Git 工作目錄不乾淨，建議先提交或暫存變更"
        if [[ "$EXECUTE" == "true" ]]; then
            read -p "是否繼續執行? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                info "取消執行"
                exit 0
            fi
        fi
    fi

    # 創建日誌目錄
    mkdir -p "$(dirname "$LOG_FILE")"

    success "前置條件檢查完成"
}

# 解析移動對照表
parse_migration_mapping() {
    info "解析檔案移動對照表..."

    # 檢查檔案是否存在
    if [[ ! -f "$MIGRATION_MAPPING_FILE" ]]; then
        error "移動對照表檔案不存在: $MIGRATION_MAPPING_FILE"
        exit 1
    fi

    # 解析對照表中的檔案移動項目
    local in_table=false
    local line_number=0

    while IFS= read -r line; do
        ((line_number++))

        # 跳過空行和註解
        if [[ -z "$line" || "$line" =~ ^[[:space:]]*# ]]; then
            continue
        fi

        # 檢查是否進入表格區域
        if [[ "$line" =~ ^\|.*原路徑.*新路徑.* ]]; then
            in_table=true
            continue
        fi

        # 檢查是否離開表格區域
        if [[ "$line" =~ ^### || "$line" =~ ^## ]]; then
            in_table=false
            continue
        fi

        # 解析表格行
        if [[ "$in_table" == "true" && "$line" =~ ^\|.*\|.*\|.*\|.*\|$ ]]; then
            # 移除首尾的 | 並分割
            local cleaned_line="${line#|}"
            cleaned_line="${cleaned_line%|}"

            IFS='|' read -ra FIELDS <<< "$cleaned_line"

            if [[ ${#FIELDS[@]} -ge 2 ]]; then
                local source_path=$(echo "${FIELDS[0]}" | xargs)  # trim whitespace
                local dest_path=$(echo "${FIELDS[1]}" | xargs)    # trim whitespace

                # 跳過表頭分隔線
                if [[ "$source_path" =~ ^-+$ || "$dest_path" =~ ^-+$ ]]; then
                    continue
                fi

                # 跳過空的路徑
                if [[ -n "$source_path" && -n "$dest_path" && "$source_path" != "原路徑" ]]; then
                    FILE_MAPPINGS+=("$source_path|$dest_path")
                fi
            fi
        fi
    done < "$MIGRATION_MAPPING_FILE"

    info "解析完成，共找到 ${#FILE_MAPPINGS[@]} 個檔案移動項目"
}

# 驗證檔案移動
validate_file_moves() {
    info "驗證檔案移動..."

    local missing_files=()
    local existing_targets=()

    for mapping in "${FILE_MAPPINGS[@]}"; do
        IFS='|' read -ra PARTS <<< "$mapping"
        local source_path="${PARTS[0]}"
        local dest_path="${PARTS[1]}"

        # 檢查來源檔案是否存在
        if [[ ! -f "$source_path" ]]; then
            missing_files+=("$source_path")
            continue
        fi

        # 檢查目標檔案是否已存在
        if [[ -f "$dest_path" ]]; then
            existing_targets+=("$dest_path")
        fi
    done

    # 報告缺失的檔案
    if [[ ${#missing_files[@]} -gt 0 ]]; then
        warning "以下來源檔案不存在:"
        for file in "${missing_files[@]}"; do
            warning "  - $file"
        done
    fi

    # 報告已存在的目標檔案
    if [[ ${#existing_targets[@]} -gt 0 ]]; then
        warning "以下目標檔案已存在:"
        for file in "${existing_targets[@]}"; do
            warning "  - $file"
        done

        if [[ "$EXECUTE" == "true" ]]; then
            read -p "是否覆蓋現有檔案? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                info "取消執行"
                exit 0
            fi
        fi
    fi

    success "檔案驗證完成"
}

# 創建目錄結構
create_directory_structure() {
    info "創建目標目錄結構..."

    local directories=()

    for mapping in "${FILE_MAPPINGS[@]}"; do
        IFS='|' read -ra PARTS <<< "$mapping"
        local dest_path="${PARTS[1]}"
        local dest_dir=$(dirname "$dest_path")

        # 添加到目錄列表（去重）
        if [[ ! " ${directories[@]} " =~ " ${dest_dir} " ]]; then
            directories+=("$dest_dir")
        fi
    done

    # 創建目錄
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

    success "目錄結構創建完成"
}

# 移動檔案
move_files() {
    info "開始移動檔案..."

    local total_files=${#FILE_MAPPINGS[@]}
    local current_file=0

    for mapping in "${FILE_MAPPINGS[@]}"; do
        ((current_file++))
        IFS='|' read -ra PARTS <<< "$mapping"
        local source_path="${PARTS[0]}"
        local dest_path="${PARTS[1]}"

        info "[$current_file/$total_files] 處理: $source_path -> $dest_path"

        # 檢查來源檔案
        if [[ ! -f "$source_path" ]]; then
            warning "跳過不存在的檔案: $source_path"
            FAILED_MOVES+=("$source_path: 檔案不存在")
            continue
        fi

        if [[ "$DRY_RUN" == "true" ]]; then
            info "[DRY-RUN] 將移動: $source_path -> $dest_path"
        else
            # 確保目標目錄存在
            local dest_dir=$(dirname "$dest_path")
            mkdir -p "$dest_dir"

            # 使用 Git mv 來移動檔案（保持歷史記錄）
            if git mv "$source_path" "$dest_path" 2>/dev/null; then
                success "已移動: $source_path -> $dest_path"
                MOVED_FILES+=("$source_path -> $dest_path")
            else
                # 如果 git mv 失敗，使用普通 mv
                if mv "$source_path" "$dest_path" 2>/dev/null; then
                    # 手動添加到 Git
                    git add "$dest_path" 2>/dev/null || true
                    success "已移動 (手動): $source_path -> $dest_path"
                    MOVED_FILES+=("$source_path -> $dest_path")
                else
                    error "移動失敗: $source_path -> $dest_path"
                    FAILED_MOVES+=("$source_path -> $dest_path: 移動失敗")
                fi
            fi
        fi
    done

    success "檔案移動完成"
}

# 驗證移動結果
verify_moves() {
    if [[ "$DRY_RUN" == "true" ]]; then
        return
    fi

    info "驗證移動結果..."

    local verification_failed=false

    for mapping in "${FILE_MAPPINGS[@]}"; do
        IFS='|' read -ra PARTS <<< "$mapping"
        local source_path="${PARTS[0]}"
        local dest_path="${PARTS[1]}"

        # 檢查來源檔案是否還存在
        if [[ -f "$source_path" ]]; then
            warning "來源檔案仍然存在: $source_path"
            verification_failed=true
        fi

        # 檢查目標檔案是否存在
        if [[ ! -f "$dest_path" ]]; then
            warning "目標檔案不存在: $dest_path"
            verification_failed=true
        fi
    done

    if [[ "$verification_failed" == "true" ]]; then
        error "移動驗證失敗"
        exit 1
    else
        success "移動驗證通過"
    fi
}

# 生成移動報告
generate_report() {
    info "生成移動報告..."

    local report_file="docs/ddd-file-move-report.md"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    if [[ "$DRY_RUN" == "true" ]]; then
        local mode_text="預覽模式"
    else
        local mode_text="執行模式"
    fi

    cat > "$report_file" << EOF
# DDD 檔案移動報告

**執行時間**: $timestamp
**模式**: $mode_text

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

    cat >> "$report_file" << EOF

## 後續步驟

1. 執行命名空間更新: \`php scripts/ddd-namespace-updater.php --mode=execute\`
2. 更新配置檔案
3. 執行測試並修復問題
4. 更新文件

## 注意事項

- 所有移動都已記錄在 Git 中
- 如需回滾，請使用 Git 命令或備份恢復
- 詳細日誌請查看: $LOG_FILE
EOF

    success "移動報告已生成: $report_file"
}

# 清理函數
cleanup() {
    if [[ ${#FAILED_MOVES[@]} -gt 0 ]]; then
        error "移動過程中發生錯誤，請檢查日誌"
        exit 1
    fi
}

# 主函數
main() {
    local start_time=$(date '+%s')

    info "開始 DDD 檔案移動腳本"
    if [[ "$DRY_RUN" == "true" ]]; then
        info "模式: 預覽模式"
    else
        info "模式: 執行模式"
    fi

    # 檢查前置條件
    check_prerequisites

    # 初始化檔案映射陣列
    FILE_MAPPINGS=()

    # 解析移動對照表
    parse_migration_mapping

    # 驗證檔案移動
    validate_file_moves

    # 創建目錄結構
    create_directory_structure

    # 移動檔案
    move_files

    # 驗證移動結果
    verify_moves

    # 生成報告
    generate_report

    local end_time=$(date '+%s')
    local duration=$((end_time - start_time))

    success "DDD 檔案移動完成，耗時 ${duration} 秒"

    # 顯示摘要
    echo ""
    echo "==================== 執行摘要 ===================="
    echo "總檔案數: ${#FILE_MAPPINGS[@]}"
    echo "成功移動: ${#MOVED_FILES[@]}"
    echo "失敗項目: ${#FAILED_MOVES[@]}"
    echo "執行時間: ${duration} 秒"
    echo "詳細日誌: $LOG_FILE"
    echo "移動報告: docs/ddd-file-move-report.md"
    echo "=================================================="
}

# 設置陷阱來處理退出
trap cleanup EXIT

# 解析參數並執行主函數
parse_arguments "$@"
main
