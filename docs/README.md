# AlleyNote 文件目錄

這個目錄包含 AlleyNote 專案的所有技術文件，按照 DDD 領域架構組織。

## 📂 文件結構說明

### 目錄組織原則

文件依照**領域驅動設計（DDD）**的概念組織：
- **`domains/`** - 按業務領域分類的文件（系統架構和深入說明）
- **`guides/`** - 按使用者角色分類的指南（實用教學）
- **`archive/`** - 開發過程文件、TODO、規劃、報告（歷史記錄）

---

## 📚 主要目錄

### 🎯 `domains/` - 領域文件（深入理解系統）

按照系統的業務領域組織，每個領域包含該部分的架構設計、技術規格和實作細節。

#### **`domains/post/`** - 文章領域
目前無專門文件（規劃中）

**涵蓋內容**：
- 文章的建立、編輯、刪除
- 文章狀態管理（草稿、已發布、已下架）
- 分類和標籤系統
- 置頂和排序功能

#### **`domains/auth/`** - 認證與授權領域
- JWT_AUTHENTICATION_SPECIFICATION.md - JWT 認證機制規格
- JWT_SETUP_TOOL_GUIDE.md - JWT 設定工具使用指南
- USER_ACTIVITY_LOGGING_ARCHITECTURE.md - 使用者活動記錄架構
- USER_ACTIVITY_LOGGING_GUIDE.md - 活動記錄使用指南
- USER_ACTIVITY_LOGGING_SPEC.md - 活動記錄規格
- ROUTE_JWT_CONFIGURATION.md - 路由層級的 JWT 配置

**涵蓋內容**：
- 使用者註冊、登入、登出
- JWT Token 管理
- 角色和權限控制
- 使用者活動追蹤（21 種活動類型）
- 異常行為偵測

#### **`domains/security/`** - 安全領域
目前無專門文件（規劃中）

**涵蓋內容**：
- XSS、CSRF、SQL Injection 防護
- IP 黑白名單
- 地理位置限制
- 攻擊偵測和自動封禁

#### **`domains/attachment/`** - 附件領域
目前無專門文件（規劃中）

**涵蓋內容**：
- 檔案上傳和管理
- 多格式支援（圖片、PDF、文件）
- 檔案驗證和安全檢查
- 儲存空間管理

#### **`domains/statistics/`** - 統計領域 ⭐
- STATISTICS_FEATURE_OVERVIEW.md - 統計功能總覽（從這裡開始！）
- STATISTICS_FEATURE_SPECIFICATION.md - 詳細功能規格
- STATISTICS_OPERATIONS_MANUAL.md - 運維操作手冊
- STATISTICS_DATABASE_MIGRATION_GUIDE.md - 資料庫遷移指南
- STATISTICS_DOMAIN_ANALYSIS.md - 領域分析
- STATISTICS_RECALCULATION_GUIDE.md - 資料重算指南

**涵蓋內容**：
- 多維度統計分析（內容、用戶、行為）
- 趨勢預測和時間序列分析
- 統計快照系統（每日/週/月）
- 視覺化儀表板

#### **`domains/shared/`** - 共享基礎設施
- ARCHITECTURE_AUDIT.md - 完整架構審查報告
- DDD_ARCHITECTURE_DESIGN.md - DDD 架構設計文件
- PHPSTAN_LEVEL_10_ENFORCEMENT.md - PHPStan Level 10 實施
- CACHE_TAGGING_SYSTEM_GUIDE.md - 快取標籤系統使用指南
- CACHE_TAGGING_SYSTEM_API_REFERENCE.md - 快取系統 API 參考
- CACHE_TAGGING_SYSTEM_PERFORMANCE_GUIDE.md - 快取效能指南
- CACHE_TAGGING_SYSTEM_DEPLOYMENT_GUIDE.md - 快取系統部署指南
- MULTI_LAYER_CACHE_SYSTEM.md - 多層快取系統
- ROUTING_SYSTEM_GUIDE.md - 路由系統指南
- ROUTING_SYSTEM_ARCHITECTURE.md - 路由系統架構
- ROUTING_IMPLEMENTATION_GUIDE.md - 路由實作指南

**涵蓋內容**：
- 整體系統架構
- DI（依賴注入）容器
- 驗證系統
- 快取機制
- 路由系統
- 共用工具和輔助函式

---

### 📖 `guides/` - 使用指南（實用教學）

按照使用者角色組織的實用指南和教學文件。

