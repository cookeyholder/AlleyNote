# JWT 認證系統規格書

**專案**: AlleyNote 公布欄系統
**版本**: v4.0
**建立日期**: 2025-08-26
**更新日期**: 2025-09-03
**架構**: 前後端分離 (原生 HTML/JavaScript/CSS + PHP 8.4.12 DDD)
**作者**: GitHub Copilot

## 1. 概述

### 1.1 目標
將 AlleyNote 前後端分離架構的認證系統實作為現代 JWT (JSON Web Token) 認證機制，為 原生 HTML/JavaScript/CSS 前端和 PHP 8.4.12 DDD 後端提供安全、可擴展且無狀態的使用者認證。

### 1.2 範圍
- 實作前後端分離的 JWT token 產生、驗證和管理
- 原生 HTML/JavaScript/CSS Composition API 認證狀態管理
- PHP 8.4.12 後端 API 認證系統
- 加入 refresh token 機制
- 實作 CORS 安全認證 middleware
- 提供 token 黑名單功能
- 前後端完整的測試覆蓋 (1,372 後端測試)

### 1.3 架構原則
遵循 DDD (Domain-Driven Design) 原則和前後端分離最佳實踐：
- **Domain Layer**: JWT 相關的業務邏輯和規則
- **Application Layer**: API 控制器和應用服務
- **Infrastructure Layer**: JWT 函式庫整合和持久化
- **Frontend Layer**: 原生 HTML/JavaScript/CSS 認證狀態管理和 API 整合
- **Shared Layer**: 通用介面和例外處理

## 2. 技術規格

### 2.1 JWT 結構設計 (API 優先)

#### 2.1.1 Header
```json
{
  "alg": "RS256",
  "typ": "JWT"
}
```

#### 2.1.2 Payload (Access Token) - API 優化
```json
{
  "iss": "alleynote-api",
  "aud": "alleynote-spa",
  "sub": "user-{userId}",
  "iat": 1640995200,
  "exp": 1640998800,
  "nbf": 1640995200,
  "jti": "unique-token-id",
  "user_id": 123,
  "username": "johndoe",
  "email": "john@example.com",
  "role": "user",
  "permissions": ["read_posts", "create_posts"],
  "device_id": "device-fingerprint",
  "ip_address": "192.168.1.1"
}
```

#### 2.1.3 Payload (Refresh Token)
```json
{
  "iss": "alleynote-api",
  "aud": "alleynote-client",
  "sub": "user-{userId}",
  "iat": 1640995200,
  "exp": 1643587200,
  "nbf": 1640995200,
  "jti": "unique-refresh-token-id",
  "token_type": "refresh",
  "user_id": 123,
  "device_id": "device-fingerprint"
}
```

### 2.2 Token 生命週期
- **Access Token**: 1 小時 (3600 秒)
- **Refresh Token**: 30 天 (2592000 秒)
- **記住我**: Refresh token 延長至 90 天

### 2.3 安全性設計
- 使用 RS256 演算法 (RSA SHA-256)
- RSA 金鑰對從環境變數載入 (私鑰用於簽章，公鑰用於驗證)
- 支援 token 黑名單機制
- 防重放攻擊 (jti)
- IP 地址綁定 (可選)
- 裝置指紋綁定

## 3. API 規格

### 3.1 登入 API (POST /auth/login)

#### 請求
```json
{
  "email": "user@example.com",
  "password": "password123",
  "remember_me": false,
  "device_info": {
    "name": "Chrome Browser",
    "fingerprint": "device-hash"
  }
}
```

#### 成功回應 (200)
```json
{
  "success": true,
  "message": "登入成功",
  "data": {
    "user": {
      "id": 123,
      "username": "johndoe",
      "email": "user@example.com",
      "role": "user",
      "permissions": ["read_posts", "create_posts"]
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1Q...",
      "refresh_token": "eyJ0eXAiOiJKV1Q...",
      "token_type": "Bearer",
      "expires_in": 3600,
      "expires_at": "2025-01-15T11:30:00Z"
    }
  }
}
```

### 3.2 刷新 Token API (POST /auth/refresh)

#### 請求
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1Q..."
}
```

#### 成功回應 (200)
```json
{
  "success": true,
  "message": "Token 刷新成功",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1Q...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "expires_at": "2025-01-15T12:30:00Z"
  }
}
```

### 3.3 登出 API (POST /auth/logout)

#### 請求 Header
```
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

#### 請求 Body (可選)
```json
{
  "logout_all_devices": false
}
```

#### 成功回應 (200)
```json
{
  "success": true,
  "message": "登出成功"
}
```

### 3.4 取得使用者資訊 API (GET /auth/me)

