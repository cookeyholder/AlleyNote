# AlleyNote Nginx 配置說明

本目錄包含所有 Nginx 伺服器配置檔案。

## 配置檔案總覽

| 檔案名稱                    | 用途                                     | 使用環境                                 |
| --------------------------- | ---------------------------------------- | ---------------------------------------- |
| `default.conf`              | 基礎 HTTP 配置，包含 ACME Challenge 處理 | 開發環境                                 |
| `frontend-backend.conf`     | 前端靜態檔案 + 後端 API 代理配置         | 開發環境 (docker-compose.yml)            |
| `nginx-production.conf`     | 生產環境完整配置，含效能優化與安全標頭   | 生產環境 (docker-compose.production.yml) |
| `ssl.conf`                  | HTTPS/SSL 配置，包含 TLS 強化設定        | 生產環境                                 |
| `api-security-headers.conf` | API 安全回應標頭配置                     | 共用（開發與生產）                       |
| `test.conf`                 | 測試環境配置                             | 測試環境                                 |
| `conf.d/default.conf`       | 額外配置覆蓋檔                           | 視部署需求而定                           |

## 環境對應關係

### 開發環境（docker-compose.yml）

- 使用 `frontend-backend.conf` 作為主要配置（映射到 `/etc/nginx/conf.d/default.conf`）
- 載入 `api-security-headers.conf` 作為安全標頭
- SSL 配置預設停用

### 生產環境（docker-compose.production.yml）

- 使用 `nginx-production.conf` 作為主要配置（映射到 `/etc/nginx/conf.d/default.conf`）
- 啟用 `ssl.conf` 處理 HTTPS 流量
- 載入 `api-security-headers.conf`（透過 production 配置引用）

## 安全標頭

`api-security-headers.conf` 包含以下安全標頭：

- X-Content-Type-Options
- X-Frame-Options
- X-XSS-Protection
- Referrer-Policy
- Content-Security-Policy

## 注意事項

1. 生產環境配置使用 `:ro`（唯讀）掛載，增強安全性
2. 開發環境的 SSL 配置預設註解，需手動啟用
3. `test.conf` 僅用於測試環境，不應在生產中使用
