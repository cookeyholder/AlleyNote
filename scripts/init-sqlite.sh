#!/bin/bash

# AlleyNote SQLite 資料庫初始化腳本
# 使用現代化的 Phinx migration 管理系統
# 支援多環境配置與 Docker 整合

set -euo pipefail

# ===============================
# 常數定義與環境檢測
# ===============================

readonly SCRIPT_NAME="$(basename "$0")"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
readonly DATABASE_DIR="$PROJECT_DIR/database"
readonly BACKUPS_DIR="$DATABASE_DIR/backups"
readonly LOG_DIR="$PROJECT_DIR/storage/logs"
readonly CONFIG_FILE="$PROJECT_DIR/phinx.php"

# 預設設定
DEFAULT_ENV="development"
DEFAULT_DATABASE_FILE="$DATABASE_DIR/alleynote.sqlite3"

# 日誌設定
LOG_FILE="$LOG_DIR/database-init-$(date +%Y%m%d).log"

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
    
    # 同時輸出到終端和日誌檔案
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
        "DEBUG")
            if [[ "${DEBUG:-0}" == "1" ]]; then
                echo -e "${PURPLE}[DEBUG]${NC} $message"
                echo "[$timestamp] [DEBUG] $message" >> "$LOG_FILE" 2>/dev/null || true
            fi
            ;;
}

show_help() {
    cat << EOF
${BOLD}AlleyNote SQLite 資料庫初始化工具${NC}

${BOLD}用法:${NC}
    $SCRIPT_NAME [選項]

${BOLD}選項:${NC}
    -e, --env ENVIRONMENT    指定環境 (development|testing|production)
                            預設: development
    -d, --database PATH      指定資料庫檔案路徑
    -f, --force             強制重新建立資料庫 (刪除現有資料庫)
    -b, --backup            初始化前自動備份現有資料庫
    -s, --skip-migrations   跳過 migration 執行，僅建立空資料庫
    -v, --verify            初始化後執行完整性驗證
    --no-seed               跳過 seed 執行
    --debug                 啟用除錯模式
    -h, --help              顯示此說明

${BOLD}環境變數:${NC}
    DATABASE_URL            資料庫連線 URL
    PHINX_ENVIRONMENT       Phinx 環境設定
    DEBUG                   啟用除錯輸出 (0|1)

${BOLD}範例:${NC}
    $SCRIPT_NAME                           # 使用預設設定初始化開發環境
    $SCRIPT_NAME -e testing -f             # 強制重新建立測試環境資料庫
    $SCRIPT_NAME -e production -b -v       # 在生產環境中備份並初始化，包含驗證
    $SCRIPT_NAME --skip-migrations         # 僅建立空資料庫，不執行 migration

${BOLD}支援的環境:${NC}
    development   開發環境 (預設)
    testing       測試環境
    production    正式環境

此工具支援 Docker 環境自動檢測，並會根據執行環境調整路徑與設定。
EOF
}

detect_docker_environment() {
    if [[ -n "${DOCKER_CONTAINER:-}" ]] || [[ -f /.dockerenv ]] || grep -q docker /proc/1/cgroup 2>/dev/null; then
        log_message "INFO" "檢測到 Docker 環境"
        return 0
    fi
    return 1
}

check_dependencies() {
    local missing_deps=()
    
    # 檢查必要的指令
    local required_commands=("php" "sqlite3" "composer")
    
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            missing_deps+=("$cmd")
        fi
    done
    
    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        log_message "ERROR" "缺少必要的相依套件："
        for dep in "${missing_deps[@]}"; do
            log_message "ERROR" "  - $dep"
        done
        return 1
    fi
    
    # 檢查 Composer 套件
    if [[ ! -f "$PROJECT_DIR/vendor/autoload.php" ]]; then
        log_message "WARN" "偵測到 Composer 套件尚未安裝"
        log_message "INFO" "正在安裝 Composer 套件..."
        cd "$PROJECT_DIR"
        if composer install --no-dev --optimize-autoloader; then
            log_message "SUCCESS" "Composer 套件安裝完成"
        else
            log_message "ERROR" "Composer 套件安裝失敗"
            return 1
        fi
    fi
    
    # 檢查 Phinx
    if [[ ! -f "$PROJECT_DIR/vendor/bin/phinx" ]]; then
        log_message "ERROR" "找不到 Phinx migration 工具"
        log_message "INFO" "請執行: composer install"
        return 1
    fi
    
    return 0
}

