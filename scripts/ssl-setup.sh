#!/bin/bash

# SSL 設定腳本 - Let's Encrypt 自動化管理
# 使用方法: ./scripts/ssl-setup.sh [domain] [email]

set -e

# 設定預設值
DEFAULT_DOMAIN="localhost"
DEFAULT_EMAIL="admin@localhost"

# 從參數或環境變數取得設定
DOMAIN="${1:-${SSL_DOMAIN:-$DEFAULT_DOMAIN}}"
EMAIL="${2:-${SSL_EMAIL:-$DEFAULT_EMAIL}}"
STAGING="${CERTBOT_STAGING:-true}"

# 顏色輸出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 函式：輸出訊息
log_info() {
    echo -e "${BLUE}[資訊]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[成功]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[警告]${NC} $1"
}

log_error() {
    echo -e "${RED}[錯誤]${NC} $1"
}

# 函式：檢查必要條件
check_requirements() {
    log_info "檢查必要條件..."
    
    # 檢查 Docker 和 Docker Compose
    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安裝！請先安裝 Docker。"
        exit 1
    fi
    
    # Compose detection: prefer `docker compose` plugin
    if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        COMPOSE_CMD="docker compose"
    elif command -v docker-compose >/dev/null 2>&1; then
        COMPOSE_CMD="docker-compose"
    else
        log_error "Docker Compose 未安裝！請先安裝 Docker Compose (docker compose 或 docker-compose)。"
        exit 1
    fi
    
    # 檢查網域設定
    if [ "$DOMAIN" = "localhost" ]; then
        log_warning "網域設定為 localhost，SSL 將只能在開發環境使用"
        log_warning "正式環境請設定真實網域名稱"
        read -p "是否繼續？(y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    log_success "必要條件檢查完成"
}

# 函式：建立必要目錄
create_directories() {
    log_info "建立 SSL 相關目錄..."
    
    mkdir -p ssl-data
    mkdir -p certbot-data
    mkdir -p logs/nginx
    
    # 設定權限
    chmod 755 ssl-data certbot-data logs
    
    log_success "目錄建立完成"
}

# 函式：更新環境變數檔案
update_env_file() {
    log_info "更新環境變數設定..."
    
    if [ ! -f .env ]; then
        cp .env.example .env
        log_info "建立 .env 檔案"
    fi
    
    # 更新 SSL 相關設定
    sed -i.bak "s/SSL_DOMAIN=.*/SSL_DOMAIN=$DOMAIN/" .env
    sed -i.bak "s/SSL_EMAIL=.*/SSL_EMAIL=$EMAIL/" .env
    sed -i.bak "s/CERTBOT_STAGING=.*/CERTBOT_STAGING=$STAGING/" .env
    
    # 更新 APP_URL
    if [ "$STAGING" = "false" ] && [ "$DOMAIN" != "localhost" ]; then
        sed -i.bak "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env
    fi
    
    log_success "環境變數更新完成"
}

# 函式：準備 Nginx 設定
prepare_nginx_config() {
    log_info "準備 Nginx 設定檔案..."
    
    # 建立臨時的 SSL 設定檔案，替換環境變數
    envsubst '${SSL_DOMAIN}' < docker/nginx/ssl.conf > docker/nginx/ssl.conf.tmp
    mv docker/nginx/ssl.conf.tmp docker/nginx/ssl_prepared.conf
    
    log_success "Nginx 設定準備完成"
}

# 函式：初次申請憑證
request_certificate() {
    log_info "申請 SSL 憑證..."
    
    if [ "$STAGING" = "true" ]; then
        log_warning "使用 Let's Encrypt 測試環境（Staging）"
        STAGING_FLAG="--staging"
    else
        log_info "使用 Let's Encrypt 正式環境"
        STAGING_FLAG=""
    fi
    
    # 啟動基本服務
    $COMPOSE_CMD up -d web nginx database redis
    
    # 等待服務啟動
    log_info "等待服務啟動..."
    sleep 10
    
    # 申請憑證
    $COMPOSE_CMD run --rm certbot certonly \
        --webroot \
        --webroot-path=/var/www/certbot \
        --email "$EMAIL" \
        --agree-tos \
        --no-eff-email \
        $STAGING_FLAG \
        -d "$DOMAIN" || {
        log_error "憑證申請失敗！"
        log_info "常見原因："
        log_info "1. 網域名稱解析錯誤"
        log_info "2. 防火牆阻擋 80/443 埠"
        log_info "3. 網域已有其他服務佔用"
        exit 1
    }
    
    log_success "SSL 憑證申請成功！"
}

