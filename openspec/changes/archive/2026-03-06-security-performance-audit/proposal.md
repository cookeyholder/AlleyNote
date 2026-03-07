## Why

GitHub 回報了我們專案依賴套件中的資安漏洞（例如：針對 `minimatch` 和 `rollup` 的 Dependabot 警告）。此外，我們必須透過逐行（line-by-line）的全面性程式碼審查，並導入最佳實務，來確保整個程式碼庫具備最高等級的資訊安全與效能表現。這能保障我們的應用程式在面對不斷演進的威脅時能保持安全，並且運作得更有效率。

## What Changes

- 解決所有仍在活躍狀態的 GitHub Dependabot 與 CodeQL 警告（例如：ReDoS 與路徑遍歷漏洞）。
- 針對前端與後端程式碼庫，執行嚴謹的逐行資安與效能審查。
- 升級或替換有漏洞的相依套件。
- 重構審查過程中發現的低效演算法或資料庫查詢。
- 實作自動化的資安與效能檢查，以防止未來出現效能退化或安全漏洞。

## Capabilities

### New Capabilities
- `security-performance-standards`：定義專案所需的嚴謹資安規範、漏洞修復程序以及效能基準（benchmarks）。

### Modified Capabilities

## Impact

- **Dependencies (相依套件):** 更新前端（`package.json`）與後端（`composer.json`）的相依套件。
- **Codebase (程式碼庫):** 對前端和後端程式碼進行廣泛的重構，以解決資安漏洞和效能瓶頸。
- **CI/CD (持續整合與部署):** 針對 GitHub Actions 工作流程（workflows）進行潛在的強化，以支援持續性的資安掃描。