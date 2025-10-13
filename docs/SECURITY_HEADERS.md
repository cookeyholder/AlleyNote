# HTTP 安全標頭實作文件

## 概述

本文件說明 AlleyNote 專案中實作的 HTTP 安全標頭配置，以增強應用程式的安全性。

## 實作方式

本專案採用**雙層安全標頭策略**：

### 1. Nginx 層級（前端 Port 80）
- 前端靜態資源的安全標頭在 Nginx 配置中設置
- 配置文件：`docker/nginx/frontend-backend.conf`

### 2. PHP 應用層級（API Port 8080）
- API 端點的安全標頭在 PHP 應用程式入口設置
- 配置文件：`backend/public/index.php`
- **原因**：由於 FastCGI 的特性，Nginx 層級的 `add_header` 指令可能被 PHP 回應覆蓋，因此在 PHP 層面直接設置標頭更為可靠

## 已實作的安全標頭

### 1. X-Frame-Options
- **開發環境**: `SAMEORIGIN`
- **生產環境**: `DENY`
- **作用**: 防止點擊劫持（Clickjacking）攻擊
- **說明**: 
  - `SAMEORIGIN`: 允許同源頁面嵌入框架
  - `DENY`: 完全禁止在框架中顯示頁面

### 2. X-Content-Type-Options
- **值**: `nosniff`
- **作用**: 防止瀏覽器進行 MIME 類型嗅探
- **說明**: 強制瀏覽器遵循 Content-Type 標頭宣告的類型

### 3. X-XSS-Protection
- **值**: `1; mode=block`
- **作用**: 啟用瀏覽器內建的 XSS 過濾器
- **說明**: 當檢測到 XSS 攻擊時，阻止頁面載入

### 4. Referrer-Policy
- **值**: `strict-origin-when-cross-origin`
- **作用**: 控制 Referer 標頭的發送
- **說明**: 同源請求發送完整 URL，跨源請求僅發送來源

### 5. Strict-Transport-Security (HSTS)
- **值**: `max-age=31536000; includeSubDomains; preload` (生產環境)
- **作用**: 強制使用 HTTPS 連線
- **說明**: 
  - `max-age=31536000`: 一年內強制 HTTPS
  - `includeSubDomains`: 包含所有子網域
  - `preload`: 可加入 HSTS 預載清單

### 6. Content-Security-Policy (CSP)

#### 開發環境配置
```
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.ckeditor.com https://cdn.tailwindcss.com https://cdn.jsdelivr.net;
style-src 'self' 'unsafe-inline' https://cdn.ckeditor.com https://cdn.tailwindcss.com https://fonts.googleapis.com https://cdnjs.cloudflare.com;
img-src 'self' data: blob: https: http:;
font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.ckeditor.com;
connect-src 'self' http://localhost:8080;
frame-src 'self';
object-src 'none';
base-uri 'self';
form-action 'self';
```

#### 生產環境配置
```
default-src 'self';
script-src 'self' https://cdn.ckeditor.com https://cdn.jsdelivr.net;
style-src 'self' 'unsafe-inline' https://cdn.ckeditor.com https://fonts.googleapis.com https://cdnjs.cloudflare.com;
img-src 'self' data: blob: https:;
font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.ckeditor.com;
connect-src 'self' https://cdn.ckeditor.com;
frame-src 'self';
object-src 'none';
base-uri 'self';
form-action 'self';
upgrade-insecure-requests;
```

**注意事項**:
- 開發環境允許 `unsafe-inline` 和 `unsafe-eval` 以支援熱重載和除錯
- 生產環境移除 `unsafe-inline` 和 `unsafe-eval` 以提高安全性
- 生產環境加入 `upgrade-insecure-requests` 自動升級不安全請求

### 7. Cross-Origin-Opener-Policy (COOP)
- **開發環境**: `same-origin-allow-popups`
- **生產環境**: `same-origin`
- **作用**: 隔離瀏覽器上下文群組
- **說明**: 防止跨源文件獲取視窗參照

### 8. Cross-Origin-Resource-Policy (CORP)
- **值**: `same-origin`
- **作用**: 防止資源被跨源載入
- **說明**: 限制資源僅能被同源頁面存取

### 9. Permissions-Policy
- **值**: `geolocation=(), microphone=(), camera=()`
- **作用**: 控制瀏覽器功能的使用權限
- **說明**: 禁用地理位置、麥克風和相機功能

## API 端點安全標頭

API 端點 (Port 8080) 配置了以下安全標頭：

1. **CORS 標頭**:
   - `Access-Control-Allow-Origin`: 限制為前端來源
   - `Access-Control-Allow-Methods`: 指定允許的 HTTP 方法
   - `Access-Control-Allow-Headers`: 指定允許的請求標頭
   - `Access-Control-Allow-Credentials`: 允許攜帶憑證

2. **基本安全標頭**:
   - `X-Frame-Options: DENY`
   - `X-Content-Type-Options: nosniff`
   - `X-XSS-Protection: 1; mode=block`
   - `Referrer-Policy: strict-origin-when-cross-origin`

3. **FastCGI 安全設定**:
   - 隱藏 `X-Powered-By` 標頭

## 其他安全配置

### 1. 伺服器資訊隱藏
- `server_tokens off`: 隱藏 Nginx 版本資訊
- `fastcgi_hide_header X-Powered-By`: 隱藏 PHP 版本資訊

### 2. 檔案上傳限制（生產環境）
- `client_max_body_size 10M`: 限制上傳檔案大小
- `client_body_timeout 60s`: 限制請求體超時時間
- `client_header_timeout 60s`: 限制請求標頭超時時間

### 3. 敏感檔案保護（生產環境）
禁止存取以下檔案：
- 隱藏檔案（`.` 開頭）
- 備份檔案（`~` 結尾）
- 配置檔案（`.env`, `.ini`, `.log`, `.conf`）
- 上傳目錄中的 PHP 檔案

## 測試安全標頭

### 使用 curl 測試

```bash
# 測試前端安全標頭
curl -I http://localhost:3000

# 測試 API 安全標頭
curl -I http://localhost:8080/api/posts
```

### 使用線上工具

1. **SecurityHeaders.com**: https://securityheaders.com/
2. **Mozilla Observatory**: https://observatory.mozilla.org/
3. **SSL Labs**: https://www.ssllabs.com/ssltest/ (測試 HTTPS 配置)

## 維護建議

1. **定期更新 CSP 白名單**: 當新增 CDN 或第三方服務時，更新 CSP 配置
2. **監控 CSP 違規**: 考慮實作 CSP 報告機制
3. **逐步收緊策略**: 從寬鬆的開發配置逐步過渡到嚴格的生產配置
4. **測試新標頭**: 在開發環境充分測試後再部署到生產環境
5. **關注安全公告**: 持續關注新的安全標頭和最佳實踐

## 待改進項目

- [ ] 實作 CSP 違規報告端點
- [ ] 移除生產環境的 `unsafe-inline`（需重構內聯樣式）
- [ ] 實作 Subresource Integrity (SRI) 用於 CDN 資源
- [ ] 考慮實作 Feature-Policy 的完整配置
- [ ] 評估是否需要 Cross-Origin-Embedder-Policy (COEP)

## 參考資料

- [OWASP Secure Headers Project](https://owasp.org/www-project-secure-headers/)
- [MDN Web Security](https://developer.mozilla.org/en-US/docs/Web/Security)
- [Content Security Policy Reference](https://content-security-policy.com/)
- [HTTP Security Headers Best Practices](https://www.netsparker.com/blog/web-security/http-security-headers/)
