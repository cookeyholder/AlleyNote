# AlleyNote 快速開始指南

> 5 分鐘內啟動並運行 AlleyNote 專案

## 系統架構

```
┌─────────────────────────────────────────┐
│                                         │
│        瀏覽器 (http://localhost:3000)    │
│                                         │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│   Docker nginx 容器 (port 3000:80)      │
│                                         │
│   前端：純 HTML/JS/CSS                   │
│   目錄：/usr/share/nginx/html           │
│   掛載：./frontend                       │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│   Docker nginx 容器 (port 8080)         │
│                                         │
│   API：FastCGI 代理到 PHP 容器          │
│   端點：http://localhost:8080/api       │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│   Docker PHP 容器 (port 9000)           │
│                                         │
│   後端：Slim Framework + DDD            │
│   目錄：/var/www/html                   │
│   掛載：./backend                        │
└─────────────────────────────────────────┘
```

## 前置需求

- Docker 20.10+
- Docker Compose 2.0+
- 可用端口：3000（前端）、8080（API）、6379（Redis）

## 快速啟動

### 1. Clone 專案

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
```

### 2. 複製環境變數

```bash
cp backend/.env.example backend/.env
```

### 3. 啟動服務

```bash
docker-compose up -d
```

### 4. 初始化資料庫（首次啟動）

```bash
# 執行資料庫遷移
docker-compose exec web php vendor/bin/phinx migrate

# 載入種子資料（測試帳號和範例資料）
docker-compose exec web php vendor/bin/phinx seed:run
```

### 5. 訪問應用

- **前端**：http://localhost:3000
- **API 文件**：http://localhost:8080/api/docs/ui
- **健康檢查**：http://localhost:8080/api/health

### 6. 測試帳號

| 角色 | 電子信箱 | 密碼 |
|------|----------|------|
| 管理員 | admin@example.com | password |

## 開發指令

### 前端開發

前端使用原生 HTML/JavaScript/CSS，**無需構建工具**：

```bash
# 直接編輯檔案，刷新瀏覽器即可看到變更
vim frontend/index.html
vim frontend/js/main.js
vim frontend/css/main.css
```

### 後端開發

```bash
# 執行測試
docker-compose exec web composer test

# 程式碼風格檢查與修復
docker-compose exec web composer cs-fix

# 靜態分析
docker-compose exec web composer analyse

# 完整 CI 檢查
docker-compose exec web composer ci

# 查看日誌
docker-compose logs -f web

# 進入容器
docker-compose exec web bash
```

### 常用 Docker 指令

```bash
# 檢查服務狀態
docker-compose ps

# 重啟服務
docker-compose restart

# 停止服務
docker-compose down

# 重建容器
docker-compose up -d --build

# 查看所有日誌
docker-compose logs -f
```

## 故障排除

### 問題 1：端口已被佔用

**錯誤訊息**：
```
Error: bind: address already in use
```

**解決方案**：
```bash
# 檢查端口佔用（macOS/Linux）
lsof -i :3000
lsof -i :8080

# 終止佔用端口的程序
kill -9 <PID>

# 或修改 docker-compose.yml 使用其他端口
```

### 問題 2：容器無法啟動

**錯誤訊息**：
```
Error: container failed to start
```

**解決方案**：
```bash
# 查看容器日誌
docker-compose logs web
docker-compose logs nginx

# 重建容器
docker-compose down
docker-compose up -d --build

# 清理並重建
docker-compose down -v
docker-compose up -d
```

### 問題 3：無法訪問前端

**檢查步驟**：
```bash
# 1. 確認容器運行中
docker compose ps

# 2. 測試 nginx
curl -I http://localhost:3000

# 3. 查看 nginx 日誌
docker compose logs nginx

# 4. 確認文件存在
ls -la frontend/index.html
```

### 問題 4：API 回應 500 錯誤

**檢查步驟**：
```bash
# 1. 查看 PHP 錯誤日誌
docker compose logs web

# 2. 確認資料庫已初始化
docker compose exec web php vendor/bin/phinx status

# 3. 檢查環境變數
docker compose exec web env | grep -E "JWT|DATABASE"

# 4. 重新執行遷移
docker compose exec web php vendor/bin/phinx migrate
docker compose exec web php vendor/bin/phinx seed:run
```

### 問題 5：無法登入

**可能原因與解決**：

1. **種子資料未載入**
   ```bash
   docker compose exec web php vendor/bin/phinx seed:run
   ```

2. **JWT 金鑰未設定**
   ```bash
   # 檢查 backend/.env 檔案
   cat backend/.env | grep JWT_SECRET

   # 若未設定，新增隨機金鑰
   echo "JWT_SECRET=$(openssl rand -hex 32)" >> backend/.env
   docker-compose restart web
   ```

3. **快取問題**
   ```bash
   # 清除瀏覽器 localStorage
   # 開啟 DevTools > Application > Local Storage > 清除

   # 或重新整理頁面（Ctrl+Shift+R / Cmd+Shift+R）
   ```

### 問題 6：權限錯誤

**錯誤訊息**：
```
Permission denied
```

**解決方案**：
```bash
# 確保目錄權限正確
chmod -R 755 backend/database
chmod -R 755 frontend
chmod -R 777 backend/storage

# 確保資料庫檔案可寫入
chmod 666 backend/database/alleynote.sqlite3
```

## 技術支援

- **文件**：查看 `README.md` 了解專案架構
- **問題回報**：開啟 GitHub Issue
- **日誌分析**：`docker compose logs -f`

## 下一步

專案啟動成功後，您可以：

1. 瀏覽前端介面：http://localhost:3000
2. 使用測試帳號登入體驗功能
3. 查看 API 文件了解後端接口
4. 閱讀 `README.md` 了解專案架構
5. 查看 `docs/` 目錄獲取詳細文件
