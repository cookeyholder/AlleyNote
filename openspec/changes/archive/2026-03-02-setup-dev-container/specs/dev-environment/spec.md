## ADDED Requirements

### Requirement: 標準化開發環境
系統 SHALL 提供基於 Docker 容器的標準化開發環境，包含 PHP 8.4、Node.js 24+ 與 Git。

#### Scenario: 啟動開發容器
- **WHEN** 使用者在 VS Code 中執行 'Reopen in Container'
- **THEN** 系統應成功構建並進入包含 PHP 與 Node.js 環境的容器

### Requirement: VS Code 擴充套件預裝
系統 SHALL 在開發容器啟動時自動安裝必要的 VS Code 擴充套件（如 PHP Intelephense, ESLint, Tailwind CSS IntelliSense）。

#### Scenario: 檢查擴充套件
- **WHEN** 使用者進入開發容器後
- **THEN** VS Code 應已啟用配置在 devcontainer.json 中的所有擴充套件