#### 請求 Header
```
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

#### 成功回應 (200)
```json
{
  "success": true,
  "data": {
    "id": 123,
    "username": "johndoe",
    "email": "user@example.com",
    "role": "user",
    "permissions": ["read_posts", "create_posts"],
    "created_at": "2025-01-01T00:00:00Z",
    "last_login": "2025-01-15T10:30:00Z"
  }
}
```

## 4. 領域模型設計

### 4.1 Value Objects
- `JwtPayload`: JWT payload 資料
- `TokenPair`: Access token 和 refresh token 組合
- `DeviceInfo`: 裝置資訊
- `TokenBlacklistEntry`: 黑名單項目

### 4.2 Entities
- `User`: 使用者實體 (現有)
- `RefreshToken`: Refresh token 實體

### 4.3 Domain Services
- `JwtTokenService`: JWT token 核心服務
- `TokenBlacklistService`: Token 黑名單服務
- `RefreshTokenService`: Refresh token 管理服務

### 4.4 Repositories
- `RefreshTokenRepositoryInterface`: Refresh token 持久化
- `TokenBlacklistRepositoryInterface`: 黑名單持久化

### 4.5 Application Services
- `JwtAuthService`: JWT 認證應用服務
- `TokenManagementService`: Token 管理服務

### 4.6 Infrastructure
- `FirebaseJwtProvider`: Firebase JWT 函式庫包裝
- `RefreshTokenRepository`: Refresh token 資料存取
- `TokenBlacklistRepository`: 黑名單資料存取

### 4.7 Middleware
- `JwtAuthenticationMiddleware`: JWT 認證中介軟體
- `JwtAuthorizationMiddleware`: JWT 授權中介軟體

## 5. 資料庫設計

### 5.1 refresh_tokens 表
```sql
CREATE TABLE refresh_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    jti VARCHAR(255) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    device_id VARCHAR(255),
    device_name VARCHAR(255),
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_refresh_tokens_jti ON refresh_tokens(jti);
CREATE INDEX idx_refresh_tokens_user_id ON refresh_tokens(user_id);
CREATE INDEX idx_refresh_tokens_expires_at ON refresh_tokens(expires_at);
```

### 5.2 token_blacklist 表
```sql
CREATE TABLE token_blacklist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    jti VARCHAR(255) NOT NULL UNIQUE,
    token_type ENUM('access', 'refresh') NOT NULL,
    user_id INTEGER,
    expires_at DATETIME NOT NULL,
    blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_token_blacklist_jti ON token_blacklist(jti);
CREATE INDEX idx_token_blacklist_expires_at ON token_blacklist(expires_at);
```

## 6. 錯誤處理

### 6.1 錯誤碼定義
- `JWT_001`: Token 格式無效
- `JWT_002`: Token 已過期
- `JWT_003`: Token 簽章無效
- `JWT_004`: Token 已在黑名單
- `JWT_005`: Refresh token 無效
- `JWT_006`: 使用者不存在或已停用
- `JWT_007`: Token 發行者無效
- `JWT_008`: Token 受眾無效

### 6.2 HTTP 狀態碼
- `200`: 操作成功
- `400`: 請求格式錯誤
- `401`: 認證失敗或 token 無效
- `403`: 權限不足
- `422`: Token 格式無效
- `500`: 伺服器錯誤

## 7. 安全性考量

### 7.1 Token 安全
- 使用 RSA 2048 位元金鑰對
- 私鑰安全存儲，僅用於 token 簽章
- 公鑰可以共享，用於 token 驗證
- Token 不包含敏感資訊 (如密碼)
- 實作 token 輪轉機制
- 支援強制登出所有裝置

### 7.2 防護措施
- 驗證 token 的 iss、aud、exp 等標準聲明
- 檢查 token 是否在黑名單
- IP 地址驗證 (可選)
- 裝置指紋驗證
- 頻率限制

### 7.3 資料保護
- Refresh token 加密存儲
- 定期清理過期 token
- 記錄安全相關事件

## 8. 效能考量

### 8.1 快取策略
- RSA 公鑰快取
- 黑名單快取 (Redis 可選)
- 使用者權限快取

### 8.2 最佳化
- 非同步清理過期 token
- 批次處理黑名單檢查
- 資料庫索引最佳化

## 9. 向後相容性

### 9.1 遷移策略
- 保持現有 AuthService 介面不變
- 內部實作逐步切換至 JWT
- 提供設定開關控制認證方式

### 9.2 部署計劃
- 階段 1: 實作 JWT 系統並測試
- 階段 2: 平行運行兩套認證系統
- 階段 3: 逐步遷移用戶到 JWT
- 階段 4: 移除舊認證系統

## 10. 測試策略

### 10.1 單元測試
- JWT token 產生和驗證
- Token 黑名單操作
- Refresh token 管理
- Middleware 功能
- 錯誤處理

### 10.2 整合測試
- API 端點測試
- 資料庫操作測試
- 認證流程測試
- 安全性測試

### 10.3 測試覆蓋率目標
- 程式碼覆蓋率 >= 95%
- 分支覆蓋率 >= 90%
- 所有錯誤情況都有測試

## 11. 文件需求

### 11.1 技術文件
- API 規格文件更新
- 系統架構圖
- 部署指南更新
- 故障排除指南

### 11.2 開發文件
- 程式碼註解
- README 更新
- CHANGELOG 記錄
- 遷移指南

## 12. 驗收標準

每個功能模組都必須滿足以下標準：

### 12.1 功能性標準
- ✅ 所有 API 端點正常運作
- ✅ JWT token 正確產生和驗證
- ✅ Refresh token 機制正常
- ✅ 黑名單功能正常
- ✅ 錯誤處理完整

### 12.2 非功能性標準
- ✅ 程式碼符合 PSR 標準
- ✅ 通過 PHPStan level 8 檢查
- ✅ 單元測試覆蓋率 >= 95%
- ✅ 整合測試通過
- ✅ 效能測試通過

### 12.3 安全性標準
- ✅ Token 簽章驗證正確
- ✅ 過期 token 被拒絕
- ✅ 黑名單 token 被拒絕
- ✅ 無敏感資訊洩漏
- ✅ 安全事件正確記錄

## 13. 交付物

1. **程式碼**
   - 完整的 JWT 認證系統
   - 單元測試和整合測試
   - 資料庫遷移腳本

2. **文件**
   - API 規格文件
   - 系統設計文件
   - 部署指南
   - 故障排除指南

3. **配置**
   - 環境變數設定
   - Docker 配置更新
   - Nginx 配置更新 (如需要)

4. **監控**
   - 日誌記錄規範
   - 效能監控指標
   - 安全監控規則

---

**備註**: 本規格書將根據開發過程中的發現和需求變化進行更新。所有變更都將記錄在 CHANGELOG.md 中。
