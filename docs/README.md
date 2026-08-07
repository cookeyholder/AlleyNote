# AlleyNote 文件中心

> 歡迎使用 AlleyNote 官方技術與操作文件系統。本目錄涵蓋系統架構、領域驅動設計 (DDD)、API 整合、前端開發與營運維護手冊。

---

## 🧭 快速分流與指引

| 閱讀對象 | 建議入口 | 核心內容 |
|----------|----------|----------|
| 👋 **新進開發者** | [📖 完整文件索引 (INDEX.md)](INDEX.md) | 全站文件地圖與開發環境快速導覽 |
| 🖥️ **後端開發者** | [🏛️ 領域模型總覽 (domains/README.md)](domains/README.md) | 7 大 Bounded Context、DDD 架構與依賴關係 |
| 🎨 **前端開發者** | [🎨 前端架構指南 (frontend/README.md)](frontend/README.md) | 純 ES6 模組、無建構工具 SPA、API Client 與 State 管理 |
| 🔌 **API 整合者** | [🔌 API 文件中心 (api/README.md)](api/README.md) | OpenAPI 3.0 規格、Swagger UI 與全端點範例 |
| ⚙️ **系統管理員** | [🛠️ 系統維運指南 (guides/admin/README.md)](guides/admin/README.md) | Docker 容器部署、環境變數與效能調校 |
| 📝 **內容管理者** | [📝 管理後台使用手冊 (guides/content-creators/01-管理後台使用手冊.md)](guides/content-creators/01-管理後台使用手冊.md) | 公告發布、Markdown 匯出與標籤分類操作 |

---

## 📚 核心主題目錄

- 🏛️ **[decisions/](decisions/)** — 架構決策記錄 (ADR)，包含關鍵架構演進與技術選型決策。
- 📦 **[domains/](domains/)** — 7 大領域 (Auth, Post, Notification, Statistics, Security, Setting, Attachment) 規範。
- 🏗️ **[architecture/](architecture/)** — 後端自製 HTTP 引擎、統計分析器與快取層架構設計。
- 💻 **[frontend/](frontend/)** — 無框架純 JavaScript SPA 架構與元件設計。
- 🛠️ **[runbooks/](runbooks/)** — 本地開發、CI/CD 流程與安全設定 Step-by-Step 營運手冊。
- 📜 **[DOCUMENTATION_GOVERNANCE.md](DOCUMENTATION_GOVERNANCE.md)** — 文件維護與 CI 連動治理規範。