# 函式：設定自動續簽
setup_auto_renewal() {
    log_info "設定自動續簽..."
    
    # 建立續簽腳本
    cat > scripts/ssl-renew.sh << 'EOF'
#!/bin/bash
# SSL 憑證自動續簽腳本

cd "$(dirname "$0")/.."

echo "$(date): 開始檢查 SSL 憑證續簽..."

# 嘗試續簽憑證
if $COMPOSE_CMD run --rm certbot renew --quiet; then
    echo "$(date): 憑證續簽檢查完成"
    # 重新載入 Nginx 設定
    $COMPOSE_CMD exec nginx nginx -s reload
    echo "$(date): Nginx 設定重新載入完成"
else
    echo "$(date): 憑證續簽失敗！"
    exit 1
fi
EOF
    
    chmod +x scripts/ssl-renew.sh
    
    # 設定 Cron Job（可選）
    log_info "建議設定以下 Cron Job 進行自動續簽："
    log_info "0 2 * * 1 cd $(pwd) && ./scripts/ssl-renew.sh >> logs/ssl-renewal.log 2>&1"
    
    log_success "自動續簽腳本建立完成"
}

# 函式：啟動完整服務
start_services() {
    log_info "啟動完整的 SSL 服務..."
    
    # 重新啟動所有服務
    $COMPOSE_CMD down
    $COMPOSE_CMD up -d
    
    # 等待服務啟動
    sleep 15
    
    log_success "服務啟動完成！"
}

# 函式：驗證 SSL 設定
verify_ssl() {
    log_info "驗證 SSL 設定..."
    
    # 檢查憑證檔案
    if [ -f "ssl-data/live/$DOMAIN/fullchain.pem" ]; then
        log_success "SSL 憑證檔案存在"
        
    # 顯示憑證資訊
    log_info "憑證資訊："
    $COMPOSE_CMD exec -T certbot openssl x509 -in "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" -text -noout | grep -E "(Subject:|Not After:|Issuer:)"
    else
        log_warning "SSL 憑證檔案不存在"
    fi
    
    # 測試 HTTPS 連線（如果不是 localhost）
    if [ "$DOMAIN" != "localhost" ]; then
        log_info "測試 HTTPS 連線..."
        if curl -Is "https://$DOMAIN" > /dev/null 2>&1; then
            log_success "HTTPS 連線測試成功！"
        else
            log_warning "HTTPS 連線測試失敗，請檢查網域解析和防火牆設定"
        fi
    fi
}

# 函式：顯示使用說明
show_usage() {
    echo "SSL 設定腳本使用說明："
    echo "  ./scripts/ssl-setup.sh [網域名稱] [電子郵件]"
    echo ""
    echo "範例："
    echo "  ./scripts/ssl-setup.sh example.com admin@example.com"
    echo "  ./scripts/ssl-setup.sh localhost admin@localhost"
    echo ""
    echo "環境變數："
    echo "  SSL_DOMAIN     - SSL 網域名稱"
    echo "  SSL_EMAIL      - 聯絡電子郵件"
    echo "  CERTBOT_STAGING - 是否使用測試環境 (true/false)"
}

# 主程式
main() {
    echo "========================================"
    echo "  AlleyNote SSL 設定工具"
    echo "  Let's Encrypt 自動化管理"
    echo "========================================"
    
    # 顯示設定資訊
    log_info "SSL 設定資訊："
    log_info "  網域名稱: $DOMAIN"
    log_info "  電子郵件: $EMAIL"
    log_info "  測試模式: $STAGING"
    echo ""
    
    # 詢問確認
    read -p "是否要繼續執行 SSL 設定？(y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "取消 SSL 設定"
        exit 0
    fi
    
    # 執行設定步驟
    check_requirements
    create_directories
    update_env_file
    prepare_nginx_config
    request_certificate
    setup_auto_renewal
    start_services
    verify_ssl
    
    echo ""
    echo "========================================"
    log_success "SSL 設定完成！"
    echo "========================================"
    
    log_info "接下來的步驟："
    log_info "1. 檢查服務狀態: docker compose ps"
    log_info "2. 查看日誌: docker compose logs -f nginx"
    if [ "$DOMAIN" != "localhost" ]; then
        log_info "3. 測試網站: https://$DOMAIN"
        log_info "4. 檢查 SSL 評級: https://www.ssllabs.com/ssltest/"
    fi
    log_info "5. 設定自動續簽 Cron Job"
    
    if [ "$STAGING" = "true" ]; then
        echo ""
        log_warning "注意：目前使用測試環境憑證"
        log_warning "正式環境請設定 CERTBOT_STAGING=false 並重新執行"
    fi
}

# 處理參數
case "${1:-}" in
    -h|--help)
        show_usage
        exit 0
        ;;
    *)
        main "$@"
        ;;
esac
