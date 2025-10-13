# AlleyNote 快速開始指南

> 5 分鐘內啟動並運行 AlleyNote 專案

---

## 📋 系統需求

在開始之前，請確認您的系統符合以下需求：

### 必要軟體

- **Docker** 20.10 或更新版本
- **Docker Compose** 2.0 或更新版本
- **Git**（用於 clone 專案）

### 系統資源

- 至少 2GB RAM
- 至少 5GB 可用磁碟空間
- 可用端口：3000（前端）、8080（API）

### 檢查安裝

```bash
# 檢查 Docker 版本
docker --version

# 檢查 Docker Compose 版本
docker compose version

# 確認 Docker 服務運行中
docker ps
```

---

## 🚀 快速啟動（5 步驟）

### 步驟 1：Clone 專案

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
```

### 步驟 2：環境設定

```bash
# 複製環境變數範本
cp backend/.env.example backend/.env

# （可選）編輯 .env 檔案
# nano backend/.env
```

**重要環境變數**：
```env
# JWT 金鑰（建議修改為隨機字串）
JWT_SECRET=your-secret-key-here

# 資料庫路徑
DATABASE_PATH=/var/www/html/backend/database/alleynote.sqlite3

# 環境模式
APP_ENV=development
```

### 步驟 3：啟動 Docker 容器

```bash
# 啟動所有服務
docker compose up -d

# 等待容器啟動完成（約 10-20 秒）
# 檢查容器狀態
docker compose ps
```

**預期輸出**：
```
NAME                COMMAND                  SERVICE   STATUS    PORTS
alleynote-nginx-1   "/docker-entrypoint.…"   nginx     Up        0.0.0.0:3000->80/tcp, 0.0.0.0:8080->8080/tcp
alleynote-web-1     "docker-php-entrypoi…"   web       Up        9000/tcp
```

### 步驟 4：初始化資料庫

```bash
# 執行資料庫遷移
docker compose exec web php vendor/bin/phinx migrate

# 載入測試資料（包含預設帳號）
docker compose exec web php vendor/bin/phinx seed:run
```

**成功訊息**：
```
All Done. Took X.XXXs
```

### 步驟 5：訪問應用

開啟瀏覽器，訪問以下網址：

- **前端應用**：http://localhost:3000
- **API 文件**：http://localhost:8080/api/docs
- **健康檢查**：http://localhost:8080/api/health

---

## 🔑 預設帳號

系統提供三個測試帳號，對應不同權限等級：

| 角色 | 電子郵件 | 密碼 | 權限 |
|------|----------|------|------|
| **超級管理員** | admin@example.com | Admin@123456 | 完整系統權限 |
| **管理員** | manager@example.com | Manager@123 | 文章管理、使用者管理 |
| **編輯者** | editor@example.com | Editor@123 | 文章發布、編輯 |

**⚠️ 重要**：正式環境請立即更改預設密碼！

---

## ✅ 驗證安裝

### 1. 檢查前端

訪問 http://localhost:3000，應該看到登入頁面。

### 2. 檢查 API

```bash
# 測試健康檢查端點
curl http://localhost:8080/api/health

# 預期回應
{"status":"ok","timestamp":"2025-10-13T..."}
```

### 3. 測試登入

使用預設帳號登入前端，應該能進入管理後台。

---

## 🛠️ 常用指令

### Docker 管理

```bash
# 啟動服務
docker compose up -d

# 停止服務
docker compose down

# 重啟服務
docker compose restart

# 查看日誌
docker compose logs -f

# 查看特定容器日誌
docker compose logs -f web
docker compose logs -f nginx
```

### 開發指令

```bash
# 進入 PHP 容器
docker compose exec web bash

# 執行測試
docker compose exec web composer test

# 程式碼風格檢查
docker compose exec web composer cs-check

# 自動修復程式碼風格
docker compose exec web composer cs-fix

