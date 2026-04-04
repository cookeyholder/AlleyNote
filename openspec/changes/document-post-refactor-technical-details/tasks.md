## 1. 重構面向盤點與文件範圍確認

- [ ] 1.1 以 `code-reviewer` 腳本重新盤點重構影響面，整理 `ExceptionRegistry`、`ApiResource`、`ApiTestCase` 的實際程式入口與相依檔案
- [ ] 1.2 建立「重大重構文件更新範圍」對照表，明確列出需更新的 canonical 路徑（`README.md`、`docs/runbooks/DEVELOPMENT.md`、`docs/architecture/*`）

## 2. Canonical 文件內容更新

- [ ] 2.1 更新 `README.md`：新增重構後技術文件入口，確保從根目錄可導覽至技術細節章節
- [ ] 2.2 更新 `docs/runbooks/DEVELOPMENT.md`：補齊例外處理、Resource 轉換與測試 DSL 的實作邊界與反模式說明
- [ ] 2.3 新增或更新 `docs/architecture/` 文件：完整記錄設計決策、擴充邊界、測試策略與風險/取捨

## 3. 治理流程與驗證

- [ ] 3.1 更新 PR/審查清單（或等價流程文件）：加入「重大重構技術文件完整性」四項核對點
- [ ] 3.2 在 OpenSpec change 任務中回填所有實際文件路徑與完成證據，確保可追蹤
- [ ] 3.3 執行連結與內容一致性檢查，確認文件指向的類別、路徑與命令皆與目前程式碼一致
