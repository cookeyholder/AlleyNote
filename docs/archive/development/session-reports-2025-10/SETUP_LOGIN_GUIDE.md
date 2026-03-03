# AlleyNote 登入設定指南

## 🎯 問題解決方案

目前登入功能遇到 500 錯誤，但我已經為您設置好資料庫和測試帳號。

## ✅ 已完成的設置

### 1. 資料庫初始化

資料庫已創建並包含以下表結構：
- `users` - 使用者表
- `posts` - 文章表
- `tags` - 標籤表
- `post_tags` - 文章標籤關聯表

### 2. 測試帳號

已創建測試管理員帳號：

**帳號資訊**：
- Email: `admin@example.com`
- Password: `password`
- 使用者名稱: `admin`

## 🔧 如何使用登入功能

### 方法一：修復後端錯誤（推薦）

後端登入 API 目前回傳 500 錯誤。需要檢查以下項目：

1. **檢查 JWT 金鑰配置**
   ```bash
   # 進入後端目錄
   cd backend
   
   # 檢查 .env 檔案中的 JWT 設定
   cat .env | grep JWT
   ```
   
   確保有以下配置：
   ```
   JWT_PRIVATE_KEY_PATH=path/to/private_key.pem
   JWT_PUBLIC_KEY_PATH=path/to/public_key.pem
   JWT_ALGORITHM=RS256
   JWT_ACCESS_TOKEN_TTL=3600
   JWT_REFRESH_TOKEN_TTL=2592000
   ```

2. **檢查 RSA 金鑰檔案**
   ```bash
   # 檢查金鑰檔案是否存在
   ls -la backend/*.pem
   ```
   
   如果不存在，需要生成：
   ```bash
   cd backend
   openssl genrsa -out private_key.pem 2048
   openssl rsa -in private_key.pem -pubout -out public_key.pem
   chmod 600 private_key.pem
   chmod 644 public_key.pem
   ```

3. **重啟容器**
   ```bash
   docker compose restart web
   ```

### 方法二：使用 API 直接測試（用於診斷）

```bash
# 測試登入 API
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

成功時應該回傳：
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com"
    }
  }
}
```

### 方法三：新增更多測試帳號

可以手動新增其他測試帳號：

```bash
docker run --rm -v "$(pwd)/backend:/app" -w /app php:8.4-cli php -r "
\$pdo = new PDO('sqlite:database/alleynote.sqlite3');
\$hash = password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT);
\$pdo->exec(\"
  INSERT INTO users (username, email, password_hash, created_at, updated_at) 
  VALUES ('USERNAME', 'EMAIL@example.com', '\$hash', datetime('now'), datetime('now'))
\");
echo '✅ 帳號已創建\n';
"
```

## 🔍 除錯步驟

### 1. 檢查後端日誌

```bash
# 查看 PHP-FPM 日誌
docker compose logs --tail=50 web

# 查看 nginx 日誌
docker compose logs --tail=50 nginx
```

### 2. 檢查資料庫內容

```bash
# 查看現有使用者
docker run --rm -v "$(pwd)/backend:/app" -w /app php:8.4-cli php -r "
\$pdo = new PDO('sqlite:database/alleynote.sqlite3');
\$stmt = \$pdo->query('SELECT id, username, email FROM users');
print_r(\$stmt->fetchAll(PDO::FETCH_ASSOC));
"
```

### 3. 測試 API 健康狀態

```bash
# 測試健康檢查端點
curl http://localhost:8080/api/health

# 測試根路徑
curl http://localhost:8080/api/
```

## 📝 資料庫結構

### Users 表

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME
);
```

### 新增使用者的 SQL

```sql
INSERT INTO users (username, email, password_hash, created_at, updated_at) 
VALUES (
    'admin', 
    'admin@example.com', 
    '$2y$10$...',  -- password_hash('password', PASSWORD_BCRYPT)
    datetime('now'), 
    datetime('now')
);
```

## 🚀 快速開始（臨時解決方案）

如果後端登入功能暫時無法修復，您可以：

1. **直接跳過登入頁面**（開發模式）
   - 修改前端路由守衛，暫時允許未登入訪問
   - 在 localStorage 中手動設定假的 token

2. **使用 Postman 或 curl 測試 API**
   - 直接使用 API 端點測試功能
   - 繞過前端介面驗證後端邏輯

3. **檢查 AuthController**
   ```bash
   # 查看 AuthController 程式碼
   cat backend/app/Application/Controllers/Api/V1/AuthController.php
   ```

## 📚 相關文件

- [前端使用者指南](docs/FRONTEND_USER_GUIDE.md)
- [前端建置修復記錄](FRONTEND_BUILD_FIX.md)
- [API 文件](http://localhost:8080/api/docs/ui)

## 🐛 已知問題

1. **500 錯誤**：登入 API 回傳系統錯誤
   - 原因：可能是 JWT 金鑰配置或其他後端邏輯問題
   - 狀態：待修復
   - 優先級：高

2. **日誌檔案缺失**：應用程式未產生錯誤日誌
   - 原因：storage/logs 目錄權限或配置問題
   - 狀態：已創建目錄，待觀察

## 📞 下一步行動

建議按以下順序進行：

1. ✅ 資料庫已初始化
2. ✅ 測試帳號已創建
3. ⏳ 修復後端登入 API 錯誤
4. ⏳ 測試完整登入流程
5. ⏳ 新增更多功能測試

---

**建立日期**：2025-01-05  
**狀態**：進行中  
**最後更新**：2025-01-05 20:00
