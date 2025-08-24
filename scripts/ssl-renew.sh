#!/bin/bash

# SSL 憑證續簽腳本
# 自動檢查並續簽 Let's Encrypt 憑證

set -e

# 設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_DIR/logs/ssl-renewal.log"

# 顏色輸出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 日誌函式
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S'): $1" | tee -a "$LOG_FILE"
}

log_info() {
    echo -e "${BLUE}[資訊]${NC} $1"
    log "[資訊] $1"
}

log_success() {
    echo -e "${GREEN}[成功]${NC} $1"
    log "[成功] $1"
}

log_warning() {
    echo -e "${YELLOW}[警告]${NC} $1"
    log "[警告] $1"
}

log_error() {
    echo -e "${RED}[錯誤]${NC} $1"
    log "[錯誤] $1"
}

# 建立日誌目錄
mkdir -p "$(dirname "$LOG_FILE")"

log_info "開始 SSL 憑證續簽檢查..."

# 切換到專案目錄
cd "$PROJECT_DIR"

# Detect compose command (prefer "docker compose" if available)
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    log_error "需要安裝 Docker Compose (docker compose 或 docker-compose)"
    exit 1
fi

# 檢查 Docker Compose 是否正常運作
if ! $COMPOSE_CMD ps | grep -q "Up"; then
    log_warning "部分服務未啟動，嘗試啟動服務..."
    $COMPOSE_CMD up -d
    sleep 10
fi

# 執行憑證續簽
log_info "檢查憑證是否需要續簽..."

if $COMPOSE_CMD run --rm certbot renew --dry-run; then
    log_info "憑證續簽檢查通過，執行實際續簽..."
    
    if $COMPOSE_CMD run --rm certbot renew --quiet; then
        log_success "憑證續簽完成"
        
        # 重新載入 Nginx 設定
        log_info "重新載入 Nginx 設定..."
    if $COMPOSE_CMD exec -T nginx nginx -s reload; then
            log_success "Nginx 設定重新載入完成"
        else
            log_warning "Nginx 設定重新載入失敗，嘗試重啟容器..."
            $COMPOSE_CMD restart nginx
            log_info "Nginx 容器已重啟"
        fi
        
        # 檢查憑證有效期
        log_info "檢查憑證有效期..."
        SSL_DOMAIN=$(grep "SSL_DOMAIN=" .env | cut -d'=' -f2)
        if [ -f "ssl-data/live/$SSL_DOMAIN/fullchain.pem" ]; then
            EXPIRY_DATE=$($COMPOSE_CMD exec -T certbot openssl x509 -in "/etc/letsencrypt/live/$SSL_DOMAIN/fullchain.pem" -noout -enddate | cut -d'=' -f2)
            log_info "憑證有效期至: $EXPIRY_DATE"
        fi
        
        # 發送通知（如果設定了 Telegram）
    send_notification "SSL 憑證續簽成功" "網域 $SSL_DOMAIN 的 SSL 憑證已成功續簽"
        
    else
        log_error "憑證續簽失敗！"
    send_notification "SSL 憑證續簽失敗" "網域 $SSL_DOMAIN 的 SSL 憑證續簽失敗，請檢查設定"
        exit 1
    fi
else
    log_error "憑證續簽檢查失敗！"
    send_notification "SSL 憑證續簽檢查失敗" "網域 $SSL_DOMAIN 的 SSL 憑證續簽檢查失敗"
    exit 1
fi

log_success "SSL 憑證續簽流程完成"

# 函式：發送 Telegram 通知
send_notification() {
    local title="$1"
    local message="$2"
    
    # 讀取 Telegram 設定
    if [ -f .env ]; then
        TELEGRAM_BOT_TOKEN=$(grep "TELEGRAM_BOT_TOKEN=" .env | cut -d'=' -f2)
        TELEGRAM_CHAT_ID=$(grep "TELEGRAM_CHAT_ID=" .env | cut -d'=' -f2)
        
        if [ -n "$TELEGRAM_BOT_TOKEN" ] && [ -n "$TELEGRAM_CHAT_ID" ] && [ "$TELEGRAM_BOT_TOKEN" != "your-bot-token" ]; then
            local full_message="🔒 *$title*\n\n$message\n\n⏰ 時間: $(date '+%Y-%m-%d %H:%M:%S')\n🖥️ 伺服器: $(hostname)"
            
            curl -s -X POST "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/sendMessage" \
                -d chat_id="$TELEGRAM_CHAT_ID" \
                -d text="$full_message" \
                -d parse_mode="Markdown" > /dev/null 2>&1 || true
        fi
    fi
}

# 清理舊日誌（保留最近 30 天）
find "$(dirname "$LOG_FILE")" -name "*.log" -type f -mtime +30 -delete 2>/dev/null || true

log_info "SSL 憑證續簽腳本執行完畢"