validate_environment() {
    local env="$1"
    
    case "$env" in
        development|testing|production)
            return 0
            ;;
        *)
            log_message "ERROR" "無效的環境: $env"
            log_message "INFO" "支援的環境: development, testing, production"
            return 1
            ;;
    esac
}

backup_existing_database() {
    local db_file="$1"
    
    if [[ ! -f "$db_file" ]]; then
        return 0
    fi
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="$BACKUPS_DIR/alleynote_backup_${backup_timestamp}.sqlite3"
    
    log_message "INFO" "備份現有資料庫到: $backup_file"
    
    mkdir -p "$BACKUPS_DIR"
    
    if cp "$db_file" "$backup_file"; then
        # 壓縮備份檔案以節省空間
        if command -v gzip &> /dev/null; then
            gzip "$backup_file"
            backup_file="${backup_file}.gz"
            log_message "SUCCESS" "資料庫已備份並壓縮: $backup_file"
        else
            log_message "SUCCESS" "資料庫已備份: $backup_file"
        fi
        return 0
    else
        log_message "ERROR" "資料庫備份失敗"
        return 1
    fi
}

cleanup_old_backups() {
    local keep_days=${BACKUP_RETENTION_DAYS:-7}
    
    log_message "INFO" "清理 $keep_days 天前的備份檔案..."
    
    if [[ -d "$BACKUPS_DIR" ]]; then
        find "$BACKUPS_DIR" -name "alleynote_backup_*.sqlite3*" -type f -mtime +$keep_days -delete 2>/dev/null || true
        local remaining_backups=$(find "$BACKUPS_DIR" -name "alleynote_backup_*.sqlite3*" -type f | wc -l)
        log_message "INFO" "保留 $remaining_backups 個備份檔案"
    fi
}

create_database_structure() {
    local db_file="$1"
    
    log_message "INFO" "建立資料庫目錄結構..."
    
    mkdir -p "$DATABASE_DIR" "$BACKUPS_DIR"
    
    # 建立空的 SQLite 資料庫檔案
    if touch "$db_file"; then
        log_message "SUCCESS" "資料庫檔案已建立: $db_file"
    else
        log_message "ERROR" "無法建立資料庫檔案: $db_file"
        return 1
    fi
    
    # 設定適當的檔案權限
    chmod 664 "$db_file" 2>/dev/null || true
    chmod 755 "$DATABASE_DIR" 2>/dev/null || true
    
    return 0
}

execute_migrations() {
    local environment="$1"
    
    log_message "INFO" "執行資料庫 migrations (環境: $environment)..."
    
    cd "$PROJECT_DIR"
    
    # 檢查是否有待執行的 migrations
    local migration_status
    if migration_status=$(vendor/bin/phinx status -e "$environment" 2>&1); then
        log_message "DEBUG" "Migration 狀態檢查完成"
    else
        log_message "ERROR" "無法檢查 migration 狀態: $migration_status"
        return 1
    fi
    
    # 執行 migrations
    local migration_output
    if migration_output=$(vendor/bin/phinx migrate -e "$environment" 2>&1); then
        log_message "SUCCESS" "資料庫 migrations 執行完成"
        log_message "DEBUG" "Migration 輸出: $migration_output"
    else
        log_message "ERROR" "Migration 執行失敗: $migration_output"
        return 1
    fi
    
    return 0
}

