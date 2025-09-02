# 前後端分離重構待辦清單

> **建立日期：** 2025年9月2日
> **分支：** feature/frontend-backend-separation
> **目標：** 重新組織專案結構以支援前後端分離架構

## 📋 執行進度追蹤

### 🎯 重構目標
將現有的 PHP DDD 後端專案重新組織為支援前後端分離的架構，建立 `backend/` 和 `frontend/` 目錄結構。

### 📊 現狀分析
- ✅ 目前專案採用 DDD 架構，程式碼在 `app/` 目錄下
- ✅ 有完整的 Docker 配置（nginx + php + redis）
- ✅ 已有 API 基礎架構
- 🔄 需要重新組織以支援前端整合

### 🗂️ 目標目錄結構
```
/AlleyNote
├── backend/                    # PHP DDD 後端程式碼
│   ├── app/                   # 搬移現有 app/ 內容
│   ├── config/                # 搬移現有 config/ 內容
│   ├── database/              # 搬移現有 database/ 內容
│   ├── tests/                 # 搬移現有 tests/ 內容
│   ├── storage/               # 搬移現有 storage/ 內容
│   ├── composer.json          # 搬移現有 composer.json
│   ├── phpunit.xml           # 搬移現有 phpunit.xml
│   └── ...                   # 其他 PHP 相關檔案
├── frontend/                  # 前端程式碼（新建立）
│   ├── src/                  # 前端源碼
│   ├── public/               # 搬移現有 public/ 內容
│   ├── dist/                 # 建構後的檔案
│   ├── package.json          # 前端套件管理
│   └── vite.config.js        # 建構工具配置
├── docker/                    # Docker 配置（保留）
├── scripts/                   # 腳本工具（保留）
├── docs/                      # 文件（保留）
└── docker-compose.*.yml       # Docker Compose 配置（調整）
```

---

## 📝 詳細執行步驟

### 步驟 1：建立分支和準備工作
- [x] 1.1 建立新分支 `feature/frontend-backend-separation` ✅ **已完成**
- [x] 1.2 確保當前程式碼狀態乾淨（沒有未提交的變更） ✅ **已完成**
- [x] 1.3 建立備份點（創建 tag） ✅ **已完成**

**驗收標準：**
- ✅ Git 分支已建立且切換成功
- ✅ 工作目錄無未提交變更
- ✅ 建立備份 tag

### 步驟 2：建立新目錄結構
- [x] 2.1 建立 `backend/` 目錄 ✅ **已完成**
- [x] 2.2 建立 `frontend/` 目錄 ✅ **已完成**
- [x] 2.3 建立 `frontend/src/` 目錄 ✅ **已完成**
- [x] 2.4 建立 `frontend/public/` 目錄 ✅ **已完成**
- [x] 2.5 建立臨時備份目錄 `_migration_backup/` ✅ **已完成**

**驗收標準：**
- ✅ 所有目錄建立成功
- ✅ 目錄結構符合設計

### 步驟 3：搬移後端相關檔案到 backend/
- [x] 3.1 搬移 `app/` → `backend/app/` ✅ **已完成**
- [x] 3.2 搬移 `config/` → `backend/config/` ✅ **已完成**
- [x] 3.3 搬移 `database/` → `backend/database/` ✅ **已完成**
- [x] 3.4 搬移 `tests/` → `backend/tests/` ✅ **已完成**
- [x] 3.5 搬移 `storage/` → `backend/storage/` ✅ **已完成**
- [x] 3.6 搬移 `composer.json` → `backend/composer.json` ✅ **已完成**
- [x] 3.7 搬移 `composer.lock` → `backend/composer.lock` ✅ **已完成**
- [x] 3.8 搬移 `phpunit.xml` → `backend/phpunit.xml` ✅ **已完成**
- [x] 3.9 搬移 `phinx.php` → `backend/phinx.php` ✅ **已完成**
- [x] 3.10 搬移所有 `phpstan*.neon` 檔案 → `backend/` ✅ **已完成**
- [x] 3.11 搬移 `.php-cs-fixer.*` 檔案 → `backend/` ✅ **已完成**

**驗收標準：**
- ✅ 所有檔案搬移完成且無遺失
- ✅ 原始位置檔案已移除
- ✅ 新位置檔案內容完整

### 步驟 4：搬移前端相關檔案到 frontend/
- [x] 4.1 搬移 `public/` → `frontend/public/` ✅ **已完成**
- [x] 4.2 建立 `frontend/src/` 基本結構 ✅ **已完成**
- [x] 4.3 建立 `frontend/package.json` ✅ **已完成**
- [x] 4.4 建立 `frontend/vite.config.js` ✅ **已完成**

