# AlleyNote

> Bulletin board platform · DDD Architecture · PHP 8.4 + SQLite + ES6 SPA

![PHP](https://img.shields.io/badge/PHP-8.4-%23777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
[![CI](https://github.com/cookeyholder/AlleyNote/actions/workflows/ci.yml/badge.svg)](https://github.com/cookeyholder/AlleyNote/actions/workflows/ci.yml)

---

## Who are you?

| Role | Start here |
|------|-----------|
| 🖥️ Backend Developer | [`docs/architecture/01-統計分析器模式.md`](docs/architecture/01-統計分析器模式.md) |
| 🎨 Frontend Developer | [`docs/frontend/01-架構總覽.md`](docs/frontend/01-架構總覽.md) |
| 📝 Content Manager | [`docs/guides/content-creators/01-管理後台使用手冊.md`](docs/guides/content-creators/01-管理後台使用手冊.md) |
| ⚙️ System Admin | [`docs/guides/admin/03-系統需求.md`](docs/guides/admin/03-系統需求.md) |
| 🔌 API Integrator | [`docs/api/README.md`](docs/api/README.md) |
| 👋 Newcomer | [`docs/INDEX.md`](docs/INDEX.md) |

---

## Quick Start

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
cp backend/.env.example backend/.env
# Edit .env to set JWT_PRIVATE_KEY and JWT_PUBLIC_KEY
docker compose up -d
```

Full guide → [`docs/runbooks/01-開發環境.md`](docs/runbooks/01-開發環境.md)

---

## Development Commands

| Location | Command | Purpose |
|----------|---------|---------|
| `backend/` | `composer test` | PHPUnit tests (2220+) |
| `backend/` | `composer analyse` | PHPStan Level 10 static analysis |
| `backend/` | `composer cs-fix` | PHP-CS-Fixer auto-format |
| `backend/` | `composer check-all` | Analyse + style + tests |
| `frontend/` | `npm run lint` | Prettier check |
| `frontend/` | `npm run dev` | Dev server (port 3000) |
| `tests/e2e/` | `npm test` | Playwright E2E tests |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.4, DDD, PHP-DI 7.x, FastRoute, PSR-7/15 |
| Database | SQLite 3 + PDO (no ORM) |
| Cache | Redis (Predis) + SQLite fallback |
| Auth | JWT (RS256), firebase/php-jwt |
| Frontend | ES6 modules (no build tools), Tailwind CSS CDN, CKEditor 5 |
| Charts | Chart.js 4.x |
| Container | Docker Compose (PHP 8.4 + Nginx + Redis) |
| CI/CD | GitHub Actions (PHPStan, PHPUnit, Playwright, CodeQL) |

---

## Project Structure

```
AlleyNote/
├── backend/
│   ├── app/
│   │   ├── Application/     # Controllers, middleware, resources, services
│   │   ├── Domains/         # 7 Bounded Contexts
│   │   │   ├── Post/        # Core bulletin domain
│   │   │   ├── Auth/        # Authentication & authorization
│   │   │   ├── Statistics/  # Analytics
│   │   │   ├── Security/    # Security & rate limiting
│   │   │   ├── Attachment/  # File uploads
│   │   │   ├── Setting/     # System settings
│   │   │   └── Shared/      # Shared value objects
│   │   ├── Infrastructure/  # Infrastructure implementations
│   │   └── Shared/          # Shared kernel
│   ├── config/              # DI container, route definitions
│   ├── database/            # Phinx migrations
│   ├── tests/               # Unit, integration, functional, security tests
│   └── public/              # Entry point index.php
├── frontend/
│   ├── index.html
│   ├── js/                  # ES6 modules
│   └── css/                 # Tailwind styles
├── docker/                  # PHP, Nginx, Redis config
├── tests/e2e/               # Playwright E2E tests
└── docs/                    # Documentation (5 reader personas)
    ├── decisions/           # ADRs (Architecture Decision Records)
    ├── domains/             # Domain overviews
    ├── architecture/        # Design documents
    ├── frontend/            # Frontend architecture
    └── guides/              # Manuals
```

---

## Links

- [📖 Documentation index](docs/INDEX.md)
- [📋 Changelog](CHANGELOG.md)
- [🤝 Contributing](CONTRIBUTING.md)
- [🛡️ Documentation governance](docs/DOCUMENTATION_GOVERNANCE.md)
- [🐛 Report an issue](https://github.com/cookeyholder/AlleyNote/issues)

---

## License

MIT
