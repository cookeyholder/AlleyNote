#!/bin/bash

# AlleyNote 快取清理腳本
# 用於定期清理過期快取和監控快取使用情況

set -e

# 設定變數
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_DIR/storage/logs"
CACHE_DIR="$PROJECT_DIR/storage/cache"
LOG_FILE="$LOG_DIR/cache-cleanup.log"
MAX_LOG_SIZE=10485760  # 10MB

# 確保日誌目錄存在
mkdir -p "$LOG_DIR"

# 記錄日誌函數
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# 檢查日誌檔案大小並輪轉
rotate_log_if_needed() {
    if [[ -f "$LOG_FILE" ]] && [[ $(stat -f%z "$LOG_FILE" 2>/dev/null || stat -c%s "$LOG_FILE" 2>/dev/null || echo 0) -gt $MAX_LOG_SIZE ]]; then
        mv "$LOG_FILE" "${LOG_FILE}.old"
        log "日誌檔案已輪轉"
    fi
}

# 檢查 Docker 環境
check_docker() {
    # detect compose command
    if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        COMPOSE_CMD="docker compose"
    elif command -v docker-compose >/dev/null 2>&1; then
        COMPOSE_CMD="docker-compose"
    else
        log "錯誤: docker-compose 未安裝 (需要 docker compose 或 docker-compose)"
        exit 1
    fi

    if ! $COMPOSE_CMD ps | grep -q "alleynote_web"; then
        log "錯誤: AlleyNote 容器未運行"
        exit 1
    fi
}

# 清理過期快取
cleanup_expired_cache() {
    log "開始清理過期快取..."

    local start_time=$(date +%s)
    local before_count=$(find "$CACHE_DIR" -name "*.cache" 2>/dev/null | wc -l || echo 0)
    local before_size=$(du -sk "$CACHE_DIR" 2>/dev/null | cut -f1 || echo 0)

    # 使用 PHP 腳本清理
    local result
    if result=$(cd "$PROJECT_DIR" && ${COMPOSE_CMD} exec -T web php scripts/cache-monitor.php clean 2>&1); then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        local after_count=$(find "$CACHE_DIR" -name "*.cache" 2>/dev/null | wc -l || echo 0)
        local after_size=$(du -sk "$CACHE_DIR" 2>/dev/null | cut -f1 || echo 0)
        local cleaned_count=$((before_count - after_count))
        local freed_kb=$((before_size - after_size))

        log "快取清理完成: 清理了 $cleaned_count 個檔案，釋放了 ${freed_kb}KB 空間，耗時 ${duration}s"
    else
        log "快取清理失敗: $result"
        return 1
    fi
}

# 監控快取使用情況
monitor_cache_usage() {
    log "檢查快取使用情況..."

    # 獲取快取統計
    local stats_result
    if stats_result=$(cd "$PROJECT_DIR" && ${COMPOSE_CMD} exec -T web php scripts/cache-monitor.php stats 2>&1); then
        # 提取關鍵指標
        local hit_rate=$(echo "$stats_result" | grep "命中率" | grep -o '[0-9]*\.[0-9]*%' || echo "0%")
        local file_count=$(echo "$stats_result" | grep "快取檔案" | grep -o '[0-9]*' || echo "0")
        local total_size=$(echo "$stats_result" | grep "總大小" | sed 's/.*總大小: //' || echo "未知")

        log "快取統計 - 命中率: $hit_rate, 檔案數: $file_count, 總大小: $total_size"

        # 檢查是否需要警告
        local hit_rate_num=$(echo "$hit_rate" | sed 's/%//')
        if (( $(echo "$hit_rate_num < 30" | bc -l 2>/dev/null || echo 0) )); then
            log "警告: 快取命中率過低 ($hit_rate)，建議檢查快取策略"
        fi

        # 檢查檔案數量是否過多
        if [[ $file_count -gt 10000 ]]; then
            log "警告: 快取檔案數量過多 ($file_count)，建議檢查快取清理策略"
        fi
    else
        log "獲取快取統計失敗: $stats_result"
    fi
}

# 清理舊的日誌檔案
cleanup_old_logs() {
    log "清理舊的日誌檔案..."

    # 清理超過 30 天的日誌檔案
    local cleaned=0
    if [[ -d "$LOG_DIR" ]]; then
        while IFS= read -r -d '' file; do
            rm "$file"
            ((cleaned++))
        done < <(find "$LOG_DIR" -name "*.log" -type f -mtime +30 -print0 2>/dev/null || true)
    fi

    if [[ $cleaned -gt 0 ]]; then
        log "清理了 $cleaned 個舊的日誌檔案"
    fi
}

# 檢查磁碟空間
check_disk_space() {
    local cache_usage
    local storage_usage

    cache_usage=$(du -sh "$CACHE_DIR" 2>/dev/null | cut -f1 || echo "未知")
    storage_usage=$(df -h "$PROJECT_DIR/storage" 2>/dev/null | tail -1 | awk '{print $5}' || echo "未知")

    log "磁碟使用情況 - 快取目錄: $cache_usage, 儲存目錄使用率: $storage_usage"

    # 檢查儲存空間是否不足
    local usage_percent
    usage_percent=$(echo "$storage_usage" | sed 's/%//')
    if [[ "$usage_percent" =~ ^[0-9]+$ ]] && [[ $usage_percent -gt 85 ]]; then
        log "警告: 儲存空間使用率過高 ($storage_usage)，建議清理或擴充容量"
    fi
}

# 主執行流程
main() {
    log "========== 開始快取清理作業 =========="

    # 輪轉日誌檔案
    rotate_log_if_needed

    # 檢查執行環境
    check_docker

    # 執行清理任務
    cleanup_expired_cache || log "快取清理作業失敗"

    # 監控快取使用情況
    monitor_cache_usage

    # 清理舊日誌
    cleanup_old_logs

    # 檢查磁碟空間
    check_disk_space

    log "========== 快取清理作業完成 =========="
    echo # 空行分隔
}

# 錯誤處理
trap 'log "腳本執行中斷或發生錯誤"; exit 1' ERR INT TERM

# 檢查參數
case "${1:-auto}" in
    "auto")
        main
        ;;
    "force-clean")
        log "強制清理所有快取..."
        check_docker
    cd "$PROJECT_DIR" && ${COMPOSE_CMD} exec -T web php scripts/cache-monitor.php clear
        ;;
    "stats")
        check_docker
    cd "$PROJECT_DIR" && ${COMPOSE_CMD} exec -T web php scripts/cache-monitor.php stats
        ;;
    "help"|"-h"|"--help")
        echo "AlleyNote 快取清理腳本"
        echo ""
        echo "用法: $0 [選項]"
        echo ""
        echo "選項:"
        echo "  auto        自動清理過期快取 (預設)"
        echo "  force-clean 強制清理所有快取"
        echo "  stats       顯示快取統計資訊"
        echo "  help        顯示此幫助資訊"
        echo ""
        echo "範例:"
        echo "  $0                # 執行自動清理"
        echo "  $0 force-clean    # 強制清理所有快取"
        echo "  $0 stats          # 查看快取統計"
        echo ""
        echo "Cron 範例 (每小時執行一次):"
        echo "  0 * * * * $SCRIPT_DIR/cache-cleanup.sh >> $LOG_FILE 2>&1"
        ;;
    *)
        log "錯誤: 未知的選項 '$1'"
        echo "使用 '$0 help' 查看使用說明"
        exit 1
        ;;
esac