**驗收標準：**
- ✅ 前端目錄結構完整
- ✅ 基本設定檔案已建立

### 步驟 5：更新 Docker 配置
- [x] 5.1 更新 `docker-compose.yml` 路徑參考 ✅ **已完成**
- [x] 5.2 更新 `docker-compose.production.yml` 路徑參考 ✅ **已完成**
- [x] 5.3 更新 `docker-compose.test.yml` 路徑參考 ✅ **已完成**
- [x] 5.4 更新 `docker/nginx/` 配置檔案 ✅ **已完成**
- [x] 5.5 更新 `docker/php/` Dockerfile ✅ **已完成**

**驗收標準：**
- ✅ Docker Compose 檔案路徑正確
- ✅ 容器配置符合新結構
- ✅ 可正常啟動所有服務

### 步驟 6：更新設定檔案
- [ ] 6.1 更新 `backend/composer.json` 中的 autoload 路徑
- [ ] 6.2 更新 `backend/phpunit.xml` 中的測試路徑
- [ ] 6.3 更新 `backend/config/` 中的路徑參考
- [ ] 6.4 更新腳本檔案中的路徑參考

**驗收標準：**
- 所有路徑參考正確
- 自動載入功能正常
- 測試可正常執行

### 步驟 7：建立前端基礎結構
- [ ] 7.1 初始化 Node.js 專案
- [ ] 7.2 安裝 Vite 和基本套件
- [ ] 7.3 建立基本的前端檔案結構
- [ ] 7.4 建立 API 客戶端程式碼
- [ ] 7.5 建立簡單的範例頁面

**驗收標準：**
- 前端專案可正常建構
- API 客戶端功能正常
- 範例頁面可正常顯示

### 步驟 8：更新根目錄檔案
- [ ] 8.1 更新 `.gitignore`
- [ ] 8.2 更新 `README.md`
- [ ] 8.3 建立根目錄的 `package.json`（管理工作區）
- [ ] 8.4 更新 `.env.example`

**驗收標準：**
- Git 忽略規則正確
- 文件反映新結構
- 工作區管理功能正常

### 步驟 9：測試和驗證
- [ ] 9.1 測試 Docker Compose 能否正常啟動
- [ ] 9.2 測試後端 API 功能
- [ ] 9.3 測試前端基本功能
- [ ] 9.4 執行後端測試套件
- [ ] 9.5 檢查程式碼品質工具

**驗收標準：**
- 所有服務正常啟動
- API 端點回應正確
- 前端頁面正常載入
- 所有測試通過
- 程式碼品質檢查通過

### 步驟 10：清理和文件更新
- [ ] 10.1 移除臨時檔案和備份
- [ ] 10.2 更新文件
- [ ] 10.3 更新腳本工具
- [ ] 10.4 建立遷移指南

**驗收標準：**
- 無多餘檔案
- 文件完整且正確
- 腳本工具功能正常
- 遷移指南清楚明瞭

---

## 🔧 關鍵檔案路徑對應表

| 原始路徑 | 新路徑 | 狀態 |
|---------|--------|------|
| `app/` | `backend/app/` | ⏳ 待處理 |
| `config/` | `backend/config/` | ⏳ 待處理 |
| `database/` | `backend/database/` | ⏳ 待處理 |
| `tests/` | `backend/tests/` | ⏳ 待處理 |
| `storage/` | `backend/storage/` | ✅ 已完成 |
| `public/` | `frontend/public/` | ⏳ 待處理 |
| `composer.json` | `backend/composer.json` | ⏳ 待處理 |
| `phpunit.xml` | `backend/phpunit.xml` | ⏳ 待處理 |

---

## 📚 相關文件

- [專案 DDD 指引](./copilot-instructions.md)
- [Docker 配置說明](./docs/DEPLOYMENT.md)
- [API 文件](./docs/API_DOCUMENTATION.md)

---

## 🚨 注意事項

1. **備份重要性**：在開始搬移前，請確保已建立完整備份
2. **路徑一致性**：所有設定檔案中的路徑都需要更新
3. **測試驗證**：每個步驟完成後都應該進行測試
4. **逐步提交**：建議將大的變更分成多個 commit
5. **Docker 快取**：搬移檔案後可能需要重新建構 Docker 映像

---

## 🎯 完成標準

- [ ] 所有檔案搬移完成且無遺失
- [ ] Docker 環境正常啟動
- [ ] 後端 API 功能正常
- [ ] 前端基礎結構建立完成
- [ ] 所有測試通過
- [ ] 程式碼品質檢查通過
- [ ] 文件更新完成

---

**最後更新：** 2025年9月2日
**當前進度：** 步驟 3.5 已完成 (storage/ 搬移)
