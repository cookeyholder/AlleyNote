## ADDED Requirements

### Requirement: Tailwind CDN 加上 SRI Integrity
`frontend/index.html` 中的 Tailwind CSS CDN `<script>` 標籤必須包含 `integrity` 屬性，使用 Subresource Integrity（SRI）確保 CDN 內容未被竄改。

#### Scenario: Tailwind script 有 integrity 屬性
- **WHEN** 瀏覽器載入 `index.html`
- **THEN** `cdn.tailwindcss.com` 的 `<script>` 標籤包含 `integrity` 與 `crossorigin="anonymous"` 屬性

### Requirement: CSP 規則包含 Tailwind CDN 來源
`SecurityHeaderService` 的 Content-Security-Policy 必須在 `script-src` 與 `style-src` 中包含 `https://cdn.tailwindcss.com`，確保 Tailwind CDN 不被 CSP 阻擋。

#### Scenario: CSP 允許 Tailwind CDN
- **WHEN** 瀏覽器載入 AlleyNote 頁面
- **THEN** 回應標頭 `Content-Security-Policy` 的 `script-src` 包含 `https://cdn.tailwindcss.com`
- **AND** `style-src` 包含 `https://cdn.tailwindcss.com`

### Requirement: 後台標籤渲染使用安全方法
`postEditor.js` 中渲染標籤名稱時須使用 `escapeHtml` 跳脫，禁止直接將使用者輸入的標籤名稱插入 `innerHTML`。

#### Scenario: 標籤名稱含特殊字元時正確跳脫
- **WHEN** 標籤名稱為 `<img src=x onerror=alert(1)>`
- **AND** `renderSelectedTags()` 渲染該標籤
- **THEN** 頁面顯示文字 `<img src=x onerror=alert(1)>`
- **AND** 不執行 JavaScript
