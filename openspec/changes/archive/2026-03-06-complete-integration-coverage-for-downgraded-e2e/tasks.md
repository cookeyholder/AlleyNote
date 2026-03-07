## 1. OpenSpec 與矩陣

- [x] 1.1 建立 change 與降級能力承接矩陣
  - AC: proposal/design/spec 明確列出每個降級能力對應的 integration 覆蓋

## 2. 測試實作

- [x] 2.1 新增時區與設定持久化整合測試
  - AC: 驗證 `site_timezone` 設定讀取、更新、UTC↔site 轉換
- [x] 2.2 新增密碼規則矩陣整合測試
  - AC: 覆蓋長度、字元類型、連續字元、重複字元、常見密碼
- [x] 2.3 新增標籤/角色/權限關聯整合測試
  - AC: 覆蓋 role-permissions 綁定與 post-tags 移除一致性
- [x] 2.4 新增富文本安全渲染整合測試
  - AC: 危險標籤被移除、允許標籤保留

## 3. 驗證與收斂

- [x] 3.1 執行新增整合測試
  - AC: 新增測試全綠
