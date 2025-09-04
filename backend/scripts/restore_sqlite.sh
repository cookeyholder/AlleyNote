#!/bin/bash

# AlleyNote SQLite 還原腳本
# 提供完整的還原功能，支援多種壓縮格式與安全驗證

set -euo pipefail

# ===============================
# 配置區域
# ===============================

readonly SCRIPT_NAME="$(basename "$0")"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
readonly DATABASE_DIR="$PROJECT_DIR/database"
readonly DEFAULT_DATABASE_FILE="$DATABASE_DIR/alleynote.sqlite3"
readonly BACKUPS_DIR="$DATABASE_DIR/backups"
readonly LOG_DIR="$PROJECT_DIR/storage/logs"
readonly LOG_FILE="$LOG_DIR/restore-$(date +%Y%m%d).log"

# ANSI 顏色碼
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly BOLD='\033[1m'
readonly NC='\033[0m' # No Color

# ===============================
# 工具函式
# ===============================

log_message() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    # 確保日誌目錄存在
    mkdir -p "$(dirname "$LOG_FILE")" 2>/dev/null || true
    
    case "$level" in
        "INFO")
            echo -e "${GREEN}[INFO]${NC} $message"
            echo "[$timestamp] [INFO] $message" >> "$LOG_FILE" 2>/dev/null || true
            ;;
        "WARN")
            echo -e "${YELLOW}[WARN]${NC} $message"
            echo "[$timestamp] [WARN] $message" >> "$LOG_FILE" 2>/dev/null || true
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} $message" >&2
            echo "[$timestamp] [ERROR] $message" >> "$LOG_FILE" 2>/dev/null || true
            ;;
        "SUCCESS")
            echo -e "${GREEN}[SUCCESS]${NC} $message"
            echo "[$timestamp] [SUCCESS] $message" >> "$LOG_FILE" 2>/dev/null || true
            ;;
    esac
}

show_help() {
    cat << EOF
${BOLD}AlleyNote SQLite 還原工具${NC}

${BOLD}用法:${NC}
    $SCRIPT_NAME [選項] [備份檔案] [目標資料庫]

${BOLD}選項:${NC}
    -b, --backup-target      還原前先備份目標資料庫
    -f, --force              強制覆寫目標資料庫（不詢問）
    -v, --verify             還原後驗證資料庫完整性
    -l, --list               列出可用的備份檔案
    --auto                   自動選擇最新的備份檔案
    --dry-run               僅顯示操作步驟，不實際執行
    --debug                 啟用除錯模式
    -h, --help              顯示此說明

${BOLD}環境變數:${NC}
    RESTORE_BACKUP_BEFORE   還原前是否自動備份 (true|false)

${BOLD}範例:${NC}
    $SCRIPT_NAME                                       # 列出可用備份並互動選擇
    $SCRIPT_NAME --auto                                # 自動選擇最新備份還原
    $SCRIPT_NAME backup.sqlite3.gz                     # 還原指定備份檔案
    $SCRIPT_NAME backup.sqlite3.gz target.sqlite3     # 還原到指定目標
    $SCRIPT_NAME -l                                    # 僅列出可用備份
    $SCRIPT_NAME -b -v backup.sqlite3.gz              # 備份目標後還原並驗證

${BOLD}支援的備份格式:${NC}
    *.sqlite3      未壓縮的 SQLite 資料庫檔案
    *.sqlite3.gz   gzip 壓縮的備份檔案
    *.sqlite3.bz2  bzip2 壓縮的備份檔案
    *.sqlite3.xz   xz 壓縮的備份檔案
EOF
}

detect_compression_type() {
    local file="$1"
    
    case "$file" in
        *.gz)
            echo "gzip"
            ;;
        *.bz2)
            echo "bzip2"
            ;;
        *.xz)
            echo "xz"
            ;;
        *)
            echo "none"
            ;;
    esac
}

check_dependencies() {
    local required_commands=("sqlite3")
    local missing_deps=()
    
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            missing_deps+=("$cmd")
        fi
    done
    
    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        log_message "ERROR" "缺少必要的指令："
        for dep in "${missing_deps[@]}"; do
            log_message "ERROR" "  - $dep"
        done
        return 1
    fi
    
    return 0
}

