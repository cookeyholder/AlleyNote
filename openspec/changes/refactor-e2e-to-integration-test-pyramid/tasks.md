## 1. 建立分層與範圍

- [x] 1.1 完成全量 E2E 檔案盤點與降級候選清單
  - AC: 每個 spec 皆有「保留/拆分/降級/合併」判斷
- [x] 1.2 建立 OpenSpec change（proposal/design/tasks/spec）
  - AC: 文件可清楚描述責任切分與 apply 範圍

## 2. E2E 降級套用（本次）

- [x] 2.1 降級時區與設定/統計/編輯器等高環境依賴套件
  - AC: 相關 suite 不再造成硬失敗（fail）
- [x] 2.2 精簡批次刪除與後台綜合巡檢重複情境
  - AC: 保留核心路徑，移除冗餘巡檢
- [x] 2.3 將資料正確性類 assertions 從 E2E 轉為 smoke 形態
  - AC: E2E 主要失敗改為流程/導覽/互動層問題

## 3. 整合測試接手（後續）

- [ ] 3.1 新增 Password 規則矩陣整合測試
  - AC: 覆蓋長度、字元集、黑名單、確認密碼一致性
- [ ] 3.2 新增 Timezone 轉換與儲存一致性整合測試
  - AC: 覆蓋網站時區↔UTC 轉換與 DB round-trip
- [ ] 3.3 新增 Settings 持久化與跨頁生效整合測試
  - AC: 覆蓋 key 設定值寫入、讀取與跨模組反映
- [ ] 3.4 新增 Statistics API 數據正確性整合測試
  - AC: 覆蓋範圍切換、聚合、排序

## 4. 驗證與收斂

- [ ] 4.1 執行 E2E 全套並確認無失敗（允許 skip）
  - AC: unexpected failures = 0
- [ ] 4.2 補充 docs/testing 說明測試分層準則
  - AC: 開發者可依準則判斷新測試應放置層級
