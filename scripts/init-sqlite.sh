#!/bin/bash

# SQLite 資料庫初始化腳本
# 建立資料庫檔案並執行遷移

set -e

# 設定變數
DB_DIR="/var/www/html/database"
DB_FILE="$DB_DIR/alleynote.db"
MIGRATIONS_DIR="$DB_DIR/migrations"

echo "正在初始化 SQLite 資料庫..."

# 建立資料庫目錄
mkdir -p "$DB_DIR"

# 檢查資料庫檔案是否存在
if [ ! -f "$DB_FILE" ]; then
    echo "建立新的 SQLite 資料庫檔案: $DB_FILE"
    touch "$DB_FILE"
    chmod 664 "$DB_FILE"
    chown www-data:www-data "$DB_FILE"
else
    echo "資料庫檔案已存在: $DB_FILE"
fi

# 執行 PHP 遷移檔案
echo "執行資料庫遷移..."

# 執行 PHP 遷移檔案
for migration in "$MIGRATIONS_DIR"/*.php; do
    if [ -f "$migration" ]; then
        echo "執行遷移: $(basename "$migration")"
        php -r "
            require_once '$migration';
            \$className = pathinfo('$migration', PATHINFO_FILENAME);
            \$className = ucfirst(str_replace('_', '', \$className));
            
            try {
                \$pdo = new PDO('sqlite:$DB_FILE');
                \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                \$pdo->exec('PRAGMA foreign_keys = ON');
                
                \$migration = new \$className();
                \$migration->up(\$pdo);
                echo \"遷移 \$className 執行成功\n\";
            } catch (Exception \$e) {
                echo \"遷移 \$className 執行失敗: \" . \$e->getMessage() . \"\n\";
                exit(1);
            }
        "
    fi
done

# 執行 SQL 遷移檔案
for migration in "$MIGRATIONS_DIR"/*.sql; do
    if [ -f "$migration" ]; then
        echo "執行 SQL 遷移: $(basename "$migration")"
        sqlite3 "$DB_FILE" < "$migration"
    fi
done

# 設定正確的權限
chmod 664 "$DB_FILE"
chown www-data:www-data "$DB_FILE"

echo "SQLite 資料庫初始化完成！"
echo "資料庫檔案位置: $DB_FILE"

# 顯示資料庫資訊
echo ""
echo "資料庫表格列表："
sqlite3 "$DB_FILE" ".tables"

echo ""
echo "資料庫大小："
ls -lh "$DB_FILE"