validate_backup_file() {
    local backup_file="$1"
    local compression_type="$2"
    
    if [[ ! -f "$backup_file" ]]; then
        log_message "ERROR" "找不到備份檔案: $backup_file"
        return 1
    fi
    
    # 驗證壓縮檔案完整性
    case "$compression_type" in
        "gzip")
            if ! gzip -t "$backup_file" 2>/dev/null; then
                log_message "ERROR" "備份檔案損壞或不是有效的 gzip 檔案"
                return 1
            fi
            ;;
        "bzip2")
            if ! bzip2 -t "$backup_file" 2>/dev/null; then
                log_message "ERROR" "備份檔案損壞或不是有效的 bzip2 檔案"
                return 1
            fi
            ;;
        "xz")
            if ! xz -t "$backup_file" 2>/dev/null; then
                log_message "ERROR" "備份檔案損壞或不是有效的 xz 檔案"
                return 1
            fi
            ;;
        "none")
            # 對於未壓縮的檔案，檢查是否為有效的 SQLite 檔案
            if ! sqlite3 "$backup_file" "SELECT 1;" &> /dev/null; then
                log_message "ERROR" "備份檔案不是有效的 SQLite 資料庫"
                return 1
            fi
            ;;
    esac
    
    log_message "SUCCESS" "備份檔案驗證通過"
    return 0
}

decompress_backup() {
    local backup_file="$1"
    local compression_type="$2"
    local temp_dir
    temp_dir=$(mktemp -d)
    local output_file="$temp_dir/$(basename "$backup_file" | sed 's/\.\(gz\|bz2\|xz\)$//')"
    
    case "$compression_type" in
        "gzip")
            log_message "INFO" "解壓縮 gzip 檔案..."
            if gzip -dc "$backup_file" > "$output_file"; then
                echo "$output_file"
                return 0
            fi
            ;;
        "bzip2")
            log_message "INFO" "解壓縮 bzip2 檔案..."
            if bzip2 -dc "$backup_file" > "$output_file"; then
                echo "$output_file"
                return 0
            fi
            ;;
        "xz")
            log_message "INFO" "解壓縮 xz 檔案..."
            if xz -dc "$backup_file" > "$output_file"; then
                echo "$output_file"
                return 0
            fi
            ;;
        "none")
            echo "$backup_file"
            return 0
            ;;
    esac
    
    log_message "ERROR" "解壓縮失敗"
    rm -rf "$temp_dir"
    return 1
}

backup_existing_database() {
    local target_db="$1"
    
    if [[ ! -f "$target_db" ]]; then
        return 0
    fi
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local safety_backup="$BACKUPS_DIR/safety_backup_${backup_timestamp}.sqlite3"
    
    log_message "INFO" "建立安全備份: $safety_backup"
    mkdir -p "$BACKUPS_DIR"
    
    if cp "$target_db" "$safety_backup"; then
        # 壓縮安全備份
        if command -v gzip &> /dev/null; then
            gzip "$safety_backup"
            log_message "SUCCESS" "安全備份已建立並壓縮: ${safety_backup}.gz"
        else
            log_message "SUCCESS" "安全備份已建立: $safety_backup"
        fi
        return 0
    else
        log_message "ERROR" "無法建立安全備份"
        return 1
    fi
}

list_available_backups() {
    if [[ ! -d "$BACKUPS_DIR" ]]; then
        log_message "INFO" "備份目錄不存在"
        return 1
    fi
    
    log_message "INFO" "可用的備份檔案"
    echo ""
    echo -e "${BOLD}=== AlleyNote SQLite 備份檔案 ===${NC}"
    echo -e "${CYAN}備份目錄:${NC} $BACKUPS_DIR"
    echo ""
    
    # 尋找所有備份檔案並依時間排序
    local backup_files
    backup_files=$(find "$BACKUPS_DIR" -name "*.sqlite3*" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | cut -d' ' -f2-)
    
    if [[ -z "$backup_files" ]]; then
        log_message "WARN" "沒有找到備份檔案"
        return 1
    fi
    
    local counter=1
    while IFS= read -r backup_file; do
        if [[ -f "$backup_file" ]]; then
            local file_size
            file_size=$(du -h "$backup_file" 2>/dev/null | cut -f1)
            local file_time
            file_time=$(stat -c '%y' "$backup_file" 2>/dev/null | cut -d'.' -f1)
            local basename_file
            basename_file=$(basename "$backup_file")
            
            echo -e "${YELLOW}[$counter]${NC} $basename_file"
            echo -e "    大小: $file_size, 時間: $file_time"
            echo -e "    路徑: $backup_file"
            echo ""
            
            ((counter++))
        fi
    done <<< "$backup_files"
    
    return 0
}

