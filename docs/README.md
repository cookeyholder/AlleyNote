# AlleyNote 文件中心

> 📚 完整的技術文件、API 規格與使用指南

歡迎來到 AlleyNote 文件中心！這裡包含了所有您需要了解的專案資訊，從快速入門到深入的技術細節。

---

## 📖 文件導覽

### 🚀 快速開始

| 文件 | 說明 | 適合對象 |
|------|------|---------|
| [QUICK_START.md](../QUICK_START.md) | 5 分鐘快速啟動指南 | 所有使用者 |
| [README.md](../README.md) | 專案總覽與功能介紹 | 所有使用者 |
| [CHANGELOG.md](../CHANGELOG.md) | 版本更新日誌 | 所有使用者 |

### 📝 核心功能文件

#### 統計分析系統

| 文件 | 說明 |
|------|------|
| [STATISTICS_API_SPEC.md](STATISTICS_API_SPEC.md) | 統計 API 完整規格（7 個端點） |
| [STATISTICS_PAGE_README.md](STATISTICS_PAGE_README.md) | 統計頁面使用說明 |

#### 安全功能

| 文件 | 說明 |
|------|------|
| [SECURITY_HEADERS.md](SECURITY_HEADERS.md) | HTTP 安全標頭設定 |

#### 使用者介面

| 文件 | 說明 |
|------|------|
| [FRONTEND_USER_GUIDE.md](FRONTEND_USER_GUIDE.md) | 前端使用者指南 |

---

## 🗂️ 文件分類

### API 文件（`api/`）

詳細的 API 端點說明與範例：

- 認證 API
- 文章管理 API
- 使用者管理 API
- 統計查詢 API
- 活動記錄 API

### 開發指南（`guides/`）

針對開發者的技術指南：

- **開發者指南**：DDD 架構、程式碼規範
- **部署指南**：Docker 部署、SSL 設定
- **測試指南**：單元測試、整合測試、E2E 測試

### 領域文件（`domains/`）

各個領域的詳細設計文件：

- **Auth Domain**：認證與授權
- **Post Domain**：文章管理
- **Statistics Domain**：統計分析
- **Shared**：共享元件與工具

### 歷史文件（`archive/`）

已完成的專案文件與開發記錄：

