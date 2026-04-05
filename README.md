# AlleyNote

AlleyNote 是採用 DDD 思維的公告／佈告平台，具備角色式管理、安全控制與統計分析功能。

## Canonical 文件入口

- 文件總索引：[docs/INDEX.md](docs/INDEX.md)
- 開發 runbook：[docs/runbooks/DEVELOPMENT.md](docs/runbooks/DEVELOPMENT.md)
- 重構技術細節（2026-04）：[docs/architecture/BACKEND_REFACTOR_2026-04.md](docs/architecture/BACKEND_REFACTOR_2026-04.md)
- CI/CD runbook：[docs/runbooks/CI_CD.md](docs/runbooks/CI_CD.md)
- 安全 runbook：[docs/runbooks/SECURITY.md](docs/runbooks/SECURITY.md)
- API 文件入口：[docs/api/README.md](docs/api/README.md)
- 文件治理規範：[docs/DOCUMENTATION_GOVERNANCE.md](docs/DOCUMENTATION_GOVERNANCE.md)

## 快速開始

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
cp backend/.env.example backend/.env
docker compose up -d
```

完整流程請參考：
- [docs/runbooks/DEVELOPMENT.md](docs/runbooks/DEVELOPMENT.md)

## 授權

MIT