# 靜態分析
docker compose exec web composer analyse

# 完整 CI 檢查
docker compose exec web composer ci
```

### 資料庫管理

```bash
# 執行遷移
docker compose exec web php vendor/bin/phinx migrate

# 回滾遷移
docker compose exec web php vendor/bin/phinx rollback

# 重新載入種子資料
docker compose exec web php vendor/bin/phinx seed:run

# 檢查遷移狀態
docker compose exec web php vendor/bin/phinx status
```

---

## 🔧 故障排除

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
ports:
  - "3001:80"  # 改用 3001 端口
  - "8081:8080"  # 改用 8081 端口
```

### 問題 2：容器無法啟動

**檢查步驟**：

```bash
# 1. 查看容器日誌
docker compose logs web
docker compose logs nginx

# 2. 檢查 Docker 磁碟空間
docker system df

# 3. 清理並重建
docker compose down -v
docker compose up -d --build
```

### 問題 3：資料庫初始化失敗

**解決方案**：

```bash
# 1. 檢查資料庫檔案權限
ls -la backend/database/

# 2. 確保目錄存在且可寫入
mkdir -p backend/database
chmod 777 backend/database

# 3. 重新執行遷移
docker compose exec web php vendor/bin/phinx migrate -e development

# 4. 重新載入種子
docker compose exec web php vendor/bin/phinx seed:run -e development
```

### 問題 4：無法登入

**可能原因與解決**：

1. **JWT 金鑰未設定**
   ```bash
   # 檢查 .env 檔案
   cat backend/.env | grep JWT_SECRET
   
   # 設定隨機金鑰
   echo "JWT_SECRET=$(openssl rand -hex 32)" >> backend/.env
   docker compose restart web
   ```

2. **種子資料未載入**
   ```bash
   docker compose exec web php vendor/bin/phinx seed:run
   ```

3. **瀏覽器快取**
   - 清除瀏覽器 Local Storage
   - 強制重新整理（Ctrl+Shift+R / Cmd+Shift+R）

### 問題 5：權限錯誤

**錯誤訊息**：
```
Permission denied
```

**解決方案**：

```bash
# 設定正確的目錄權限
chmod -R 755 backend/database
chmod -R 755 frontend
chmod -R 777 backend/storage

# 設定資料庫檔案權限
chmod 666 backend/database/alleynote.sqlite3
```

---

## 📚 下一步

安裝完成後，您可以：

1. ✅ **瀏覽功能**：使用測試帳號體驗系統功能
2. 📖 **閱讀文件**：查看 [README.md](README.md) 了解完整功能
3. 🔧 **開始開發**：參考 [docs/guides/developer/](docs/guides/developer/) 開發指南
4. 📊 **查看統計**：訪問統計頁面了解數據分析功能
5. 🔐 **測試安全**：嘗試密碼強度驗證功能

---

## 💡 提示

### 開發模式 vs 生產模式

**開發模式**（預設）：
- 詳細的錯誤訊息
- 不啟用快取
- 自動重載程式碼

**生產模式**：
```env
# 修改 backend/.env
APP_ENV=production
APP_DEBUG=false
```
- 隱藏錯誤細節
- 啟用所有快取
- 優化效能

### 效能優化建議

```bash
# 建立資料庫索引（已自動執行）
docker compose exec web php vendor/bin/phinx migrate

# 清理快取
docker compose exec web rm -rf backend/storage/cache/*

# 優化 Composer autoload
docker compose exec web composer dump-autoload --optimize
```

---

## 🆘 需要幫助？

- 📖 **文件**：查看 [docs/](docs/) 目錄
- 🐛 **報告問題**：[GitHub Issues](https://github.com/cookeyholder/AlleyNote/issues)
- 💬 **討論**：[GitHub Discussions](https://github.com/cookeyholder/AlleyNote/discussions)

---

**🎉 恭喜！您已成功安裝 AlleyNote 系統！**
