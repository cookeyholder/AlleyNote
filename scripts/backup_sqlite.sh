#!/bin/bash

# AlleyNote SQLite 備份腳本
# 提供完整的備份功能，支援 Docker 環境與多環境配置

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
readonly LOG_FILE="$LOG_DIR/backup-$(date +%Y%m%d).log"

# 預設設定
DEFAULT_RETENTION_DAYS=7
DEFAULT_COMPRESSION="gzip"

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
${BOLD}AlleyNote SQLite 備份工具${NC}

${BOLD}用法:${NC}
    $SCRIPT_NAME [選項] [來源資料庫] [備份檔案]

${BOLD}選項:${NC}
    -c, --compress TYPE      壓縮類型 (gzip|bzip2|xz|none) 預設: gzip
    -r, --retention DAYS     備份保留天數，預設: 7
    -v, --verify            備份後驗證完整性
    -f, --force             覆寫現有備份檔案
    --cleanup               僅執行舊備份清理
    --list                  列出現有備份檔案
    --debug                 啟用除錯模式
    -h, --help              顯示此說明

${BOLD}環境變數:${NC}
    BACKUP_RETENTION_DAYS   備份保留天數
    COMPRESSION_LEVEL       壓縮等級 (1-9)

${BOLD}範例:${NC}
    $SCRIPT_NAME                                    # 使用預設設定備份
    $SCRIPT_NAME -v -c gzip                        # 備份並驗證，使用 gzip 壓縮
    $SCRIPT_NAME ./data.sqlite3 ./backup.sqlite3   # 指定來源和目標
    $SCRIPT_NAME --cleanup                          # 僅清理舊備份
    $SCRIPT_NAME --list                             # 列出現有備份

${BOLD}支援的壓縮格式:${NC}
    gzip      預設壓縮格式，平衡速度與壓縮比
    bzip2     較高壓縮比，速度較慢
    xz        最高壓縮比，速度最慢
    none      不壓縮
EOF
}

detect_docker_environment() {
    if [[ -n "${DOCKER_CONTAINER:-}" ]] || [[ -f /.dockerenv ]] || grep -q docker /proc/1/cgroup 2>/dev/null; then
        return 0
    fi
    return 1
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

validate_database_file() {
    local db_file="$1"
    
    if [[ ! -f "$db_file" ]]; then
        log_message "ERROR" "找不到資料庫檔案: $db_file"
        return 1
    fi
    
    # 檢查是否為有效的 SQLite 檔案
    if ! sqlite3 "$db_file" "SELECT 1;" &> /dev/null; then
        log_message "ERROR" "無效的 SQLite 資料庫檔案: $db_file"
        return 1
    fi
    
    return 0
}

compress_backup() {
    local backup_file="$1"
    local compression="$2"
    local compression_level="${COMPRESSION_LEVEL:-6}"
    
    case "$compression" in
        "gzip")
            log_message "INFO" "使用 gzip 壓縮備份檔案..."
            if gzip -${compression_level} "$backup_file"; then
                echo "${backup_file}.gz"
                return 0
            fi
            ;;
        "bzip2")
            log_message "INFO" "使用 bzip2 壓縮備份檔案..."
            if bzip2 -${compression_level} "$backup_file"; then
                echo "${backup_file}.bz2"
                return 0
            fi
            ;;
        "xz")
            log_message "INFO" "使用 xz 壓縮備份檔案..."
            if xz -${compression_level} "$backup_file"; then
                echo "${backup_file}.xz"
                return 0
            fi
            ;;
        "none")
            echo "$backup_file"
            return 0
            ;;
    esac
    
    log_message "ERROR" "壓縮失敗"
    return 1
}