select_backup_interactively() {
    if ! list_available_backups; then
        return 1
    fi
    
    echo -e "${BOLD}請選擇要還原的備份檔案:${NC}"
    read -p "輸入編號 (或 'q' 取消): " choice
    
    if [[ "$choice" == "q" ]] || [[ "$choice" == "Q" ]]; then
        log_message "INFO" "使用者取消操作"
        return 1
    fi
    
    # 驗證輸入是否為數字
    if ! [[ "$choice" =~ ^[0-9]+$ ]]; then
        log_message "ERROR" "無效的選擇"
        return 1
    fi
    
    # 取得對應的備份檔案
    local backup_files
    backup_files=$(find "$BACKUPS_DIR" -name "*.sqlite3*" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | cut -d' ' -f2-)
    
    local selected_backup
    selected_backup=$(echo "$backup_files" | sed -n "${choice}p")
    
    if [[ -z "$selected_backup" ]] || [[ ! -f "$selected_backup" ]]; then
        log_message "ERROR" "無效的選擇或檔案不存在"
        return 1
    fi
    
    echo "$selected_backup"
    return 0
}

get_latest_backup() {
    local latest_backup
    latest_backup=$(find "$BACKUPS_DIR" -name "*.sqlite3*" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)
    
    if [[ -n "$latest_backup" ]] && [[ -f "$latest_backup" ]]; then
        echo "$latest_backup"
        return 0
    else
        log_message "ERROR" "沒有找到可用的備份檔案"
        return 1
    fi
}

perform_restore() {
    local backup_file="$1"
    local target_db="$2"
    local compression_type="$3"
    local backup_existing="$4"
    local verify_after="$5"
    local dry_run="$6"
    
    if [[ "$dry_run" == true ]]; then
        echo ""
        echo -e "${BOLD}=== 還原操作預覽 ===${NC}"
        echo -e "${CYAN}備份檔案:${NC}   $backup_file"
        echo -e "${CYAN}目標資料庫:${NC} $target_db"
        echo -e "${CYAN}壓縮格式:${NC}   $compression_type"
        echo -e "${CYAN}預先備份:${NC}   $backup_existing"
        echo -e "${CYAN}事後驗證:${NC}   $verify_after"
        echo ""
        return 0
    fi
    
    log_message "INFO" "開始還原 SQLite 資料庫..."
    log_message "INFO" "備份檔案: $backup_file"
    log_message "INFO" "目標資料庫: $target_db"
    
    # 預先備份現有資料庫
    if [[ "$backup_existing" == true ]] && [[ -f "$target_db" ]]; then
        if ! backup_existing_database "$target_db"; then
            log_message "ERROR" "預先備份失敗，還原操作終止"
            return 1
        fi
    fi
    
    # 確保目標目錄存在
    mkdir -p "$(dirname "$target_db")"
    
    # 解壓縮備份檔案（如果需要）
    local source_file
    if source_file=$(decompress_backup "$backup_file" "$compression_type"); then
        log_message "INFO" "備份檔案處理完成"
    else
        log_message "ERROR" "備份檔案處理失敗"
        return 1
    fi
    
    # 執行還原
    if cp "$source_file" "$target_db"; then
        log_message "SUCCESS" "資料庫還原完成"
    else
        log_message "ERROR" "資料庫還原失敗"
        # 清理臨時檔案
        [[ "$compression_type" != "none" ]] && rm -f "$source_file"
        return 1
    fi
    
    # 清理臨時檔案
    if [[ "$compression_type" != "none" ]]; then
        rm -f "$source_file"
    fi
    
    # 設定適當的權限
    chmod 664 "$target_db" 2>/dev/null || true
    
    # 驗證還原的資料庫
    if [[ "$verify_after" == true ]]; then
        if verify_restored_database "$target_db"; then
            log_message "SUCCESS" "資料庫還原驗證通過"
        else
            log_message "ERROR" "資料庫還原驗證失敗"
            return 1
        fi
    fi
    
    # 顯示還原統計資訊
    display_restore_info "$backup_file" "$target_db"
    
    return 0
}

verify_restored_database() {
    local db_file="$1"
    
    log_message "INFO" "驗證還原的資料庫..."
    
    # 基本連線測試
    if ! sqlite3 "$db_file" "SELECT 1;" &> /dev/null; then
        log_message "ERROR" "資料庫連線失敗"
        return 1
    fi
    
    # 完整性檢查
    local integrity_result
    if integrity_result=$(sqlite3 "$db_file" "PRAGMA integrity_check;" 2>&1); then
        if [[ "$integrity_result" == "ok" ]]; then
            log_message "SUCCESS" "資料庫完整性檢查通過"
            return 0
        else
            log_message "ERROR" "資料庫完整性檢查失敗: $integrity_result"
            return 1
        fi
    else
        log_message "ERROR" "無法執行完整性檢查"
        return 1
    fi
}

