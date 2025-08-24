<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'AlleyNote API',
    version: '1.0.0',
    description: "AlleyNote 公布欄系統 RESTful API 文件。提供文章管理、使用者認證、IP 管理等功能的完整 API 介面。\n\n## API 版本控制\n\n本 API 支援版本控制，透過以下方式指定版本：\n- URL 路徑：`/api/v1/posts`\n- Accept 標頭：`Accept: application/vnd.alleynote.v1+json`\n\n## 版本歷史\n\n- **v1.0.0** (2025-01-15): 初始版本，包含基本的貼文管理和認證功能\n- **v1.1.0** (規劃中): 新增留言系統和檔案管理\n- **v2.0.0** (規劃中): API 結構重構和效能優化",
    termsOfService: 'https://alleynote.example.com/terms',
    contact: new OA\Contact(
        name: 'AlleyNote 開發團隊',
        email: 'dev@alleynote.example.com',
        url: 'https://github.com/alleynote/alleynote',
    ),
    license: new OA\License(
        name: 'MIT',
        url: 'https://opensource.org/licenses/MIT',
    ),
)]
#[OA\Server(
    url: 'http://localhost/api/v1',
    description: '開發環境 API 伺服器 (v1.0)',
)]
#[OA\Server(
    url: 'https://api.alleynote.example.com/v1',
    description: '正式環境 API 伺服器 (v1.0)',
)]
#[OA\Server(
    url: 'http://localhost/api',
    description: '開發環境 API 伺服器 (最新版本)',
)]
#[OA\Server(
    url: 'https://api.alleynote.example.com',
    description: '正式環境 API 伺服器 (最新版本)',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: '使用 JWT Token 進行 Bearer 認證',
)]
#[OA\SecurityScheme(
    securityScheme: 'sessionAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'PHPSESSID',
    description: '使用 Session Cookie 進行認證',
)]
#[OA\SecurityScheme(
    securityScheme: 'csrfToken',
    type: 'apiKey',
    in: 'header',
    name: 'X-CSRF-TOKEN',
    description: 'CSRF 保護令牌，用於防止跨站請求偽造攻擊',
)]
#[OA\Tag(
    name: 'posts',
    description: "文章管理相關 API - 建立、讀取、更新、刪除文章\n\n支援的功能：\n- 分頁查詢和搜尋\n- 分類和優先級管理\n- 置頂和過期設定\n- 標籤系統",
    externalDocs: new OA\ExternalDocumentation(
        description: '了解更多文章管理功能',
        url: 'https://docs.alleynote.example.com/v1/posts',
    ),
)]
#[OA\Tag(
    name: 'auth',
    description: "身份驗證相關 API - 登入、登出、註冊、密碼重設\n\n支援的認證方式：\n- JWT Bearer Token\n- Session Cookie\n- API Key (管理員功能)",
    externalDocs: new OA\ExternalDocumentation(
        description: '認證機制說明',
        url: 'https://docs.alleynote.example.com/v1/auth',
    ),
)]
#[OA\Tag(
    name: 'ip',
    description: "IP 管理相關 API - IP 黑白名單管理\n\n功能包括：\n- IP 黑名單設定\n- IP 白名單管理\n- 存取記錄查詢",
    externalDocs: new OA\ExternalDocumentation(
        description: 'IP 管理說明',
        url: 'https://docs.alleynote.example.com/v1/ip',
    ),
)]
#[OA\Tag(
    name: 'attachments',
    description: "附件管理相關 API - 檔案上傳、下載、刪除\n\n支援的檔案類型：\n- 圖片：PNG, JPG, GIF, WebP\n- 文件：PDF, DOC, DOCX, TXT\n- 壓縮檔：ZIP, RAR\n\n檔案大小限制：最大 10MB",
    externalDocs: new OA\ExternalDocumentation(
        description: '檔案管理說明',
        url: 'https://docs.alleynote.example.com/v1/attachments',
    ),
)]
#[OA\Tag(
    name: 'system',
    description: "系統管理相關 API - 系統狀態、配置管理\n\n管理功能：\n- 系統健康檢查\n- 效能監控\n- 配置參數管理\n- 日誌查詢",
    externalDocs: new OA\ExternalDocumentation(
        description: '系統管理說明',
        url: 'https://docs.alleynote.example.com/v1/system',
    ),
)]
#[OA\ExternalDocumentation(
    description: 'AlleyNote 完整文件',
    url: 'https://docs.alleynote.example.com/v1',
)]
class OpenApiSpec
{
    // 這個類別僅用於 OpenAPI 規範定義
    // 實際的 API 實作在各個 Controllers 中
}