execute_seeds() {
    local environment="$1"
    
    log_message "INFO" "執行資料庫 seeds (環境: $environment)..."
    
    cd "$PROJECT_DIR"
    
    # 檢查是否有 seed 檔案
    if [[ ! -d "$PROJECT_DIR/database/seeds" ]] || [[ -z "$(ls -A "$PROJECT_DIR/database/seeds" 2>/dev/null)" ]]; then
        log_message "INFO" "沒有找到 seed 檔案，跳過 seeding"
        return 0
    fi
    
    local seed_output
    if seed_output=$(vendor/bin/phinx seed:run -e "$environment" 2>&1); then
        log_message "SUCCESS" "資料庫 seeds 執行完成"
        log_message "DEBUG" "Seed 輸出: $seed_output"
    else
        log_message "WARN" "Seeds 執行出現問題: $seed_output"
        # Seeds 失敗不應該阻止整個初始化流程
    fi
    
    return 0
}

verify_database() {
    local db_file="$1"
    local environment="$2"
    
    log_message "INFO" "執行資料庫完整性驗證..."
    
    # 基本連線測試
    if ! sqlite3 "$db_file" "SELECT 1;" &> /dev/null; then
        log_message "ERROR" "資料庫連線失敗"
        return 1
    fi
    
    # 檢查資料庫完整性
    local integrity_check
    if integrity_check=$(sqlite3 "$db_file" "PRAGMA integrity_check;" 2>&1); then
        if [[ "$integrity_check" == "ok" ]]; then
            log_message "SUCCESS" "資料庫完整性檢查通過"
        else
            log_message "ERROR" "資料庫完整性檢查失敗: $integrity_check"
            return 1
        fi
    else
        log_message "ERROR" "無法執行資料庫完整性檢查"
        return 1
    fi
    
    # 顯示資料庫統計資訊
    display_database_info "$db_file" "$environment"
    
    return 0
}

