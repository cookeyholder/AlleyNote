# AlleyNote 快速開始指南

## 問題已解決 ✅

**問題**：無法正常訪問 http://localhost:3000，因為 Vite 開發伺服器佔用了端口。

**解決方案**：終止了 Vite 開發伺服器，現在 Docker nginx 容器正常提供前端服務。

## 當前架構

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

## 啟動服務

```bash
# 進入專案目錄
cd /Users/cookeyholder/projects/AlleyNote

# 啟動所有服務
docker-compose up -d

# 檢查服務狀態
docker-compose ps

# 查看日誌
docker-compose logs -f
```

## 訪問應用

- **前端**：http://localhost:3000
- **API**：http://localhost:8080/api
- **Redis**：localhost:6379

## 開發前端

前端採用純 HTML/JS/CSS 架構，無需構建工具：

1. **編輯檔案**
   ```bash
   # 直接編輯 frontend/ 目錄下的檔案
   vim frontend/js/pages/public/home.js
   ```

2. **即時生效**
   - 由於使用 bind mount，修改會立即反映在容器中
   - 重新整理瀏覽器即可看到變更

3. **除錯**
   - 使用瀏覽器 DevTools
   - 查看控制台輸出
   - 檢查 Network 標籤

## 開發後端

後端採用 PHP + Slim Framework + DDD 架構：

1. **執行測試**
   ```bash
   docker-compose exec web composer test
   ```

2. **程式碼風格檢查**
   ```bash
   docker-compose exec web composer cs-fix
   ```

3. **靜態分析**
   ```bash
   docker-compose exec web composer analyse
   ```

4. **完整 CI 檢查**
   ```bash
   docker-compose exec web composer ci
   ```

## 預設帳號

**主管理員**
- 帳號：`admin`
- 密碼：`admin123`

**一般使用者**
- 帳號：`user`
- 密碼：`user123`

## 資料庫

- **類型**：SQLite 3
- **位置**：`backend/database/alleynote.sqlite3`
- **容器內**：`/var/www/html/database/alleynote.sqlite3`

**查看資料庫**：
```bash
# 進入容器
docker-compose exec web bash

# 查看資料庫
sqlite3 database/alleynote.sqlite3
.tables
.exit
```

## 常見問題

### 1. 端口被佔用

**錯誤**：Cannot assign requested address

**解決方案**：
```bash
# 檢查佔用 3000 端口的程序
lsof -i :3000

# 終止程序（替換 <PID> 為實際的程序 ID）
kill <PID>

# 重啟容器
docker-compose restart nginx
```

### 2. API 無法連線

**檢查**：
```bash
# 測試 API 是否正常
curl http://localhost:8080/api/health

# 查看 nginx 日誌
docker-compose logs nginx

# 查看 PHP 日誌
docker-compose logs web
```

### 3. 前端無法載入

**檢查**：
```bash
# 確認檔案存在
ls -la frontend/index.html

# 確認容器中的檔案
docker exec alleynote_nginx ls -la /usr/share/nginx/html/

# 測試 nginx
curl -I http://localhost:3000
```

### 4. 登入失敗

**可能原因**：
- JWT 金鑰未設定
- 資料庫中沒有使用者
- 密碼錯誤

**解決方案**：
```bash
# 檢查環境變數
docker-compose exec web env | grep JWT

# 重新執行種子資料
docker-compose exec web php vendor/bin/phinx seed:run
```

## 重要提醒

⚠️ **不要啟動 Vite 開發伺服器**

前端已經改為純 HTML/JS/CSS，不需要也不應該使用 Vite：

```bash
# ❌ 錯誤：不要執行這些命令
npm run dev
npm run frontend:dev
vite

# ✅ 正確：使用 Docker
docker-compose up -d
```

## 下一步

1. ✅ 前端架構已經從 Vite 遷移到純 HTML/JS/CSS
2. ✅ Docker 部署環境已經正確配置
3. ⏳ 需要完成文章編輯器功能
4. ⏳ 需要完成使用者管理模組
5. ⏳ 需要完成角色管理模組
6. ⏳ 需要修復未發布文章在首頁顯示的問題

詳細的開發計劃和狀態報告請參考：
- `FRONTEND_STATUS_REPORT.md` - 前端完整狀態報告
- `USER_MANAGEMENT_TEST_REPORT.md` - 使用者管理測試報告
- `NEXT_STEPS.md` - 下一步開發計劃

## 獲取幫助

如有問題，請：
1. 查看日誌：`docker-compose logs -f`
2. 檢查狀態：`docker-compose ps`
3. 查看文件：`FRONTEND_STATUS_REPORT.md`
4. 開啟 issue
