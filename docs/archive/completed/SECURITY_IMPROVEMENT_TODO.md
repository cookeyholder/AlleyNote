# AlleyNote 安全性改善待辦清單

> 根據 ARCHITECTURE_AND_SECURITY_REVIEW.md 的建議，制定的安全性改善計畫
> 
> **原則：維持現有的 HTML + Vanilla JavaScript + CSS 架構，不引入前端框架**

---

## 📋 待辦事項

### ✅ 高優先級（立即執行）

#### 1. HTTP 安全性標頭配置 ✅
- [x] 在 Nginx 配置中添加 `X-Frame-Options` 標頭（防止點擊劫持）
- [x] 添加 `X-Content-Type-Options` 標頭（防止 MIME 類型嗅探）
- [x] 添加 `Referrer-Policy` 標頭（控制 Referer 資訊洩漏）
- [x] 添加 `Content-Security-Policy` 標頭（防止 XSS 和資料注入攻擊）
  - 已根據實際使用的外部資源調整
  - 已允許的來源：CKEditor、Chart.js、Tailwind CSS、Google Fonts 等
- [x] 添加 `Strict-Transport-Security` 標頭（強制 HTTPS）
- [x] 測試標頭配置不會影響現有功能

**驗收標準**：
- ✅ HTTP 安全標頭已在 `docker/nginx/ssl.conf` 和 `docker/nginx/default.conf` 中配置
- ✅ 所有前端功能正常運作（CKEditor、Chart.js 等）
- ✅ HSTS 標頭已配置（max-age=63072000, includeSubDomains, preload）
- ✅ CSP 標頭已配置適當的策略

---

#### 2. 細化後端權限控制 ✅

**目標**：將權限檢查從 Controller 內部移到路由定義層面

- [x] 修改 `JwtAuthorizeMiddleware`，支援接收權限參數
  - 已實作 `JwtAuthorizationMiddleware` 支援 RBAC 和 ABAC
- [x] 定義完整的權限列表
  - 已在 `JwtAuthorizationMiddleware` 的預設配置中定義權限
  - 支援 `posts.*`、`users.*`、`roles.*`、`tags.*`、`settings.*` 等
- [x] 更新所有路由定義，明確指定所需權限
  - 路由已使用 `jwt.auth` 和 `jwt.authorize` 中介軟體
- [x] 更新角色與權限的資料庫結構（已完成）
- [x] 撰寫單元測試，驗證權限檢查邏輯（已有測試）
- [x] 撰寫整合測試，驗證各路由的權限控制（已有測試）

**驗收標準**：
- ✅ 從 `routes.php` 可以清楚看出哪些端點需要認證和授權
- ✅ 未授權的請求會返回 403 Forbidden
- ✅ 測試通過（CI 已通過）

---

#### 3. 統一的請求驗證層 ✅

**目標**：確保所有輸入都經過嚴格驗證

- [x] 選擇並實作驗證系統
  - 已實作自訂驗證系統（`app/Shared/Validation/Validator.php`）
- [x] 建立驗證服務和 Factory
  - 已建立 `ValidatorFactory` 和 `ValidationResult`
- [x] 為每個 API 端點定義驗證規則
  - 已為 Post、User、Auth 等建立專用驗證器
  - 使用者輸入（username、email、password）- 已實作
  - 文章輸入（title、content、published_at）- 已實作
  - 標籤輸入（name、slug）- 已實作
  - 角色輸入（name、permissions）- 已實作
- [x] 在 Controller 或中介軟體中應用驗證規則
  - 已在各 Controller 中應用驗證
- [x] 確保驗證失敗時返回清晰的錯誤訊息（422 狀態碼）
  - 已實作 `ValidationException` 處理
- [x] 撰寫測試驗證所有邊界情況
  - 已有完整的驗證測試

**驗收標準**：
- ✅ 所有 API 端點都有明確的驗證規則
- ✅ 非法輸入會被拒絕並返回清晰錯誤訊息
- ✅ 驗證規則測試覆蓋率達到 90% 以上（CI 測試通過）

---

### 🔸 中優先級

#### 4. 輸出淨化與編碼 ✅

**目標**：防止 XSS 攻擊

- [x] 審查所有後端輸出，確保適當的處理
  - JSON API 回應：確保數據正確編碼（已實作）
  - HTML 輸出：使用 `htmlspecialchars`（已在需要的地方實作）
- [x] 審查前端輸出，確保使用 DOMPurify
  - 已在前端整合 DOMPurify（透過 CDN）
  - 特別是顯示使用者生成內容的地方（文章內容等）
- [x] 建立輸出編碼的標準化工具函式
  - 已在 `app/Shared/Helpers/functions.php` 中實作
- [x] 更新文件，說明何時使用何種淨化方式
  - 已在 README 和 MIGRATION_NOTES 中記錄

**驗收標準**：
- ✅ 所有使用者輸入的內容都經過適當淨化
- ✅ XSS 攻擊測試無法成功（DOMPurify 和 htmlspecialchars 保護）
- ✅ CSP 標頭已配置，提供額外的 XSS 防護

---

#### 5. Docker 容器安全 ⚠️

**目標**：減少容器逃逸風險

- [x] 修改 `docker/php/Dockerfile`
  - 已設定檔案權限給 `www-data` 使用者
  - PHP-FPM worker 進程以 www-data 執行（標準配置）
- [x] 更新檔案權限設定，確保應用程式可正常運作
  - 已設定 storage 目錄權限
- [x] 測試容器運作
  - 所有功能正常運作
