# 前後端分離遷移指南

## 📋 遷移摘要

本專案已成功從單體架構遷移至前後端分離架構，具備以下特色：

### 🎯 新架構特點
- **前端**: Vite + JavaScript + CSS3 建構的現代化 SPA
- **後端**: PHP 8.4 + DDD 架構的 RESTful API
- **部署**: Docker Compose 容器化部署
- **代理**: Nginx 反向代理，統一入口點

### 📂 目錄結構變更

#### 遷移前 (單體架構)
```
AlleyNote/
├── app/               # PHP 應用程式
├── config/            # 設定檔
├── public/            # Web 根目錄
├── database/          # 資料庫
├── tests/             # 測試
└── composer.json      # PHP 依賴
```

#### 遷移後 (前後端分離)
```
AlleyNote/
├── frontend/          # 前端應用程式
│   ├── src/           # 前端原始碼
│   ├── public/        # 前端公用資源
│   ├── dist/          # 建構產出
│   ├── package.json   # 前端依賴
│   └── vite.config.js # 建構設定
├── backend/           # 後端應用程式
│   ├── app/           # PHP 應用程式
│   ├── config/        # 設定檔
│   ├── public/        # API 入口點
│   ├── database/      # 資料庫
│   ├── tests/         # 測試
│   └── composer.json  # PHP 依賴
├── docker/            # Docker 設定
├── package.json       # 工作區管理
└── README.md          # 更新的文件
```

## 🚀 快速開始

### 環境需求
- **Node.js**: 18.0+
- **Docker**: 24.0+
- **Docker Compose**: 2.20+

### 一鍵啟動
```bash
# 1. 複製專案
git clone <repository-url>
cd alleynote

# 2. 啟動開發環境
npm run dev

# 3. 等待服務啟動，然後訪問
open http://localhost:3000  # 前端開發伺服器
open http://localhost       # 完整服務
```

### 服務網址
| 服務 | URL | 說明 |
|------|-----|------|
| 🌐 前端應用 | http://localhost:3000 | Vite 開發伺服器 |
| 🔌 API 服務 | http://localhost/api | RESTful API |
| 📚 API 文件 | http://localhost/api/docs/ui| Swagger 文件 |
| ❤️ 健康檢查 | http://localhost/health | 系統狀態 |

## 🔧 開發工作流程

### 前端開發
```bash
cd frontend

# 啟動開發伺服器
npm run dev

# 建構生產版本
npm run build

# 預覽生產版本
npm run preview
```

### 後端開發
```bash
# 進入後端容器
docker compose exec web bash

# 執行測試
composer test

# 程式碼檢查
composer ci
```

### 全專案管理
```bash
# 安裝所有依賴
npm install

# 啟動前後端服務
npm run dev

# 執行測試
npm run test

# 建構生產版本
npm run build
```

## 📦 部署指南

### 開發環境
```bash
# 啟動所有服務
docker compose up -d

# 檢查服務狀態
docker compose ps
```

### 生產環境
```bash
# 使用生產設定
docker compose -f docker compose.production.yml up -d

# 建構前端
npm run build

# 更新後端依賴
docker compose exec web composer install --no-dev --optimize-autoloader
```

## 🧪 測試驗證

### 驗證遷移成功
```bash
# 1. 檢查 Docker 服務
docker compose ps

# 2. 測試 API 健康檢查
curl http://localhost/api/health

# 3. 檢查前端載入
curl http://localhost/ | grep "AlleyNote"

# 4. 執行後端測試
docker compose exec web composer test

# 5. 程式碼品質檢查
docker compose exec web composer ci
```

### 預期結果
- ✅ 所有 Docker 服務運行正常
- ✅ API 回應 JSON 格式健康檢查
- ✅ 前端顯示「AlleyNote 巷弄筆記」
- ✅ 1214 個測試全部通過
- ✅ 程式碼品質檢查通過

## 🔄 回滾指南

如需回滾到遷移前狀態：

```bash
# 1. 檢出遷移前的 commit
git log --oneline | grep "遷移前"
git checkout <commit-hash>

# 2. 或使用 _migration_backup 目錄
# (如果有保留的話)
```

## 📞 技術支援

### 常見問題
1. **Docker 無法啟動**: 確認 Docker daemon 正在運行
2. **前端建構失敗**: 檢查 Node.js 版本是否為 18+
3. **API 連線失敗**: 確認後端容器正常運行
4. **權限問題**: 檢查檔案權限設定

### 開發團隊聯絡方式
- **技術負責人**: [聯絡資訊]
- **專案文件**: [文件連結]
- **問題回報**: [Issue 追蹤系統]

---

> 🎉 恭喜！前後端分離遷移已成功完成！
>
> 新架構提供更好的開發體驗、更清晰的程式碼結構，以及更靈活的部署選擇。
