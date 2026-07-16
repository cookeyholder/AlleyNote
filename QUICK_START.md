# AlleyNote 快速開始

## 系統需求

- Docker & Docker Compose（建議）
- PHP 8.4 + Composer（bare-metal 替代方案）

---

## Docker 安裝（建議）

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
cp backend/.env.example backend/.env
```

編輯 `backend/.env`，填入必要的環境變數（JWT 金鑰、資料庫路徑），然後啟動：

```bash
docker compose up -d
docker compose exec web php vendor/bin/phinx migrate
docker compose exec web php vendor/bin/phinx seed:run
```

### 預設端點

| 服務 | 網址 | 說明 |
|------|------|------|
| 前端 | `http://localhost:3000` | SPA 管理後台 |
| API | `http://localhost:8081` | REST API |
| API（production） | `http://localhost:8080` | Nginx 代理 |
| Swagger UI | `http://localhost:8081/api/docs/ui` | API 文件瀏覽 |

### 預設管理員

Seeder 執行後會建立預設管理員帳號：
- **Email**: `admin@alleynote.local`
- **密碼**: 由 `scripts/reset_admin.php` 設定

```bash
docker compose exec web php scripts/reset_admin.php
```

---

## Bare-metal 安裝

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote

# 後端
cd backend
cp .env.example .env
composer install
php vendor/bin/phinx migrate
php vendor/bin/phinx seed:run
php -S 0.0.0.0:8081 -t public

# 前端（另一個終端機）
cd frontend
npm install
npm run dev
```

---

## 開發指令

| 位置 | 指令 | 用途 |
|------|------|------|
| `backend/` | `composer test` | PHPUnit 測試 |
| `backend/` | `composer analyse` | PHPStan Level 10 |
| `backend/` | `composer cs-fix` | PHP-CS-Fixer 自動修正 |
| `frontend/` | `npm run lint` | Prettier 檢查 |
| `tests/e2e/` | `npm test` | Playwright E2E 測試 |

---

## 下一步

- [📖 文件總索引](docs/INDEX.md)
- [🛠️ 完整開發環境設定](docs/runbooks/01-開發環境.md)
- [🏗️ 架構總覽](docs/architecture/README.md)
