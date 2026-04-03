# 安全 Runbook

## 範圍

本文件為安全維運與控制項目的 canonical 概覽。

## 核心控制

- JWT 驗證機制（依現行 cookie/header 流程）
- 狀態變更路由的 CSRF 防護
- XSS 防護（輸入／輸出淨化與富文本淨化策略）
- SQL 注入防護（參數化查詢）
- 安全標頭（CSP、HSTS、X-Frame-Options 等）
- 密碼強度驗證與弱密碼黑名單

## 安全標頭驗證

```bash
# 前端標頭
curl -I http://localhost:3000

# API 標頭（開發預設）
export API_HOST=http://localhost:8081
curl -I $API_HOST/api/health
```

## 密碼安全

- 強制最小複雜度與黑名單檢查。
- 管理員帳號建議使用高熵隨機密碼。
- 文件中不得存放明文憑證。

## 事件處理／加固流程

1. 重現問題並界定影響範圍。
2. 以最小行為變更方式修補。
3. 新增或調整測試。
4. 重新執行本地 CI 模擬。
5. 更新受影響的 canonical 文件。

## 相關文件

- [文件治理規範](../DOCUMENTATION_GOVERNANCE.md)
- [開發 Runbook](DEVELOPMENT.md)
