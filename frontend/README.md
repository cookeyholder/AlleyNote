# AlleyNote 前端

純 HTML + JavaScript + CSS 架構，無需構建工具。

## 目錄結構

```
frontend/
├── index.html          # 主頁面
├── css/                # 樣式表
│   └── main.css       # 主要樣式
├── js/                 # JavaScript 模組
│   ├── api/           # API 客戶端
│   │   ├── client.js  # HTTP 客戶端
│   │   ├── auth.js    # 認證 API
│   │   ├── posts.js   # 文章 API
│   │   ├── users.js   # 使用者 API
│   │   └── statistics.js # 統計 API
│   ├── components/    # UI 組件
│   │   ├── Modal.js   # 對話框組件
│   │   └── Loading.js # 載入中組件
│   ├── pages/         # 頁面模組
│   │   ├── public/    # 公開頁面
│   │   │   ├── home.js   # 首頁
│   │   │   └── login.js  # 登入頁
│   │   └── admin/     # 管理後台頁面
│   │       └── dashboard.js # 儀表板
│   ├── utils/         # 工具模組
│   │   ├── router.js  # 路由器
│   │   ├── toast.js   # 通知工具
│   │   └── validator.js # 表單驗證
│   └── main.js        # 主程式入口
├── assets/            # 靜態資源
│   ├── images/        # 圖片
│   └── icons/         # 圖示
└── html/              # HTML 模板（未使用）
    ├── admin/
    └── public/
```

## 特性

- ✅ 零構建工具依賴
- ✅ 純 ES6 模組
- ✅ 使用 Tailwind CSS CDN
- ✅ CKEditor 5 整合
- ✅ Chart.js 整合
- ✅ DOMPurify XSS 防護
- ✅ 簡單的前端路由
- ✅ Toast 通知系統
- ✅ Modal 對話框組件
- ✅ 表單驗證工具

## 開發

### Docker 部署（推薦）

前端透過 Docker Compose 中的 nginx 容器提供服務：

```bash
# 啟動所有服務
docker-compose up -d

# 訪問前端
open http://localhost:3000

# 查看 nginx 日誌
docker-compose logs -f nginx
```

### 本地開發（不推薦）

如果需要獨立運行前端進行測試：

```bash
# 使用 Python 本地伺服器
cd frontend
python3 -m http.server 3000

# 或使用 PHP 內建伺服器
php -S localhost:3000
```

**注意**：本地開發時需要修改 API URL 或設定 CORS。

## API 配置

API 基礎 URL 在 `js/api/client.js` 中配置：

```javascript
getBaseURL() {
    const protocol = window.location.protocol;
    const hostname = window.location.hostname;
    return `${protocol}//${hostname}:8080/api`;
}
```

## 路由

路由在 `js/main.js` 中定義：

- `/` - 首頁
- `/login` - 登入頁
- `/post/:id` - 文章詳情
- `/admin/dashboard` - 管理後台儀表板
- `/admin/posts` - 文章管理
- `/admin/users` - 使用者管理
- 等等...

## 注意事項

1. 需要現代瀏覽器支援 ES6 模組
2. 使用 CDN 資源（需要網路連線）
3. CORS 需要正確配置
4. 本地開發需要使用本地伺服器（不能直接開啟 HTML 檔案）

## 移除的內容

- ❌ Vite 構建工具
- ❌ npm/package.json
- ❌ node_modules
- ❌ 構建配置檔案
- ❌ Vue.js（已經沒有使用）

## 遷移說明

舊的 Vite 前端已備份至：
- `frontend_vite_backup_*` 目錄
- `frontend_old` 目錄

如需回滾：
```bash
mv frontend frontend_new
mv frontend_old frontend
```