#### **`guides/admin/`** - 管理員指南
- ADMIN_QUICK_START.md - **30分鐘快速入門**（從這裡開始！）⭐
- ADMIN_MANUAL.md - 完整管理員操作手冊
- TROUBLESHOOTING_GUIDE.md - 故障排除指南
- SYSTEM_REQUIREMENTS.md - 系統需求說明

**適合對象**：
- 系統管理員
- 運維人員
- 第一次部署的人

**內容**：
- 如何安裝和部署
- 日常維護任務
- 備份和還原
- 常見問題解決
- 系統監控

#### **`guides/developer/`** - 開發者指南
- DEVELOPER_GUIDE.md - **完整開發者指南**（從這裡開始！）⭐
- API_DOCUMENTATION.md - RESTful API 文件
- DI_CONTAINER_GUIDE.md - DI 容器使用手冊
- VALIDATOR_GUIDE.md - 驗證器使用指南（29 種驗證規則）
- DI_VALIDATION_INTEGRATION.md - DI 與驗證整合
- ENVIRONMENT_CONFIGURATION_GUIDE.md - 環境配置指南
- MODERN_DATABASE_INITIALIZATION_GUIDE.md - 資料庫初始化指南
- NAMING_CONVENTIONS.md - 命名規範

**適合對象**：
- PHP 開發者
- 想要貢獻程式碼的人
- 學習 DDD 架構的人

**內容**：
- 開發環境設置
- 程式碼規範
- 測試指南
- API 使用方式
- DDD 設計模式
- 如何擴展功能

#### **`guides/deployment/`** - 部署指南
- DEPLOYMENT.md - 詳細部署說明
- SSL_DEPLOYMENT_GUIDE.md - SSL/HTTPS 設定指南

**適合對象**：
- DevOps 工程師
- 系統管理員
- 準備上線的團隊

**內容**：
- 生產環境部署
- SSL 憑證設定
- Docker 配置
- 效能優化
- 安全加固

---

### 🗄️ `archive/` - 歷史文件（開發記錄）

存放開發過程中的 TODO、規劃文件、完成報告等。這些文件記錄了專案的發展歷程，但不是使用者日常需要的。

**包含內容**：
- TODO 清單和完成報告
- 開發計劃和時程
- 技術決策記錄
- 重構報告
- 效能測試報告
- 程式碼品質改善記錄
- 遷移指南

**適合對象**：
- 想了解專案歷史的人
- 研究技術決策的人
- 核心開發團隊

---

## 🚀 快速導航

### 我是**系統管理員**，想要部署系統
1. 先看 guides/admin/ADMIN_QUICK_START.md
2. 遇到問題看 guides/admin/TROUBLESHOOTING_GUIDE.md
3. 需要詳細操作看 guides/admin/ADMIN_MANUAL.md

### 我是**開發者**，想要開發功能
1. 先看 guides/developer/DEVELOPER_GUIDE.md
2. 了解 API 看 guides/developer/API_DOCUMENTATION.md
3. 理解架構看 domains/shared/DDD_ARCHITECTURE_DESIGN.md

### 我想**了解統計功能**
1. 先看 domains/statistics/STATISTICS_FEATURE_OVERVIEW.md
2. 詳細規格看 domains/statistics/STATISTICS_FEATURE_SPECIFICATION.md
3. 操作手冊看 domains/statistics/STATISTICS_OPERATIONS_MANUAL.md

### 我想**深入研究某個領域**
- **認證授權**：domains/auth/
- **統計分析**：domains/statistics/
- **快取系統**：domains/shared/ (快取相關文件)
- **整體架構**：domains/shared/ (架構文件)

---

## 📝 文件貢獻指南

如果你要新增或修改文件，請遵循以下原則：

1. **領域文件** (domains/) - 放技術規格、架構設計、領域分析
2. **使用指南** (guides/) - 放實用教學、操作手冊、快速入門
3. **歷史文件** (archive/) - 放 TODO、計劃、報告、開發記錄

**命名規範**：
- 使用大寫加底線：MY_DOCUMENT.md
- 清楚描述內容：STATISTICS_FEATURE_OVERVIEW.md ✅  stats.md ❌
- 包含文件類型：_GUIDE.md, _SPECIFICATION.md, _MANUAL.md

---

## 📊 文件統計

- **領域文件**：30+ 份技術規格和架構文件
- **使用指南**：15+ 份實用教學和操作手冊
- **歷史文件**：50+ 份開發過程記錄
- **總計**：95+ 份完整文件

---

*最後更新：2025-10-03*