verify_backup() {
    local backup_file="$1"
    local compression="$2"
    
    log_message "INFO" "驗證備份檔案完整性..."
    
    case "$compression" in
        "gzip")
            if ! gzip -t "$backup_file" 2>/dev/null; then
                log_message "ERROR" "備份檔案壓縮完整性驗證失敗"
                return 1
            fi
            ;;
        "bzip2")
            if ! bzip2 -t "$backup_file" 2>/dev/null; then
                log_message "ERROR" "備份檔案壓縮完整性驗證失敗"
                return 1
            fi
            ;;
        "xz")
            if ! xz -t "$backup_file" 2>/dev/null; then
                log_message "ERROR" "備份檔案壓縮完整性驗證失敗"
                return 1
            fi
            ;;
        "none")
            if ! validate_database_file "$backup_file"; then
                return 1
            fi
            ;;
    esac
    
    log_message "SUCCESS" "備份檔案驗證通過"
    return 0
}

cleanup_old_backups() {
    local retention_days="$1"
    
    log_message "INFO" "清理 $retention_days 天前的備份檔案..."
    
    if [[ ! -d "$BACKUPS_DIR" ]]; then
        log_message "INFO" "備份目錄不存在，跳過清理"
        return 0
    fi
    
    # 清理各種壓縮格式的備份檔案
    local patterns=("*.sqlite3" "*.sqlite3.gz" "*.sqlite3.bz2" "*.sqlite3.xz")
    local deleted_count=0
    
    for pattern in "${patterns[@]}"; do
        while IFS= read -r -d '' file; do
            if [[ -f "$file" ]] && [[ "$file" -ot "$(date -d "$retention_days days ago" '+%Y%m%d')" ]]; then
                log_message "INFO" "刪除舊備份: $(basename "$file")"
                rm -f "$file"
                ((deleted_count++))
            fi
        done < <(find "$BACKUPS_DIR" -name "$pattern" -type f -print0 2>/dev/null || true)
    done
    
    local remaining_count
    remaining_count=$(find "$BACKUPS_DIR" -name "*.sqlite3*" -type f 2>/dev/null | wc -l)
    log_message "INFO" "刪除了 $deleted_count 個舊備份，保留 $remaining_count 個備份檔案"
}

list_backups() {
    if [[ ! -d "$BACKUPS_DIR" ]]; then
        log_message "INFO" "備份目錄不存在"
        return 0
    fi
    
    log_message "INFO" "現有備份檔案列表"
    echo ""
    echo -e "${BOLD}=== AlleyNote SQLite 備份檔案 ===${NC}"
    echo -e "${CYAN}備份目錄:${NC} $BACKUPS_DIR"
    echo ""
    
    # 列出所有備份檔案，依修改時間排序
    if find "$BACKUPS_DIR" -name "*.sqlite3*" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | cut -d' ' -f2- | head -20; then
        echo ""
        local total_count
        total_count=$(find "$BACKUPS_DIR" -name "*.sqlite3*" -type f 2>/dev/null | wc -l)
        local total_size
        total_size=$(du -sh "$BACKUPS_DIR" 2>/dev/null | cut -f1)
        echo -e "${CYAN}總計:${NC} $total_count 個備份檔案，總大小: $total_size"
    else
        echo "沒有找到備份檔案"
    fi
}

perform_backup() {
    local source_db="$1"
    local backup_file="$2"
    local compression="$3"
    local verify="$4"
    local force="$5"
    
    # 驗證來源資料庫
    if ! validate_database_file "$source_db"; then
        return 1
    fi
    
    # 確保備份目錄存在
    mkdir -p "$(dirname "$backup_file")"
    
    # 檢查目標檔案是否存在
    if [[ -f "$backup_file" ]] && [[ "$force" == false ]]; then
        log_message "ERROR" "備份檔案已存在: $backup_file (使用 -f 強制覆寫)"
        return 1
    fi
    
    log_message "INFO" "開始備份 SQLite 資料庫..."
    log_message "INFO" "來源: $source_db"
    log_message "INFO" "目標: $backup_file"
    
    # 執行備份（使用 SQLite VACUUM INTO 指令進行最佳化備份）
    if sqlite3 "$source_db" ".backup '$backup_file'" 2>/dev/null; then
        log_message "SUCCESS" "資料庫備份完成"
    else
        log_message "ERROR" "資料庫備份失敗"
        return 1
    fi
    
    # 壓縮備份檔案
    local final_backup_file
    if final_backup_file=$(compress_backup "$backup_file" "$compression"); then
        backup_file="$final_backup_file"
        log_message "SUCCESS" "備份檔案已建立: $backup_file"
    else
        log_message "ERROR" "備份壓縮失敗"
        return 1
    fi
    
    # 驗證備份檔案
    if [[ "$verify" == true ]]; then
        if ! verify_backup "$backup_file" "$compression"; then
            log_message "ERROR" "備份驗證失敗"
            return 1
        fi
    fi
    
    # 顯示備份統計資訊
    display_backup_info "$source_db" "$backup_file"
    
    return 0
}