display_database_info() {
    local db_file="$1"
    local environment="$2"
    
    log_message "INFO" "資料庫統計資訊"
    echo ""
    echo -e "${BOLD}=== AlleyNote SQLite 資料庫資訊 ===${NC}"
    echo -e "${CYAN}環境:${NC}         $environment"
    echo -e "${CYAN}檔案路徑:${NC}     $db_file"
    
    if [[ -f "$db_file" ]]; then
        local file_size
        file_size=$(du -h "$db_file" 2>/dev/null | cut -f1 || echo "未知")
        echo -e "${CYAN}檔案大小:${NC}     $file_size"
        
        local table_count
        table_count=$(sqlite3 "$db_file" "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null || echo "0")
        echo -e "${CYAN}資料表數量:${NC}   $table_count"
        
        # 列出所有資料表
        local tables
        if tables=$(sqlite3 "$db_file" "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;" 2>/dev/null); then
            if [[ -n "$tables" ]]; then
                echo -e "${CYAN}資料表清單:${NC}"
                echo "$tables" | sed 's/^/  - /'
            fi
        fi
        
        # Migration 狀態
        cd "$PROJECT_DIR"
        local migration_status
        if migration_status=$(vendor/bin/phinx status -e "$environment" 2>/dev/null); then
            echo -e "${CYAN}Migration 狀態:${NC}"
            echo "$migration_status" | sed 's/^/  /'
        fi
    fi
    echo ""
}

performance_optimization() {
    local db_file="$1"
    
    log_message "INFO" "執行資料庫效能最佳化..."
    
    # SQLite 最佳化設定
    local optimization_sql="
    PRAGMA journal_mode=WAL;
    PRAGMA synchronous=NORMAL;
    PRAGMA cache_size=10000;
    PRAGMA temp_store=memory;
    PRAGMA mmap_size=268435456;
    VACUUM;
    "
    
    if echo "$optimization_sql" | sqlite3 "$db_file" 2>/dev/null; then
        log_message "SUCCESS" "資料庫效能最佳化完成"
    else
        log_message "WARN" "部分效能最佳化設定可能未生效"
    fi
}

# ===============================
# 主程式
# ===============================

main() {
    local environment="$DEFAULT_ENV"
    local database_file="$DEFAULT_DATABASE_FILE"
    local force_recreate=false
    local auto_backup=false
    local skip_migrations=false
    local skip_seeds=false
    local run_verification=false
    
    # 解析命令列參數
    while [[ $# -gt 0 ]]; do
        case $1 in
            -e|--env)
                environment="$2"
                shift 2
                ;;
            -d|--database)
                database_file="$2"
                shift 2
                ;;
            -f|--force)
                force_recreate=true
                shift
                ;;
            -b|--backup)
                auto_backup=true
                shift
                ;;
            -s|--skip-migrations)
                skip_migrations=true
                shift
                ;;
            --no-seed)
                skip_seeds=true
                shift
                ;;
            -v|--verify)
                run_verification=true
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
            *)
                log_message "ERROR" "未知的選項: $1"
                log_message "INFO" "使用 $SCRIPT_NAME --help 查看使用說明"
                exit 1
                ;;
        esac
    done
    
    # 開始初始化流程
    log_message "INFO" "AlleyNote SQLite 資料庫初始化開始..."
    log_message "INFO" "環境: $environment"
    log_message "INFO" "資料庫檔案: $database_file"
    
    # 環境驗證
    if ! validate_environment "$environment"; then
        exit 1
    fi
    
    # 檢查相依套件
    if ! check_dependencies; then
        exit 1
    fi
    
    # 檢查設定檔案
    if [[ ! -f "$CONFIG_FILE" ]]; then
        log_message "ERROR" "找不到 Phinx 設定檔案: $CONFIG_FILE"
        exit 1
    fi
    
    # 切換到專案目錄
    cd "$PROJECT_DIR"
    
    # 處理現有資料庫
    if [[ -f "$database_file" ]]; then
        if [[ "$force_recreate" == true ]]; then
            if [[ "$auto_backup" == true ]]; then
                if ! backup_existing_database "$database_file"; then
                    log_message "ERROR" "自動備份失敗，終止初始化"
                    exit 1
                fi
            fi
            log_message "WARN" "刪除現有資料庫: $database_file"
            rm -f "$database_file"
        else
            log_message "WARN" "資料庫檔案已存在: $database_file"
            echo -e "${YELLOW}選項:${NC}"
            echo "  1) 備份現有資料庫並重新建立"
            echo "  2) 強制重新建立 (不備份)"
            echo "  3) 取消初始化"
            echo ""
            read -p "請選擇 (1-3): " choice
            
            case $choice in
                1)
                    if ! backup_existing_database "$database_file"; then
                        log_message "ERROR" "備份失敗，終止初始化"
                        exit 1
                    fi
                    rm -f "$database_file"
                    ;;
                2)
                    log_message "WARN" "強制刪除現有資料庫"
                    rm -f "$database_file"
                    ;;
                3)
                    log_message "INFO" "使用者取消初始化"
                    exit 0
                    ;;
                *)
                    log_message "ERROR" "無效的選擇"
                    exit 1
                    ;;
            esac
        fi
    fi
    
    # 建立資料庫結構
    if ! create_database_structure "$database_file"; then
        exit 1
    fi
    
    # 執行 migrations
    if [[ "$skip_migrations" == false ]]; then
        if ! execute_migrations "$environment"; then
            log_message "ERROR" "Migration 執行失敗，初始化終止"
            exit 1
        fi
    else
        log_message "INFO" "跳過 migration 執行"
    fi
    
    # 執行 seeds
    if [[ "$skip_seeds" == false ]]; then
        execute_seeds "$environment"
    else
        log_message "INFO" "跳過 seed 執行"
    fi
    
    # 效能最佳化
    performance_optimization "$database_file"
    
    # 資料庫驗證
    if [[ "$run_verification" == true ]] || [[ "$environment" == "production" ]]; then
        if ! verify_database "$database_file" "$environment"; then
            log_message "ERROR" "資料庫驗證失敗"
            exit 1
        fi
    else
        display_database_info "$database_file" "$environment"
    fi
    
    # 清理舊備份
    cleanup_old_backups
    
    # 成功完成
    log_message "SUCCESS" "AlleyNote SQLite 資料庫初始化完成!"
    log_message "INFO" "日誌檔案: $LOG_FILE"
    
    return 0
}

# 錯誤處理
trap 'log_message "ERROR" "初始化過程中發生未預期的錯誤 (行號: $LINENO)"' ERR

# 執行主程式
main "$@"
