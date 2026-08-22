# 提案：CI 流程全面加速與效能最佳化

## 背景與動機
目前專案在發起 Pull Request (PR) 時，GitHub Actions CI 的完整檢查等待時間高達 4 分 45 秒。主要瓶頸在於：
1. 後端測試覆蓋率使用重量級的 Xdebug，執行時間過長。
2. 端對端 E2E 測試以單一 Worker 循序執行，未能善用 Runner 的多核心效能。
3. 各工作流程重複執行套件安全掃描，且未配置並行取消機制（Concurrency Cancel-in-progress）。
4. 缺少非程式碼文件變更的路徑過濾（Path Filtering）。

## 預期目標
1. 將單元測試覆蓋率收集引擎切換為 PCOV，大幅縮短 PHPUnit 耗時。
2. 開啟 E2E 測試 2 個並行 Worker。
3. 在所有工作流程導入 `concurrency` 控管與 `paths-ignore` 智慧過濾。
4. 導入 PHPStan 靜態分析快取。
5. 將 PR 的 CI 最長關鍵路徑耗時由 4m 45s 降低至 2 分鐘以內。
