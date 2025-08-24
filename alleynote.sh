#!/bin/bash

# AlleyNote 專案管理腳本
# 提供常用的管理指令

set -e

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.yml}"
PROJECT_NAME="alleynote"

# Detect compose command (prefer "docker compose" if available)
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    echo "錯誤: 需要安裝 Docker Compose (docker compose 或 docker-compose)"
    exit 1
fi

show_help() {
    echo "AlleyNote 專案管理工具"
    echo ""
    echo "使用方法: $0 <指令> [選項]"
    echo ""
    echo "可用指令:"
    echo "  start                    啟動所有服務"
    echo "  stop                     停止所有服務"
    echo "  restart                  重啟所有服務"
    echo "  status                   檢查服務狀態"
    echo "  logs [service]           檢視日誌"
    echo "  shell                    進入 web 容器命令列"
    echo "  init                     初始化專案（首次設定）"
    echo "  init-db                  初始化 SQLite 資料庫"
    echo "  backup-db                備份 SQLite 資料庫"
    echo "  restore-db <file>        還原 SQLite 資料庫"
    echo "  ssl-setup <domain> <email> 設定 SSL 憑證"
    echo "  ssl-renew                手動續簽 SSL 憑證"
    echo "  test                     執行所有測試"
    echo "  clean                    清理未使用的容器和映像檔"
    echo "  update                   更新並重建容器"
    echo ""
    echo "環境變數:"
    echo "  COMPOSE_FILE             Docker Compose 設定檔 (預設: docker-compose.yml)"
    echo ""
    echo "範例:"
    echo "  $0 start                 # 啟動開發環境"
    echo "  $0 init                  # 首次初始化專案"
    echo "  $0 ssl-setup example.com admin@example.com"
    echo "  $0 backup-db             # 備份資料庫"
    echo "  COMPOSE_FILE=docker-compose.production.yml $0 start  # 啟動正式環境"
}

check_requirements() {
    command -v docker >/dev/null 2>&1 || { echo "錯誤: 需要安裝 Docker"; exit 1; }
    # COMPOSE_CMD already detected at script start
}

wait_for_service() {
    local service=$1
    local max_attempts=${2:-30}
    local attempt=1
    
    echo "等待 $service 服務啟動..."
    
    while [ $attempt -le $max_attempts ]; do
    if $COMPOSE_CMD -f "$COMPOSE_FILE" ps "$service" | grep -q "Up"; then
            echo "$service 服務已啟動"
            return 0
        fi
        echo "等待中... ($attempt/$max_attempts)"
        sleep 2
        attempt=$((attempt + 1))
    done
    
    echo "錯誤: $service 服務啟動超時"
    return 1
}

case "${1:-help}" in
    "start")
        echo "啟動 AlleyNote 服務..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" up -d
    wait_for_service web
        echo "服務啟動完成！"
        echo "網站: http://localhost"
        ;;
        
    "stop")
        echo "停止 AlleyNote 服務..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" down
        echo "服務已停止"
        ;;
        
    "restart")
        echo "重啟 AlleyNote 服務..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" restart
        echo "服務已重啟"
        ;;
        
    "status")
        echo "AlleyNote 服務狀態:"
    $COMPOSE_CMD -f "$COMPOSE_FILE" ps
        ;;
        
    "logs")
        if [ -n "$2" ]; then
            $COMPOSE_CMD -f "$COMPOSE_FILE" logs -f "$2"
        else
            $COMPOSE_CMD -f "$COMPOSE_FILE" logs -f
        fi
        ;;
        
    "shell")
        echo "進入 web 容器..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec web bash
        ;;
        
    "init")
        echo "初始化 AlleyNote 專案..."
        
        # 檢查 .env 檔案
        if [ ! -f ".env" ]; then
            echo "建立環境變數檔案..."
            cp .env.example .env
            echo "請編輯 .env 檔案設定您的配置"
        fi
        
        # 啟動服務
        echo "啟動服務..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" up -d
    wait_for_service web
        
        # 初始化資料庫
        echo "初始化資料庫..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T web ./scripts/init-sqlite.sh
        
        echo "專案初始化完成！"
        echo "網站: http://localhost"
        ;;
        
    "init-db")
        echo "初始化 SQLite 資料庫..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T web ./scripts/init-sqlite.sh
        ;;
        
    "backup-db")
        echo "備份 SQLite 資料庫..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T web ./scripts/backup_sqlite.sh
        ;;
        
    "restore-db")
        if [ -z "$2" ]; then
            echo "錯誤: 請指定備份檔案"
            echo "使用方法: $0 restore-db <備份檔案路徑>"
            exit 1
        fi
        echo "還原 SQLite 資料庫..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T web ./scripts/restore_sqlite.sh "$2"
        ;;
        
    "ssl-setup")
        if [ -z "$2" ] || [ -z "$3" ]; then
            echo "錯誤: 請提供網域名稱和電子郵件"
            echo "使用方法: $0 ssl-setup <domain> <email>"
            exit 1
        fi
        echo "設定 SSL 憑證..."
        ./scripts/ssl-setup.sh "$2" "$3"
        ;;
        
    "ssl-renew")
        echo "手動續簽 SSL 憑證..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T certbot certbot renew
    $COMPOSE_CMD -f "$COMPOSE_FILE" restart nginx
        echo "SSL 憑證續簽完成"
        ;;
        
    "test")
        echo "執行測試套件..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T web ./vendor/bin/phpunit
        ;;
        
    "clean")
        echo "清理未使用的 Docker 資源..."
        docker system prune -f
        docker volume prune -f
        echo "清理完成"
        ;;
        
    "update")
        echo "更新專案..."
        git pull
    $COMPOSE_CMD -f "$COMPOSE_FILE" build --no-cache
    $COMPOSE_CMD -f "$COMPOSE_FILE" up -d
        echo "更新完成"
        ;;
        
    "help"|"--help"|"-h")
        show_help
        ;;
        
    *)
        echo "錯誤: 未知的指令 '$1'"
        echo "使用 '$0 help' 查看可用指令"
        exit 1
        ;;
esac
