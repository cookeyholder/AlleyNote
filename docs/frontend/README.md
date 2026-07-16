# 前端架構文件

本目錄收錄 AlleyNote 前端（SPA）的架構設計文件，涵蓋模組結構、設計模式與整合指南。

## 文件列表

| 編號 | 文件名稱 | 說明 |
|------|---------|------|
| 01 | [架構總覽](01-架構總覽.md) | SPA 整體架構、路由、Store、API Client、第三方整合 |
| 02 | [管理後台基底類別](02-管理後台基底類別.md) | BaseAdminPage 模板方法模式與 5 個子頁面實作 |
| 03 | [API 模組模式](03-API模組模式.md) | API Modules 的目錄結構、CRUD 模式、錯誤處理 |
| 04 | [CKEditor 整合](04-CKEditor整合.md) | CKEditor 5 富文本編輯器整合方式與元件 API |
| 05 | [通知系統](05-通知系統.md) | 前端通知系統統一入口、通道選擇規則與 Promise 契約 |

## 技術棧

- 純 ES6 模組（無建構工具，無框架）
- Navigo 8.x（SPA 路由）
- Tailwind CSS（CDN）
- CKEditor 5（富文本編輯器）
- Chart.js 4.x（統計圖表）
- DOMPurify（XSS 防護）

## 適用對象

- 前端開發者
- 需理解前端架構的全端工程師
