# 前端架構遷移記錄

## 遷移摘要

**日期**: 2025年10月

**從**: Vite + 構建工具 + npm
**到**: 純 HTML/JavaScript/CSS（原生方案）

## 遷移原因

1. **簡化架構** - 移除不必要的構建複雜度
2. **降低維護成本** - 無需管理 node_modules 和構建配置
3. **提升部署速度** - 無需構建步驟，直接部署
4. **改善開發體驗** - 修改即生效，無需等待構建
5. **減少依賴** - 不依賴 Node.js 生態系統

## 技術棧變更

### 移除的技術

- ❌ Vite（構建工具）
- ❌ npm/package.json（套件管理）
- ❌ node_modules（依賴目錄）
- ❌ vite.config.js（構建配置）
- ❌ 構建和打包流程

### 保留/新增的技術

- ✅ 原生 HTML5
- ✅ 原生 JavaScript ES6+ Modules
- ✅ Tailwind CSS（透過 CDN）
- ✅ CKEditor 5（透過 CDN）
- ✅ Chart.js（透過 CDN）
- ✅ DOMPurify（透過 CDN）
- ✅ Docker + nginx（靜態檔案服務）

## 目錄結構變更

### 舊結構（Vite）

```
frontend/
├── src/
│   ├── main.js
│   ├── components/
│   ├── pages/
│   ├── api/
│   ├── utils/
│   └── styles/
├── public/
│   ├── index.html
│   └── assets/
├── package.json
├── vite.config.js
├── node_modules/
└── dist/ (構建輸出)
```

### 新結構（原生）

```
frontend/
├── index.html         # 主入口（直接在根目錄）
├── js/                # JavaScript 模組
│   ├── main.js       # 應用程式入口
│   ├── api/          # API 客戶端
│   ├── components/   # UI 組件
│   ├── pages/        # 頁面模組
│   └── utils/        # 工具函式
├── css/               # 樣式表
│   └── main.css
└── assets/            # 靜態資源
    ├── images/
    └── icons/
```

## 程式碼遷移

### 模組導入方式

**舊方式（Vite）**:
```javascript
import { authApi } from './api/auth.js';
import Modal from './components/Modal.js';
```

**新方式（原生 ES Modules）**:
```javascript
import { authApi } from './api/auth.js';
import { Modal } from './components/Modal.js';

// 在 HTML 中使用 type="module"
<script type="module" src="./js/main.js"></script>
```

### CSS 載入方式

**舊方式（Vite）**:
```javascript
import './styles/main.css';
```

**新方式（原生）**:
```html
<link rel="stylesheet" href="./css/main.css">
```

### 環境變數

**舊方式（Vite）**:
```javascript
const apiUrl = import.meta.env.VITE_API_URL;
```

**新方式（原生）**:
```javascript
// 直接在程式碼中配置或從 window 物件讀取
const apiUrl = window.location.hostname === 'localhost' 
  ? 'http://localhost:8080/api'
  : '/api';
```

### 第三方套件

**舊方式（npm）**:
```bash
npm install axios chart.js ckeditor5
```

**新方式（CDN）**:
```html
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/latest/classic/ckeditor.js"></script>
```

## 部署變更

### 舊部署流程（Vite）

```bash
# 1. 安裝依賴
npm install

# 2. 構建專案
npm run build

# 3. 部署 dist/ 目錄
docker-compose up -d
```

### 新部署流程（原生）

```bash
# 直接啟動，無需構建
docker-compose up -d
```

## Docker 配置變更

### 舊配置（需要構建）

```yaml
nginx:
  volumes:
    - ./frontend/dist:/usr/share/nginx/html
```

### 新配置（直接掛載）

```yaml
nginx:
  volumes:
    - ./frontend:/usr/share/nginx/html
```

## 開發流程變更

### 舊開發流程

```bash
# 啟動開發伺服器
npm run dev

# 開發時有 HMR（熱模組替換）
# 訪問 http://localhost:5173
```

### 新開發流程

```bash
# 啟動 Docker
docker-compose up -d

# 直接編輯檔案，刷新瀏覽器即可
# 訪問 http://localhost:3000
```

## 優勢與劣勢

### 優勢 ✅

1. **零構建時間** - 修改後直接刷新瀏覽器
2. **簡化部署** - 無需 npm install 和 npm run build
3. **降低複雜度** - 無需管理 package.json 和 node_modules
4. **減少依賴** - 不依賴 Node.js 環境
5. **易於理解** - 對初學者更友善
6. **減少錯誤** - 沒有構建錯誤和版本衝突

### 劣勢 ❌

1. **無自動優化** - 需手動優化程式碼
2. **無 HMR** - 需手動刷新瀏覽器
3. **CDN 依賴** - 需要網路連線（可改用本地檔案）
4. **無型別檢查** - 沒有 TypeScript 支援
5. **模組化限制** - 需遵守瀏覽器的 ES Module 規範

## 遷移檢查清單

- [x] 移除 package.json 和 node_modules
- [x] 移除 vite.config.js
- [x] 將 src/ 重新組織到根目錄
- [x] 將 index.html 移到根目錄
- [x] 更新模組導入路徑
- [x] 改用 CDN 載入第三方套件
- [x] 更新 Docker 配置
- [x] 測試所有功能正常運作
- [x] 更新相關文件

## 回滾計劃

如需回滾到 Vite 版本：

```bash
# 備份已在以下位置
cd /Users/cookeyholder/projects/AlleyNote
mv frontend frontend_native_backup
mv frontend_old frontend  # 或 frontend_vite_backup_*
```

## 相關文件

- [前端 README](./README.md) - 新架構說明
- [快速開始指南](/QUICK_START.md) - 更新後的開發指南
- [主 README](/README.md) - 專案總覽

## 遷移完成日期

**2025年10月**
