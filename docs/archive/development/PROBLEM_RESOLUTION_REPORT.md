# 問題解決報告

## 日期
2025-10-04

## 問題描述
訪問 `localhost:80` 時顯示「File not found.」錯誤，無法看到前端頁面和 Swagger API 文件。

## 根本原因
**端口衝突**：系統的端口 80 被其他服務或 OrbStack 代理層佔用，導致請求被錯誤地路由到 PHP 應用程式而不是 nginx 靜態文件服務器。

## 診斷過程

### 1. 初步檢查
- ✅ Docker 容器都正常運行
- ✅ Nginx 配置文件語法正確
- ✅ Frontend 靜態文件存在於 `frontend/dist` 目錄
- ✅ 靜態文件已正確掛載到 nginx 容器內的 `/usr/share/nginx/html`

### 2. 關鍵發現
```bash
# 從主機訪問 localhost:80
$ curl -I http://localhost/
HTTP/1.1 404 Not Found
X-Powered-By: PHP/8.4.11  # ❌ 請求被發送到 PHP！
Content-Security-Policy: ... https://unpkg.com ...

# 從 Docker 網絡內部訪問 nginx
$ docker compose exec web curl -H 'Host: localhost' http://nginx/
<!DOCTYPE html>  # ✅ 正確回應 HTML！
```

這證明了 nginx 配置完全正確，問題在於端口映射或網絡層面。

### 3. 解決方案測試
將 nginx 的端口映射從 `80:80` 改為 `8080:80`：

```yaml
# docker-compose.yml
nginx:
    ports:
        - "8080:80"  # 改為 8080
        - "443:443"
```

結果：
- ✅ `http://localhost:8080/` 成功顯示前端頁面
- ✅ `http://localhost:8080/api/docs/ui` 成功顯示 Swagger 文件
- ✅ `http://localhost:8080/api/health` API 正常工作

## 解決方案

### 當前配置（推薦）
使用端口 8080 訪問應用程式：
- **前端**: http://localhost:8080/
- **API 文件**: http://localhost:8080/api/docs/ui  
- **API 端點**: http://localhost:8080/api/

### 替代方案（如需使用端口 80）
1. **找出佔用端口 80 的服務**：
   ```bash
   lsof -i:80
   ```

2. **停止佔用端口的服務**：
   - 如果是 OrbStack，可能需要調整 OrbStack 設置
   - 如果是其他服務，停止該服務

3. **恢復端口 80**：
   ```yaml
   # docker-compose.yml
   nginx:
       ports:
           - "80:80"
           - "443:443"
   ```

## 驗證結果

### ✅ 前端頁面
- 頁面標題正確顯示：「AlleyNote - 現代化 Web 開發展示平台」
- 所有內容區塊正常載入：
  - 核心功能特色
  - 技術架構堆疊
  - API 演示
  - 效能指標
- 樣式和布局正確

### ✅ API 功能
- 健康檢查端點正常：
  ```json
  {
    "status": "ok",
    "timestamp": "2025-10-04T10:13:17+08:00",
    "service": "AlleyNote API"
  }
  ```
- 前端 API 測試按鈕正常工作
- 系統狀態顯示正確

### ✅ Swagger API 文件
- 完整的 API 文件載入成功
- 包含所有 API 端點：
  - Activity Log API
  - Posts 文章管理 API
  - Statistics 統計 API
  - Auth 身份驗證 API
  - Attachments 附件管理 API
  - Health 健康檢查 API

## 技術細節

### Nginx 配置驗證
配置文件 `docker/nginx/frontend-backend.conf` 完全正確：
- `root /usr/share/nginx/html;` - 正確指向靜態文件目錄
- `index index.html;` - 正確設置索引文件
- `try_files $uri $uri/ /index.html;` - 正確的 SPA 路由配置
- `/api` location 正確代理到 PHP-FPM

### 網絡診斷
- 容器內部網絡正常
- Docker 端口映射正常
- 問題出在主機端口 80 的網絡層面

## 建議

### 短期方案
繼續使用端口 8080，這是最快且最可靠的解決方案。

### 長期方案
1. 調查並解決端口 80 的衝突問題
2. 如果 OrbStack 是原因，考慮配置 OrbStack 的網絡設置
3. 更新文件和配置，明確說明開發環境使用的端口

## 相關文件
- `docker-compose.yml` - 已更新端口映射為 8080
- `docker/nginx/frontend-backend.conf` - Nginx 配置（未修改，配置正確）
- `frontend/dist/` - 前端靜態文件（已確認存在且正確）

## 結論
問題成功解決。專案網站現在可以通過 `http://localhost:8080/` 正常訪問，所有功能均正常運作。