display_restore_info() {
    local backup_file="$1"
    local target_db="$2"
    
    echo ""
    echo -e "${BOLD}=== 還原完成統計 ===${NC}"
    
    if [[ -f "$backup_file" ]]; then
        local backup_size
        backup_size=$(du -h "$backup_file" 2>/dev/null | cut -f1)
        local backup_time
        backup_time=$(stat -c '%y' "$backup_file" 2>/dev/null | cut -d'.' -f1)
        echo -e "${CYAN}備份檔案:${NC}   $(basename "$backup_file") ($backup_size)"
        echo -e "${CYAN}備份時間:${NC}   $backup_time"
    fi
    
    if [[ -f "$target_db" ]]; then
        local db_size
        db_size=$(du -h "$target_db" 2>/dev/null | cut -f1)
        local table_count
        table_count=$(sqlite3 "$target_db" "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null || echo "未知")
        echo -e "${CYAN}資料庫大小:${NC} $db_size"
        echo -e "${CYAN}資料表數量:${NC} $table_count"
    fi
    echo ""
}

# ===============================
# 主程式
# ===============================

main() {
    local backup_file=""
    local target_db="$DEFAULT_DATABASE_FILE"
    local backup_existing="${RESTORE_BACKUP_BEFORE:-false}"
    local force=false
    local verify_after=false
    local list_only=false
    local auto_select=false
    local dry_run=false
    
    # 解析命令列參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            -b|--backup-target)
                backup_existing=true
                shift
                ;;
            -f|--force)
                force=true
                shift
                ;;
            -v|--verify)
                verify_after=true
                shift
                ;;
            -l|--list)
                list_only=true
                shift
                ;;
            --auto)
                auto_select=true
                shift
                ;;
            --dry-run)
                dry_run=true
                shift
                ;;
            --debug)
                export DEBUG=1
                shift
                ;;
            -h|--help)
                show_help
                exit 0
                ;;
            -*)
                log_message "ERROR" "未知的選項: $1"
                exit 1
                ;;
            *)
                if [[ -z "$backup_file" ]]; then
                    backup_file="$1"
                elif [[ -z "$target_db" ]] || [[ "$target_db" == "$DEFAULT_DATABASE_FILE" ]]; then
                    target_db="$1"
                else
                    log_message "ERROR" "過多的參數: $1"
                    exit 1
                fi
                shift
                ;;
        esac
    done
    
    # 檢查相依套件
    if ! check_dependencies; then
        exit 1
    fi
    
    # 僅列出備份檔案
    if [[ "$list_only" == true ]]; then
        list_available_backups
        exit 0
    fi
    
    # 自動選擇最新備份
    if [[ "$auto_select" == true ]] && [[ -z "$backup_file" ]]; then
        if backup_file=$(get_latest_backup); then
            log_message "INFO" "自動選擇最新備份: $(basename "$backup_file")"
        else
            exit 1
        fi
    fi
    
    # 互動式選擇備份檔案
    if [[ -z "$backup_file" ]]; then
        if backup_file=$(select_backup_interactively); then
            log_message "INFO" "選擇的備份檔案: $(basename "$backup_file")"
        else
            exit 1
        fi
    fi
    
    # 驗證備份檔案
    local compression_type
    compression_type=$(detect_compression_type "$backup_file")
    
    if ! validate_backup_file "$backup_file" "$compression_type"; then
        exit 1
    fi
    
    # 檢查目標資料庫是否存在
    if [[ -f "$target_db" ]] && [[ "$force" == false ]] && [[ "$dry_run" == false ]]; then
        echo ""
        log_message "WARN" "目標資料庫檔案已存在: $target_db"
        echo -e "${YELLOW}選項:${NC}"
        echo "  1) 備份現有資料庫後覆寫"
        echo "  2) 直接覆寫（危險）"
        echo "  3) 取消操作"
        echo ""
        read -p "請選擇 (1-3): " choice
        
        case $choice in
            1)
                backup_existing=true
                ;;
            2)
                backup_existing=false
                ;;
            3)
                log_message "INFO" "使用者取消操作"
                exit 0
                ;;
            *)
                log_message "ERROR" "無效的選擇"
                exit 1
                ;;
        esac
    fi
    
    # 執行還原
    log_message "INFO" "AlleyNote SQLite 還原開始..."
    
    if perform_restore "$backup_file" "$target_db" "$compression_type" "$backup_existing" "$verify_after" "$dry_run"; then
        if [[ "$dry_run" == false ]]; then
            log_message "SUCCESS" "還原操作完成!"
            log_message "INFO" "日誌檔案: $LOG_FILE"
        fi
        exit 0
    else
        log_message "ERROR" "還原操作失敗"
        exit 1
    fi
}

# 錯誤處理
trap 'log_message "ERROR" "還原過程中發生未預期的錯誤 (行號: $LINENO)"' ERR

# 執行主程式
main "$@"