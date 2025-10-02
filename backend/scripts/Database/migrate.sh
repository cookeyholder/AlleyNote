#!/bin/bash

# AlleyNote Migration Script
# 用於執行資料庫 migration

set -e

# 取得腳本所在目錄
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# 進入專案目錄
cd "$PROJECT_DIR"

# 檢查是否有 phinx
if [ ! -f "vendor/bin/phinx" ]; then
    echo "錯誤: 找不到 Phinx，請先執行 composer install"
    exit 1
fi

# 檢查是否有 phinx.php 配置檔
if [ ! -f "phinx.php" ]; then
    echo "錯誤: 找不到 phinx.php 配置檔"
    exit 1
fi

# 函數：顯示說明
show_help() {
    echo "AlleyNote Migration 工具"
    echo ""
    echo "用法: $0 [命令] [選項]"
    echo ""
    echo "命令:"
    echo "  migrate      執行 migration (預設)"
    echo "  rollback     回滾上一個 migration"
    echo "  status       顯示 migration 狀態"
    echo "  create       建立新的 migration"
    echo "  seed         執行 seed"
    echo ""
    echo "選項:"
    echo "  -e, --env    指定環境 (development|testing|production，預設: development)"
    echo "  -t, --target 指定 migration 版本"
    echo "  -h, --help   顯示此說明"
    echo ""
    echo "範例:"
    echo "  $0 migrate"
    echo "  $0 migrate -e testing"
    echo "  $0 rollback"
    echo "  $0 status"
    echo "  $0 create CreateUsersTable"
    echo "  $0 seed"
}

# 預設參數
COMMAND="migrate"
ENVIRONMENT="development"
TARGET=""
MIGRATION_NAME=""

# 解析命令列參數
while [[ $# -gt 0 ]]; do
    case $1 in
        migrate|rollback|status|create|seed)
            COMMAND="$1"
            shift
            ;;
        -e|--env)
            ENVIRONMENT="$2"
            shift 2
            ;;
        -t|--target)
            TARGET="$2"
            shift 2
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            if [ "$COMMAND" = "create" ] && [ -z "$MIGRATION_NAME" ]; then
                MIGRATION_NAME="$1"
            fi
            shift
            ;;
    esac
done

# 驗證環境參數
if [[ ! "$ENVIRONMENT" =~ ^(development|testing|production)$ ]]; then
    echo "錯誤: 無效的環境 '$ENVIRONMENT'，可用的環境: development, testing, production"
    exit 1
fi

echo "使用環境: $ENVIRONMENT"

# 執行對應的命令
case $COMMAND in
    migrate)
        echo "執行 migration..."
        if [ -n "$TARGET" ]; then
            vendor/bin/phinx migrate -e "$ENVIRONMENT" -t "$TARGET"
        else
            vendor/bin/phinx migrate -e "$ENVIRONMENT"
        fi
        echo "Migration 完成"
        ;;
    rollback)
        echo "回滾 migration..."
        if [ -n "$TARGET" ]; then
            vendor/bin/phinx rollback -e "$ENVIRONMENT" -t "$TARGET"
        else
            vendor/bin/phinx rollback -e "$ENVIRONMENT"
        fi
        echo "回滾完成"
        ;;
    status)
        echo "Migration 狀態:"
        vendor/bin/phinx status -e "$ENVIRONMENT"
        ;;
    create)
        if [ -z "$MIGRATION_NAME" ]; then
            echo "錯誤: 請提供 migration 名稱"
            echo "用法: $0 create <MigrationName>"
            exit 1
        fi
        echo "建立 migration: $MIGRATION_NAME"
        vendor/bin/phinx create "$MIGRATION_NAME"
        echo "Migration 檔案已建立"
        ;;
    seed)
        echo "執行 seed..."
        vendor/bin/phinx seed:run -e "$ENVIRONMENT"
        echo "Seed 完成"
        ;;
    *)
        echo "錯誤: 未知的命令 '$COMMAND'"
        show_help
        exit 1
        ;;
esac

echo "操作完成"
