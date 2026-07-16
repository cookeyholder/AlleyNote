# AlleyNote 前端部署指南

## 📋 目錄

1. [概述](#概述)
2. [構建流程](#構建流程)
3. [環境配置](#環境配置)
4. [部署方案](#部署方案)
5. [效能優化](#效能優化)
6. [監控與日誌](#監控與日誌)
7. [CI/CD 流程](#cicd-流程)
8. [故障排除](#故障排除)

---

## 概述

本文件說明 AlleyNote 前端的部署流程、環境配置與最佳實踐，確保應用程式能夠穩定、高效地運行於生產環境。

### 部署目標

- ✅ **快速載入**: 首次內容繪製 < 1.5 秒
- ✅ **高可用性**: 99.9% 正常運作時間
- ✅ **安全性**: HTTPS、安全標頭、CSP
- ✅ **可擴展性**: 支援水平擴展
- ✅ **可監控性**: 完整的錯誤追蹤與效能監控

---

## 構建流程

> ⚠️ 備註：本專案前端現行為「無建置工具」架構，以下若出現 Vite 或 `dist/` 內容屬歷史參考，實際請以 `frontend/README.md` 的流程為準。

### 1. 開發環境建構

```bash
# 啟動服務
docker compose up -d

# 啟動開發伺服器（瀏覽器原生刷新）
直接編輯文件並刷新瀏覽器

# 開啟在 http://localhost:3000
```

### 2. 生產環境建構

```bash
# 建構生產版本
無需構建（已移除）

# 驗證服務
curl -I http://localhost:3000
```

### 3. 建構產物

建構後的檔案位於 `dist/` 目錄：

```
dist/
├── index.html            # 主要 HTML 檔案
├── assets/               # 靜態資源
│   ├── index-[hash].js   # 主要 JavaScript（含 hash）
│   ├── index-[hash].css  # 主要 CSS（含 hash）
│   └── vendor-[hash].js  # 第三方套件
├── images/               # 圖片資源
└── fonts/                # 字體檔案
```

### 4. 前端部署配置

**`（無需配置檔案）`**

```javascript
import { resolve } from "path";

export default defineConfig({
  // 基礎路徑
  base: "/",

  // 建構選項
  build: {
    // 輸出目錄
    outDir: "dist",

    // 資源目錄
    assetsDir: "assets",

    // 生成 source map（僅開發環境）
    sourcemap: process.env.NODE_ENV === "development",

    // 壓縮選項
    minify: "terser",
    terserOptions: {
      compress: {
        drop_console: true, // 移除 console.log
        drop_debugger: true, // 移除 debugger
      },
    },

    // Code Splitting
    rollupOptions: {
      output: {
        // 手動 chunk 分割
        manualChunks: {
          // 將 vendor 套件單獨打包
          vendor: ["axios", "dompurify", "validator"],

          // 將 CKEditor 單獨打包（較大）
          editor: ["@ckeditor/ckeditor5-build-classic"],
        },

        // 資源命名
        chunkFileNames: "assets/[name]-[hash].js",
        entryFileNames: "assets/[name]-[hash].js",
        assetFileNames: "assets/[name]-[hash].[ext]",
      },
    },

    // 檔案大小警告限制（500KB）
    chunkSizeWarningLimit: 500,

    // 清空輸出目錄
    emptyOutDir: true,
  },

  // 伺服器選項
  server: {
    port: 5173,
    host: true,

    // API 代理
    proxy: {
      "/api": {
        target: process.env.API_HOST || "http://localhost:8081",
        changeOrigin: true,
      },
    },
  },

  // 預覽伺服器選項
  preview: {
    port: 4173,
    host: true,
  },

  // 路徑別名
  resolve: {
    alias: {
      "@": resolve(__dirname, "src"),
      "@api": resolve(__dirname, "src/api"),
      "@components": resolve(__dirname, "src/components"),
      "@utils": resolve(__dirname, "src/utils"),
      "@store": resolve(__dirname, "src/store"),
    },
  },
});
```

---

## 環境配置

### 環境變數

**`.env.development`**

```bash
# 開發環境配置
# API_HOST=http://localhost:8081   # DevContainer
# API_HOST=http://localhost:8080   # Production-like
VITE_API_BASE_URL=$API_HOST/api
VITE_API_TIMEOUT=30000
VITE_ENABLE_API_LOGGER=true
VITE_ENABLE_API_MOCK=false
```

**`.env.production`**

```bash
# 生產環境配置
VITE_API_BASE_URL=https://api.alleynote.com/api
VITE_API_TIMEOUT=30000
VITE_ENABLE_API_LOGGER=false
VITE_ENABLE_API_MOCK=false

# 監控與分析
VITE_SENTRY_DSN=https://xxxxx@sentry.io/xxxxx
VITE_GA_TRACKING_ID=UA-XXXXX-X
```

**`.env.staging`**

```bash
# 測試環境配置
VITE_API_BASE_URL=https://staging-api.alleynote.com/api
VITE_API_TIMEOUT=30000
VITE_ENABLE_API_LOGGER=true
VITE_ENABLE_API_MOCK=false
```

### 環境變數使用

```javascript
// src/config/env.js
export const env = {
  apiBaseURL: import.meta.env.VITE_API_BASE_URL,
  apiTimeout: parseInt(import.meta.env.VITE_API_TIMEOUT),
  enableLogger: import.meta.env.VITE_ENABLE_API_LOGGER === "true",
  enableMock: import.meta.env.VITE_ENABLE_API_MOCK === "true",
  isDevelopment: import.meta.env.DEV,
  isProduction: import.meta.env.PROD,
};
```

---

## 部署方案

### 方案一：Nginx + Docker（推薦）

#### Dockerfile

```dockerfile
# 多階段建構
FROM node:18-alpine AS builder

WORKDIR /app

# 複製 package.json 與 lock 檔案
COPY package*.json ./

# 安裝依賴
RUN npm ci --only=production

# 複製原始碼
COPY . .

# 建構
RUN 無需構建（已移除）

# 生產階段
FROM nginx:alpine

# 複製建構產物
COPY --from=builder /app/dist /usr/share/nginx/html

# 複製 Nginx 配置
COPY nginx.conf /etc/nginx/conf.d/default.conf

# 暴露埠口
EXPOSE 80

# 啟動 Nginx
CMD ["nginx", "-g", "daemon off;"]
```

#### Nginx 配置

**`nginx.conf`**

```nginx
server {
    listen 80;
    server_name alleynote.com;

    # 根目錄
    root /usr/share/nginx/html;
    index index.html;

    # Gzip 壓縮
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript
               application/x-javascript application/xml+rss
               application/json application/javascript;

    # 安全標頭
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.ckeditor.com https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.alleynote.com;" always;

    # 快取策略
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # API 代理
    location /api {
        proxy_pass http://backend:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # SPA 路由（History API）
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 健康檢查
    location /health {
        access_log off;
        return 200 "OK";
        add_header Content-Type text/plain;
    }
}
```

#### Docker Compose

```yaml
version: "3.8"

services:
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    ports:
      - "80:80"
    depends_on:
      - backend
    environment:
      - NODE_ENV=production
    networks:
      - alleynote-network
    restart: unless-stopped

  backend:
    image: alleynote-backend:latest
    ports:
      - "8080:8080"
    networks:
      - alleynote-network
    restart: unless-stopped

networks:
  alleynote-network:
    driver: bridge
```

### 方案二：Vercel（快速部署）

#### Vercel 配置

**`vercel.json`**

```json
{
  "version": 2,
  "builds": [
    {
      "src": "package.json",
      "use": "@vercel/static-build",
      "config": {
        "distDir": "dist"
      }
    }
  ],
  "routes": [
    {
      "src": "/api/(.*)",
      "dest": "https://api.alleynote.com/api/$1"
    },
    {
      "src": "/(.*)",
      "dest": "/index.html"
    }
  ],
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        {
          "key": "X-Frame-Options",
          "value": "DENY"
        },
        {
          "key": "X-Content-Type-Options",
          "value": "nosniff"
        },
        {
          "key": "X-XSS-Protection",
          "value": "1; mode=block"
        }
      ]
    },
    {
      "source": "/assets/(.*)",
      "headers": [
        {
          "key": "Cache-Control",
          "value": "public, max-age=31536000, immutable"
        }
      ]
    }
  ]
}
```

#### 部署指令

```bash
# 安裝 Vercel CLI
npm install -g vercel

# 登入
vercel login

# 部署
vercel --prod
```

### 方案三：AWS S3 + CloudFront

#### 部署腳本

**`scripts/deploy-aws.sh`**

```bash
#!/bin/bash

# 建構
無需構建（已移除）

# 同步到 S3
aws s3 sync dist/ s3://alleynote-frontend \
  --delete \
  --cache-control "public, max-age=31536000, immutable" \
  --exclude "index.html" \
  --exclude "*.map"

# index.html 使用較短的快取
aws s3 cp dist/index.html s3://alleynote-frontend/index.html \
  --cache-control "public, max-age=0, must-revalidate"

# 清除 CloudFront 快取
aws cloudfront create-invalidation \
  --distribution-id YOUR_DISTRIBUTION_ID \
  --paths "/*"

echo "✅ 部署完成！"
```

---

## 效能優化

### 1. 程式碼分割

```javascript
// 路由懶加載
const AdminDashboard = () => import("./pages/admin/Dashboard.js");
const PostEditor = () => import("./pages/admin/PostEditor.js");

// 條件載入
if (userRole === "admin") {
  const { AdminPanel } = await import("./components/AdminPanel.js");
  renderAdminPanel(AdminPanel);
}
```

### 2. 圖片優化

```javascript
/**
 * 圖片懶加載
 */
function setupLazyLoading() {
  const images = document.querySelectorAll("img[data-src]");

  const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        img.removeAttribute("data-src");
        imageObserver.unobserve(img);
      }
    });
  });

  images.forEach((img) => imageObserver.observe(img));
}

// 初始化
setupLazyLoading();
```

### 3. 資源預載入

```html
<!DOCTYPE html>
<html lang="zh-TW">
  <head>
    <!-- DNS 預解析 -->
    <link rel="dns-prefetch" href="https://api.alleynote.com" />
    <link rel="dns-prefetch" href="https://fonts.googleapis.com" />

    <!-- 預連線 -->
    <link rel="preconnect" href="https://api.alleynote.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <!-- 預載入關鍵資源 -->
    <link rel="preload" href="/assets/main.js" as="script" />
    <link rel="preload" href="/assets/main.css" as="style" />
    <link
      rel="preload"
      href="/fonts/inter-var.woff2"
      as="font"
      type="font/woff2"
      crossorigin
    />
  </head>
</html>
```

### 4. Service Worker（PWA）

**`public/sw.js`**

```javascript
const CACHE_NAME = "alleynote-v1";
const STATIC_ASSETS = [
  "/",
  "/index.html",
  "/assets/main.js",
  "/assets/main.css",
];

// 安裝
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    }),
  );
});

// 啟用
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        }),
      );
    }),
  );
});

// 攔截請求
self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      // 快取優先策略
      return response || fetch(event.request);
    }),
  );
});
```

**註冊 Service Worker**

```javascript
// src/main.js
if ("serviceWorker" in navigator && import.meta.env.PROD) {
  window.addEventListener("load", () => {
    navigator.serviceWorker
      .register("/sw.js")
      .then((registration) => {
        console.log("SW registered:", registration);
      })
      .catch((error) => {
        console.error("SW registration failed:", error);
      });
  });
}
```

### 5. 效能監控

```javascript
/**
 * Web Vitals 監控
 */
import { getCLS, getFID, getFCP, getLCP, getTTFB } from "web-vitals";

function sendToAnalytics(metric) {
  // 發送到分析服務（如 Google Analytics）
  if (window.gtag) {
    gtag("event", metric.name, {
      event_category: "Web Vitals",
      value: Math.round(metric.value),
      event_label: metric.id,
      non_interaction: true,
    });
  }

  console.log(metric.name, metric.value);
}

// 監控核心 Web Vitals
getCLS(sendToAnalytics);
getFID(sendToAnalytics);
getFCP(sendToAnalytics);
getLCP(sendToAnalytics);
getTTFB(sendToAnalytics);
```

---

## 監控與日誌

### 1. Sentry 錯誤追蹤

```bash
npm install @sentry/browser
```

```javascript
// src/utils/monitoring.js
import * as Sentry from "@sentry/browser";

export function initMonitoring() {
  if (import.meta.env.PROD) {
    Sentry.init({
      dsn: import.meta.env.VITE_SENTRY_DSN,
      environment: import.meta.env.MODE,
      release: `alleynote-frontend@${__APP_VERSION__}`,

      // 取樣率
      tracesSampleRate: 0.1,

      // 忽略特定錯誤
      ignoreErrors: [
        "ResizeObserver loop limit exceeded",
        "Non-Error promise rejection captured",
      ],

      // 面包屑（Breadcrumbs）
      beforeBreadcrumb(breadcrumb) {
        // 過濾敏感資訊
        if (breadcrumb.category === "console") {
          return null;
        }
        return breadcrumb;
      },

      // 事件前處理
      beforeSend(event, hint) {
        // 移除敏感資訊
        if (event.request) {
          delete event.request.cookies;
        }
        return event;
      },
    });
  }
}

// 手動捕獲錯誤
export function captureError(error, context = {}) {
  if (import.meta.env.PROD) {
    Sentry.captureException(error, { extra: context });
  } else {
    console.error("Error:", error, context);
  }
}
```

### 2. Google Analytics

```javascript
// src/utils/analytics.js
export function initAnalytics() {
  if (import.meta.env.PROD) {
    const script = document.createElement("script");
    script.src = `https://www.googletagmanager.com/gtag/js?id=${import.meta.env.VITE_GA_TRACKING_ID}`;
    script.async = true;
    document.head.appendChild(script);

    window.dataLayer = window.dataLayer || [];
    function gtag() {
      dataLayer.push(arguments);
    }
    gtag("js", new Date());
    gtag("config", import.meta.env.VITE_GA_TRACKING_ID);

    window.gtag = gtag;
  }
}

/**
 * 追蹤頁面瀏覽
 */
export function trackPageView(path) {
  if (window.gtag) {
    gtag("config", import.meta.env.VITE_GA_TRACKING_ID, {
      page_path: path,
    });
  }
}

/**
 * 追蹤事件
 */
export function trackEvent(action, category, label, value) {
  if (window.gtag) {
    gtag("event", action, {
      event_category: category,
      event_label: label,
      value: value,
    });
  }
}
```

---

## CI/CD 流程

### GitHub Actions

**`.github/workflows/deploy.yml`**

```yaml
name: Deploy Frontend

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: "18"
          cache: "npm"

      - name: Install dependencies
        run: npm ci

      - name: Run linter
        run: npm run lint

      - name: Run unit tests
        run: npm run test:coverage

      - name: Run E2E tests
        run: npm run test:e2e

      - name: Upload coverage
        uses: codecov/codecov-action@v3

  build:
    needs: test
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: "18"
          cache: "npm"

      - name: Install dependencies
        run: npm ci

      - name: Build
        run: 無需構建（已移除）
        env:
          VITE_API_BASE_URL: ${{ secrets.API_BASE_URL }}
          VITE_SENTRY_DSN: ${{ secrets.SENTRY_DSN }}

      - name: Upload build artifacts
        uses: actions/upload-artifact@v3
        with:
          name: dist
          path: dist/

  deploy:
    needs: build
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'

    steps:
      - name: Download build artifacts
        uses: actions/download-artifact@v3
        with:
          name: dist
          path: dist/

      - name: Deploy to production
        uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./dist
          cname: alleynote.com
```

---

## 故障排除

### 常見問題

#### 1. 白屏問題

**原因**: JavaScript 載入失敗或執行錯誤

**解決方式**:

```bash
# 檢查 Console 錯誤
# 檢查網路請求是否成功
# 確認 base URL 配置正確
```

#### 2. API 請求失敗

**原因**: CORS 配置錯誤或 API URL 錯誤

**解決方式**:

```javascript
// 檢查環境變數
console.log("API Base URL:", import.meta.env.VITE_API_BASE_URL);

// 檢查後端 CORS 設定
// Access-Control-Allow-Origin: https://alleynote.com
```

#### 3. 路由 404

**原因**: Nginx 未正確配置 History API

**解決方式**:

```nginx
# 確保有這行配置
location / {
    try_files $uri $uri/ /index.html;
}
```

#### 4. 快取問題

**原因**: 舊版本被快取

**解決方式**:

```bash
# 清除 CloudFront 快取
aws cloudfront create-invalidation --distribution-id XXX --paths "/*"

# 或在 HTML 加入版本號
<script src="/assets/main.js?v=1.0.1"></script>
```

---

## 檢查清單

### 部署前檢查

- [ ] 所有測試通過
- [ ] 建構成功無錯誤
- [ ] 環境變數設定正確
- [ ] API 端點可正常訪問
- [ ] HTTPS 憑證有效
- [ ] 安全標頭設定正確
- [ ] CSP 政策設定完整
- [ ] 壓縮與快取策略啟用
- [ ] 監控與錯誤追蹤已設定
- [ ] 備份與回滾計畫就緒

### 部署後檢查

- [ ] 網站可正常訪問
- [ ] 所有頁面都能正確顯示
- [ ] API 請求正常運作
- [ ] 使用者登入流程正常
- [ ] 圖片與靜態資源載入正常
- [ ] 手機版顯示正常
- [ ] 跨瀏覽器測試通過
- [ ] 效能指標符合預期（LCP < 2.5s）
- [ ] 錯誤追蹤系統接收資料
- [ ] 分析工具正常運作

---

## 總結

遵循本部署指南，AlleyNote 前端可以：

1. ✅ **穩定部署** - 完整的 CI/CD 流程
2. ✅ **高效能** - Code Splitting、快取策略、CDN
3. ✅ **高安全性** - HTTPS、CSP、安全標頭
4. ✅ **可監控** - Sentry 錯誤追蹤、Web Vitals
5. ✅ **易維護** - 清晰的部署流程與故障排除指南

**記住：部署不是終點，而是持續改進的起點。**