- [ ] （可選）完全切換到非 root 使用者運行容器
  - 目前使用標準 PHP-FPM 配置（主進程 root，worker www-data）
  - 如需更嚴格的安全性，可考慮使用 rootless 容器

**驗收標準**：
- ✅ PHP-FPM worker 進程以 www-data 使用者執行
- ✅ 所有功能正常運作
- ✅ 檔案上傳、日誌寫入等功能正常
- ⚠️ （進階）完全 rootless 容器（可選，標準配置已足夠安全）

---

#### 6. 集中化配置管理 ✅

**目標**：讓配置更結構化、易於管理

- [x] 在 `backend/config` 建立配置檔案
  - `container.php` - DI 容器配置 ✅
  - `routes.php` - 路由配置 ✅
  - `statistics.php` - 統計配置 ✅
  - `swagger.php` - API 文件配置 ✅
  - 資料庫、JWT、快取等配置透過 EnvironmentConfig 管理 ✅
- [x] 從 `.env` 讀取值，並提供合理的預設值
  - 已透過 `EnvironmentConfig` 實作
- [x] 實作配置快取機制（Production 環境）
  - 已支援配置快取
- [x] 更新現有代碼，使用新的配置系統
  - 已全面使用 EnvironmentConfig
- [x] 更新文件，說明配置結構
  - 配置結構已記錄在程式碼註解中

**驗收標準**：
- ✅ 所有配置集中管理
- ✅ 開發環境和生產環境可使用不同配置
- ✅ 配置修改不需要改動程式碼

---

### 🔹 低優先級（可選）

#### 7. 後端架構優化

**目標**：明確 DDD 架構邊界

- [ ] 確認是否要完整實踐 DDD
- [ ] 如果是，重構目錄結構
  - `app/Domain` - 核心領域邏輯
  - `app/Application` - 應用服務
  - `app/Infrastructure` - 基礎設施實作
- [ ] 確保依賴方向正確（Infrastructure → Domain ← Application）
- [ ] 更新命名空間和自動載入
- [ ] 更新文件說明架構決策

**驗收標準**：
- 架構邊界清晰
- 依賴方向符合 DDD 原則
- 新成員可快速理解架構

---

#### 8. API 速率限制

**目標**：防止暴力破解和 DoS 攻擊

- [ ] 實作速率限制中介軟體（使用 Redis）
- [ ] 為登入端點設定嚴格限制（例如：5次/分鐘）
- [ ] 為一般 API 設定合理限制（例如：60次/分鐘）
- [ ] 返回適當的 `429 Too Many Requests` 回應
- [ ] 撰寫測試驗證速率限制

**驗收標準**：
- 登入失敗超過限制會被暫時封鎖
- 一般 API 有合理的速率限制
- 正常使用不受影響

---

#### 9. 安全性審計日誌

**目標**：記錄關鍵安全事件

- [ ] 設計審計日誌結構
- [ ] 記錄以下事件：
  - 登入成功/失敗
  - 權限變更
  - 敏感資料修改
  - 密碼重設
- [ ] 實作日誌查詢介面
- [ ] 定期審查日誌（手動或自動化）

**驗收標準**：
- 關鍵安全事件都有記錄
- 可以追蹤使用者操作歷史
- 日誌格式統一，易於分析

---

## 📝 開發規範

### 開發流程

1. 選擇一個待辦項目
2. 建立功能分支（例如：`security/http-headers`）
3. 查詢相關文件和最佳實踐（使用 Context7 MCP）
4. 撰寫測試（TDD）
5. 實作功能
6. 執行本地檢查：
   ```bash
   docker compose exec -T web composer ci
   ```
7. 執行 E2E 測試：
   ```bash
   npm run test:e2e
   ```
8. 更新此待辦清單，標記已完成項目
9. 提交變更（使用 Conventional Commit 格式）

### Commit Message 規範

使用繁體中文撰寫，格式如下：

```
<類型>(<範圍>): <簡短描述>

<詳細描述>

<關聯 Issue>
```

**類型**：
- `feat`: 新功能
- `fix`: 錯誤修復
- `security`: 安全性改善
- `refactor`: 重構
- `test`: 測試
- `docs`: 文件

**範例**：
```
security(nginx): 新增 HTTP 安全性標頭

- 添加 X-Frame-Options 防止點擊劫持
- 添加 X-Content-Type-Options 防止 MIME 嗅探
- 添加 Content-Security-Policy 防止 XSS
- 添加 Strict-Transport-Security 強制 HTTPS

關聯 #123
```

---

## 📊 進度追蹤

- 高優先級：3/3 完成 ✅
- 中優先級：3/3 完成 ✅
- 低優先級：0/3 完成 （可選項目）

**總進度：6/6 核心項目 (100%)** ✅

**備註**：
- 低優先級項目為可選的進階功能
- 核心安全性改善已全部完成
- 後端 CI 測試全部通過
- E2E 測試部分通過（系統統計頁面有問題需修復）

---

## 📋 近期待修復問題

### 🔴 高優先級
1. **系統統計頁面 E2E 測試失敗**
   - 測試超時 (30秒)，頁面無法載入
   - 需檢查統計 API 端點實作
   - 需檢查前端 JavaScript 錯誤
   - 需檢查依賴注入配置

### 🟡 中優先級  
2. **部分 E2E 測試被跳過**
   - 需檢視所有 `.skip()` 測試
   - 修復或移除不穩定的測試
   - 提升測試覆蓋率

---

## 📚 參考資源

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Security Headers](https://securityheaders.com/)
- [Content Security Policy Reference](https://content-security-policy.com/)
- [Docker Security Best Practices](https://docs.docker.com/develop/security-best-practices/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
