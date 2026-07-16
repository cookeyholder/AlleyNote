## Context

AlleyNote 現有 `docs/` 目錄包含 150+ 個 Markdown 文件，涵蓋 API、架構、領域、前端、操作手冊等主題。這些文件歷經多次開發者迭代累積，存在以下問題：

- **讀者對象不明確**：開發者文件、管理員手冊、API 使用指南混在同一層，新進成員難以找到需要的資訊
- **7 次重大重構無對應文件**：Statistics Analyzer 萃取、PostRepository 拆分、IP 邏輯統一、JWT Auth 策略模式、TagController 萃取、BaseAdminPage、安全審計修復 — 這些變更的設計決策與使用方式均未記錄
- **過時文件未清理**：路由此系統、快取標籤系統等已不存在的元件仍有專屬文件
- **缺少 ADR 傳統**：無系統性方式記錄架構決策的背景與取捨

## Goals / Non-Goals

**Goals:**
- 建立 `docs/decisions/` 目錄，為 7 次重構撰寫 ADR
- 為 6 個 Bounded Context 建立 `docs/domains/*/OVERVIEW.md`
- 補齊 `docs/architecture/` 三份設計文件（Analyzer、IP、Auth Strategy）
- 補齊 `docs/frontend/` 三份架構文件（SPA、BaseAdminPage、API Modules）
- 更新 `docs/INDEX.md` 與 `docs/DOCUMENTATION_GOVERNANCE.md`
- 歸檔已廢棄元件的文件
- 每份文件標註目標讀者（reader persona）

**Non-Goals:**
- 不重寫現有 guides/ 下的全部操作手冊（僅改名搬遷，不實質改寫內容）
- 不產生 OpenAPI 規格或 Swagger 文件（已有獨立文件）
- 不處理 CHANGELOG.md 的格式調整
- 不修改程式碼或測試（純文件異動）

## Decisions

### D1: ADR 使用輕量格式（標題 + 狀態 + 背景 + 決策 + 結果）
**替代方案：** 採用完整的 Alexandrian 格式（含上下文、力、選項等）
**選擇理由：** 輕量格式易於撰寫與審查，降低撰寫門檻。7 份 ADR 各自獨立，不需複雜的結構

### D2: 領域文件只寫 OVERVIEW.md，不取代原始 spec
**替代方案：** 為每個 BC 撰寫完整的 API/實作指南
**選擇理由：** OVERVIEW.md 提供 bounded context 邊界、職責與關鍵檔案對照，讓開發者快速理解領域劃分。詳細的 API 規格留給 `docs/api/`，實作細節留給程式碼

### D3: 讀者分流標籤放在 INDEX.md 的表格中
**替代方案：** 在每份文件內加入 YAML front-matter 標註 reader persona
**選擇理由：** 目前專案無 front-matter 處理工具。INDEX.md 表格的「適合對象」欄位足以提供分流指引，且不需引入新工具鏈

### D4: 過時文件移至 archive/ 而非刪除
**替代方案：** 直接刪除
**選擇理由：** 歷史文件可能仍有參考價值（尤其對已離職開發者或外部審計），移至 archive/ 保留但不在主要導覽中曝光

### D5: README.md 作為各子目錄的概覽頁
**替代方案：** 使用 00-概覽.md 統一命名
**選擇理由：** GitHub 在瀏覽目錄時會自動渲染 `README.md`，不需額外點擊。保留 README.md 也符合開源專案慣例，讓貢獻者能從任一目錄層級直接看到說明

### D6: 所有文件使用繁體中文檔名 + 數字 prefix
**替代方案：** 保留英文檔名，僅更新內容
**選擇理由：** 專案主要貢獻者為繁體中文使用者，中文檔名比英文更直覺。數字 prefix（01-、02- 等）確保 `ls` 排序即為閱讀順序，不需依賴外部索引工具

### D7: FRONTEND_INTERFACE_DESIGN 歸檔，由架構文件取代
**替代方案：** 修補原文件對齊現狀
**選擇理由：** subagent 調查發現 6 處重大落差（圖示方案、CKEditor 版本、角色選單、Header 功能、Bootstrap JS 缺失、側邊欄項目），修補成本接近重寫。直接歸檔並以 `docs/frontend/` 下三份架構文件取代

### D8: 內容管理者指南取代 FRONTEND_USER_GUIDE
**替代方案：** 保留原名「前端使用者指南」
**選擇理由：** 原名稱誤導非技術的內容管理者，他們不會認為自己是「前端使用者」。移至 `guides/content-creators/` 並命名為「管理後台使用手冊」，讓角色一目瞭然

### D9: features/ 與 testing/ 歸檔
**替代方案：** 保留在原來位置
**選擇理由：** `features/` 下為特定功能的說明文件（統計、批次刪除），`testing/` 下為一次性測試報告，都不屬於持續維護的文件類別。移至 `archive/` 保留但不佔用主要導覽空間

## Risks / Trade-offs

- **文件與程式碼不同步的風險依然存在** — ADR 與 OVERVIEW.md 在撰寫後仍需持續維護。緩解方式：在 DOCUMENTATION_GOVERNANCE.md 中明確要求重構 PR 需對應更新 ADR 與領域文件
- **ADR 可能偏離原始決策** — 7 次重構中有部分由 AI agent 執行，實際決策細節可能不完全可追溯。緩解方式：ADR 根據最終程式碼與 commit log 撰寫，而非根據回憶
- **新增文件增加維護負擔** — 33+ 份新文件若無人維護會加速惡化。緩解方式：將文件更新納入 CI 審查檢查清單