- **completed/**：專案完成報告
- **implementation/**：實作計劃與報告
- **development/**：開發過程記錄

---

## 📊 統計分析功能

### 核心特性

AlleyNote 提供完整的統計分析功能，包含：

- ✅ 多維度數據分析（文章、使用者、來源）
- ✅ 即時統計儀表板
- ✅ 資料快照與趨勢分析
- ✅ 熱門內容排行
- ✅ 使用者活動追蹤
- ✅ 圖表視覺化

### 統計 API 端點

1. **`GET /api/v1/statistics/overview`** - 統計概覽
   - 總文章數、活躍使用者、新使用者、總瀏覽量

2. **`GET /api/v1/statistics/posts`** - 文章統計
   - 分頁查詢、時間範圍篩選、多種排序

3. **`GET /api/v1/statistics/sources`** - 來源分布統計
   - 各來源的文章數量與百分比

4. **`GET /api/v1/statistics/users`** - 使用者統計
   - 使用者活動指標、分頁查詢

5. **`GET /api/v1/statistics/popular`** - 熱門內容排行
   - 依瀏覽量排序的熱門文章

6. **`GET /api/v1/statistics/charts/views/timeseries`** - 流量時間序列
   - 瀏覽量趨勢資料（用於繪製圖表）

7. **`GET /api/v1/activity-logs/login-failures`** - 登入失敗統計
   - 安全監控、失敗帳號列表、時間趨勢

### 效能指標

- **資料庫優化**：41 個索引，平均查詢時間 < 1ms
- **快取機制**：TTL 5分鐘，命中率 70-80%
- **API 回應**：平均 < 500ms

詳細說明請參考 [STATISTICS_API_SPEC.md](STATISTICS_API_SPEC.md)。

---

## 🔒 安全功能

### 密碼安全

AlleyNote 實作了企業級密碼安全機制：

- ✅ 強制密碼強度驗證（8+ 字元）
- ✅ 黑名單密碼檢查（10,000+ 常見弱密碼）
- ✅ 即時密碼強度指示器
- ✅ 安全密碼生成器
- ✅ 密碼複雜度評分

### 安全防護

- **JWT Token 認證**：Access Token + Refresh Token 機制
- **CSRF 防護**：Token 驗證
- **XSS 防護**：輸入過濾與輸出編碼
- **SQL 注入防護**：PDO Prepared Statements
- **HTTP Security Headers**：CSP、X-Frame-Options、HSTS 等
- **IP 黑白名單**：限制特定 IP 存取
- **登入失敗記錄**：異常行為偵測

詳細說明請參考 [SECURITY_HEADERS.md](SECURITY_HEADERS.md)。

---

## 🏗️ 技術架構

### 領域驅動設計（DDD）

AlleyNote 採用 DDD 架構，劃分為三個主要領域：

```
AlleyNote/
├── Auth Domain（認證領域）
│   ├── User（使用者實體）
│   ├── Role（角色值物件）
│   ├── Permission（權限值物件）
│   ├── JwtToken（JWT Token 服務）
│   └── ActivityLog（活動記錄）
│
├── Post Domain（文章領域）
│   ├── Post（文章實體）
│   ├── Tag（標籤實體）
│   ├── Attachment（附件實體）
│   ├── PostStatus（狀態值物件）
│   └── PostRepository（文章倉儲）
│
└── Statistics Domain（統計領域）
    ├── Snapshot（統計快照實體）
    ├── QueryService（查詢服務）
    ├── CacheService（快取服務）
    └── ChartData（圖表資料值物件）
```

### 技術棧

**後端**
- PHP 8.4.13
- Slim Framework 4.x
- SQLite 3.x
- JWT Authentication
- PHPUnit 11.x（測試）
- PHPStan Level 10（靜態分析）

**前端**
- HTML5 / CSS3 / JavaScript ES6+
- TinyMCE 7.x（富文本編輯器）
- Chart.js 4.x（圖表視覺化）
- Fetch API（HTTP 請求）

---

## 🧪 測試與品質

### 測試統計

```
✅ 2,225 個測試全部通過
✅ 9,255 個斷言驗證
✅ PHPStan Level 10：0 錯誤
✅ PHP CS Fixer：555 檔案格式化
```

### 測試類型

| 類型 | 數量 | 覆蓋率 |
|------|------|--------|
| 單元測試 | 1,800+ | 95%+ |
| 整合測試 | 300+ | 90%+ |
| E2E 測試 | 100+ | 80%+ |
| 安全測試 | 25+ | 100% |

### 執行測試

```bash
# 所有測試
docker compose exec web composer test

# 單元測試
docker compose exec web ./vendor/bin/phpunit --testsuite Unit

# 整合測試
docker compose exec web ./vendor/bin/phpunit --testsuite Integration

# 靜態分析
docker compose exec web composer analyse

# 完整 CI
docker compose exec web composer ci
```

---

## 📂 文件結構

```
docs/
├── README.md                      # 本文件
├── STATISTICS_API_SPEC.md         # 統計 API 規格
├── STATISTICS_PAGE_README.md      # 統計頁面說明
├── SECURITY_HEADERS.md            # 安全標頭設定
├── FRONTEND_USER_GUIDE.md         # 前端使用指南
│
├── api/                           # API 文件
│   ├── authentication.md
│   ├── posts.md
│   ├── users.md
│   └── statistics.md
│
├── guides/                        # 指南
│   ├── developer/                 # 開發者指南
│   ├── deployment/                # 部署指南
│   └── admin/                     # 管理員指南
│
├── domains/                       # 領域文件
│   ├── auth/                      # 認證領域
│   ├── post/                      # 文章領域
│   ├── statistics/                # 統計領域
│   └── shared/                    # 共享元件
│
└── archive/                       # 歷史文件
    ├── completed/                 # 完成報告
    ├── implementation/            # 實作文件
    └── development/               # 開發記錄
```

---

## 🔗 相關連結

### 專案資源

- **GitHub Repository**: [cookeyholder/AlleyNote](https://github.com/cookeyholder/AlleyNote)
- **Issues**: [回報問題](https://github.com/cookeyholder/AlleyNote/issues)
- **Pull Requests**: [貢獻程式碼](https://github.com/cookeyholder/AlleyNote/pulls)

### 外部參考

- **PHP 官方文件**: https://www.php.net/docs.php
- **Slim Framework**: https://www.slimframework.com/docs/
- **PHPUnit**: https://phpunit.de/documentation.html
- **Chart.js**: https://www.chartjs.org/docs/

---

## 🆘 需要幫助？

如果您在使用過程中遇到問題：

1. 📖 **查閱文件**：先檢查相關文件是否有解答
2. 🔍 **搜尋 Issues**：查看是否有人遇到類似問題
3. 💬 **提出問題**：開啟新的 Issue 描述您的問題
4. 🤝 **參與討論**：在 GitHub Discussions 與社群交流

---

## 📝 文件維護

本文件中心由專案維護團隊持續更新。如發現錯誤或需要補充：

1. Fork 專案
2. 修正或新增文件
3. 提交 Pull Request
4. 等待審核與合併

---

**📚 感謝您閱讀 AlleyNote 文件！祝您使用愉快！**
