# JWT 設定工具指南

這是一個全面的 JWT 金鑰管理和配置工具，提供了從初始設定到金鑰輪替的完整解決方案。

## 功能概述

### 1. 基本設定 (`setup`)
- 自動產生 RSA 金鑰對（2048 bits）
- 配置完整的 JWT 環境變數
- 驗證金鑰對的正確性
- 測試 JWT 配置整合

### 2. 金鑰輪替 (`rotate`)
- 自動備份現有 .env 檔案和金鑰
- 產生新的高強度金鑰對（4096 bits）
- 無縫更新環境配置
- 提供清楚的操作提醒

### 3. 健康檢查 (`health`)
- 檢查 .env 檔案存在性
- 驗證所有必要的 JWT 配置項目
- 測試金鑰對的有效性
- 分析金鑰強度和安全性
- 檢查檔案權限

### 4. 開發者工具 (`dev-keys`)
- 產生臨時測試用金鑰
- 提供 .env 格式的金鑰輸出
- 不會修改現有配置

## 使用方法

### 基本指令

```bash
# 初始設定（預設指令）
php scripts/jwt-setup.php setup

# 金鑰輪替（安全更新）
php scripts/jwt-setup.php rotate

# 健康檢查
php scripts/jwt-setup.php health

# 產生開發用金鑰
php scripts/jwt-setup.php dev-keys

# 顯示說明
php scripts/jwt-setup.php help
```

### Docker 環境使用

```bash
# 在 Docker 容器中執行
sudo docker compose exec -T web php scripts/jwt-setup.php [command]
```

## 使用場景

### 🔧 初始開發設定
當新建立專案或新開發者加入時：
```bash
sudo docker compose exec -T web php scripts/jwt-setup.php setup
```

### 🔄 定期金鑰輪替
為了安全性，建議定期輪替金鑰：
```bash
sudo docker compose exec -T web php scripts/jwt-setup.php rotate
```

### 🏥 問題診斷
當 JWT 相關測試失敗時：
```bash
sudo docker compose exec -T web php scripts/jwt-setup.php health
```

### 🛠️ 開發除錯
需要臨時金鑰進行測試時：
```bash
sudo docker compose exec -T web php scripts/jwt-setup.php dev-keys
```

## 安全特性

### 金鑰強度
- 初始設定：2048 bits RSA 金鑰
- 輪替升級：4096 bits RSA 金鑰
- 使用 SHA-256 雜湊算法

### 備份機制
- 自動備份 .env 檔案（含時間戳記）
- 分別備份舊的私鑰和公鑰
- 備份檔案儲存在 `storage/jwt-keys-backup/`

### 驗證流程
- 金鑰對一致性驗證
- 簽名/驗證功能測試
- 整合配置驗證

## 輸出格式

工具使用顏色編碼的輸出：
- 🟢 **綠色**：成功操作和通過的檢查
- 🟡 **黃色**：警告和重要提醒
- 🔴 **紅色**：錯誤和失敗的操作
- ⚪ **白色**：一般資訊和說明

## 常見問題

### Q: 執行輪替後測試失敗？
A: 這是正常的，因為所有現有的 JWT Token 會失效。請重新執行相關測試。

### Q: 如何恢復舊金鑰？
A: 使用備份的 .env 檔案：
```bash
cp .env.backup.[timestamp] .env
```

### Q: 開發環境和生產環境金鑰管理？
A: 建議使用不同的金鑰，生產環境應該使用更高的安全等級和專用的金鑰管理系統。

## 整合建議

### CI/CD 整合
可以將健康檢查加入 CI/CD 流程：
```bash
php scripts/jwt-setup.php health || exit 1
```

### 定期維護
建議建立 cron job 定期檢查 JWT 系統狀態：
```bash
0 6 * * 1 cd /path/to/project && php scripts/jwt-setup.php health > /dev/null || echo "JWT health check failed"
```

---

**注意：** 此工具設計用於開發和測試環境。生產環境建議使用專業的金鑰管理服務和更嚴格的安全措施。