display_backup_info() {
    local source_db="$1"
    local backup_file="$2"
    
    echo ""
    echo -e "${BOLD}=== 備份統計資訊 ===${NC}"
    
    if [[ -f "$source_db" ]]; then
        local source_size
        source_size=$(du -h "$source_db" 2>/dev/null | cut -f1)
        echo -e "${CYAN}來源大小:${NC}   $source_size"
    fi
    
    if [[ -f "$backup_file" ]]; then
        local backup_size
        backup_size=$(du -h "$backup_file" 2>/dev/null | cut -f1)
        echo -e "${CYAN}備份大小:${NC}   $backup_size"
        
        local backup_time
        backup_time=$(stat -c '%y' "$backup_file" 2>/dev/null | cut -d'.' -f1)
        echo -e "${CYAN}備份時間:${NC}   $backup_time"
    fi
    echo ""
}

# ===============================
# 主程式
# ===============================

main() {
    local source_db="$DEFAULT_DATABASE_FILE"
    local backup_file=""
    local compression="$DEFAULT_COMPRESSION"
    local retention_days="$DEFAULT_RETENTION_DAYS"
    local verify=false
    local force=false
    local cleanup_only=false
    local list_only=false
    
    # 解析命令列參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            -c|--compress)
                compression="$2"
                shift 2
                ;;
            -r|--retention)
                retention_days="$2"
                shift 2
                ;;
            -v|--verify)
                verify=true
                shift
                ;;
            -f|--force)
                force=true
                shift
                ;;
            --cleanup)
                cleanup_only=true
                shift
                ;;
            --list)
                list_only=true
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
                if [[ -z "$backup_file" ]] && [[ -f "$1" ]]; then
                    source_db="$1"
                elif [[ -z "$backup_file" ]]; then
                    backup_file="$1"
                else
                    log_message "ERROR" "過多的參數: $1"
                    exit 1
                fi
                shift
                ;;
        esac
    done
    
    # 驗證壓縮類型
    case "$compression" in
        gzip|bzip2|xz|none)
            ;;
        *)
            log_message "ERROR" "不支援的壓縮類型: $compression"
            log_message "INFO" "支援的壓縮類型: gzip, bzip2, xz, none"
            exit 1
            ;;
    esac
    
    # 檢查相依套件
    if ! check_dependencies; then
        exit 1
    fi
    
    # 僅列出備份檔案
    if [[ "$list_only" == true ]]; then
        list_backups
        exit 0
    fi
    
    # 僅執行清理
    if [[ "$cleanup_only" == true ]]; then
        cleanup_old_backups "$retention_days"
        exit 0
    fi
    
    # 生成預設備份檔案名稱
    if [[ -z "$backup_file" ]]; then
        local timestamp=$(date +%Y%m%d_%H%M%S)
        local db_basename
        db_basename=$(basename "$source_db" .sqlite3)
        backup_file="$BACKUPS_DIR/${db_basename}_backup_${timestamp}.sqlite3"
    fi
    
    # 執行備份
    log_message "INFO" "AlleyNote SQLite 備份開始..."
    
    if perform_backup "$source_db" "$backup_file" "$compression" "$verify" "$force"; then
        # 清理舊備份
        cleanup_old_backups "$retention_days"
        
        log_message "SUCCESS" "備份操作完成!"
        log_message "INFO" "日誌檔案: $LOG_FILE"
        exit 0
    else
        log_message "ERROR" "備份操作失敗"
        exit 1
    fi
}

# 錯誤處理
trap 'log_message "ERROR" "備份過程中發生未預期的錯誤 (行號: $LINENO)"' ERR

# 執行主程式
main "$@"
