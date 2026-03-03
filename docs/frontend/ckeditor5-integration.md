# CKEditor 5 富文本編輯器整合指南

## 概述

本專案已成功整合 CKEditor 5 富文本編輯器，用於提供強大的內容編輯功能。目前已應用於「系統設定」頁面的「頁腳描述」欄位。

## 技術架構

### CDN 引入方式

在 `frontend/index.html` 中引入 CKEditor 5：

```html
<!-- CKEditor 5 -->
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
<script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js"></script>
```

### 編輯器組件

位置：`frontend/js/components/RichTextEditor.js`

這個組件提供了以下功能：

#### 主要 API

1. **initRichTextEditor(elementId, options)**
   - 初始化富文本編輯器
   - 參數：
     - `elementId`: 編輯器容器的 DOM ID
     - `options`: 配置選項
       - `placeholder`: 佔位符文字
       - `initialValue`: 初始 HTML 內容
       - `onChange`: 內容變更回調函數
       - `onWordCountUpdate`: 字數統計更新回調
       - `config`: CKEditor 自定義配置

2. **getRichTextEditorContent(elementId)**
   - 獲取編輯器的 HTML 內容

3. **setRichTextEditorContent(elementId, content)**
   - 設置編輯器內容

4. **destroyRichTextEditor(elementId)**
   - 銷毀編輯器實例

5. **getRichTextEditorInstance(elementId)**
   - 獲取原始編輯器實例

6. **destroyAllRichTextEditors()**
   - 銷毀所有編輯器實例

## 使用範例

### 基本使用

```javascript
import { initRichTextEditor, getRichTextEditorContent } from '../../components/RichTextEditor.js';

// 初始化編輯器
const editor = await initRichTextEditor('my-editor', {
  placeholder: '請輸入內容...',
  initialValue: '<p>初始內容</p>',
  onChange: (data) => {
    console.log('內容已變更:', data);
  }
});

// 獲取內容
const content = getRichTextEditorContent('my-editor');
console.log('編輯器內容:', content);
```

### 自定義工具列

```javascript
const editor = await initRichTextEditor('my-editor', {
  config: {
    toolbar: {
      items: [
        'heading',
        '|',
        'bold',
        'italic',
        'underline',
        '|',
        'link',
        'bulletedList',
        'numberedList'
      ]
    }
  }
});
```

### 在系統設定中的應用

在 `frontend/js/pages/admin/settings.js` 中的實際應用：

```javascript
// 初始化頁腳描述編輯器
async function initFooterDescriptionEditor() {
  footerDescriptionEditor = await initRichTextEditor('footer-description-editor', {
    placeholder: '請輸入頁腳描述...',
    initialValue: originalSettings.footer_description || '預設描述',
    config: {
      toolbar: {
        items: [
          'heading',
          '|',
          'bold',
          'italic',
          'underline',
          '|',
          'fontSize',
          'fontColor',
          '|',
          'alignment',
          '|',
          'link',
          'bulletedList',
          'numberedList',
          '|',
          'removeFormat',
          '|',
          'undo',
          'redo'
        ]
      }
    }
  });
}

// 保存時獲取內容
const footerDescription = getRichTextEditorContent('footer-description-editor');
settings.footer_description = footerDescription;
```

## 樣式客製化

在 `frontend/css/main.css` 中定義了編輯器樣式：

```css
/* 頁腳描述編輯器樣式 */
#footer-description-editor .ck-editor__editable {
    min-height: 200px;
    max-height: 400px;
}

/* CKEditor 內容樣式 */
.ck-content {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    line-height: 1.6;
}
```

## 功能特性

### 預設支援的功能

- **文字樣式**: 粗體、斜體、底線、刪除線、程式碼
- **標題**: H1-H4
- **列表**: 有序列表、無序列表、待辦事項列表
- **對齊**: 左對齊、置中、右對齊、兩端對齊
- **連結**: 插入和編輯超連結
- **表格**: 插入和編輯表格
- **字體**: 字體大小、字體顏色、背景顏色、字體家族
- **區塊元素**: 引用區塊、程式碼區塊、水平線
- **進階功能**: 
  - HTML 支援
  - 原始碼編輯
  - 特殊字元
  - 媒體嵌入
  - 圖片處理
  - 自動格式化
  - 文字轉換
  - 字數統計

### 中文支援

編輯器已配置為支援繁體中文界面：

```javascript
{
  language: 'zh',
  heading: {
    options: [
      { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
      { model: 'heading1', view: 'h1', title: '標題 1', class: 'ck-heading_heading1' },
      { model: 'heading2', view: 'h2', title: '標題 2', class: 'ck-heading_heading2' },
      // ...
    ]
  }
}
```

## HTML 渲染

編輯器生成的 HTML 內容可以直接在前端渲染，並使用 `prose-modern` 類別來應用樣式：

```html
<div class="prose-modern" v-html="footerDescription"></div>
```

## 安全性考量

1. **HTML 清理**: 雖然 CKEditor 5 有內建的 HTML 清理機制，但仍建議在後端進行額外的 HTML 清理
2. **XSS 防護**: 使用 DOMPurify 或類似工具在渲染前清理 HTML
3. **內容驗證**: 在保存前驗證內容長度和格式

## 測試

測試文件位於 `frontend/test-ckeditor.html`，可以用來測試編輯器的基本功能。

訪問方式：
```
http://localhost:3000/test-ckeditor.html
```

## 未來改進

1. **圖片上傳**: 整合圖片上傳功能
2. **協作編輯**: 考慮加入即時協作功能
3. **版本控制**: 實作內容版本歷史
4. **模板系統**: 提供常用內容模板
5. **自定義插件**: 開發專案特定的編輯器插件

## 相關資源

- [CKEditor 5 官方文檔](https://ckeditor.com/docs/ckeditor5/latest/)
- [CKEditor 5 API 文檔](https://ckeditor.com/docs/ckeditor5/latest/api/)
- [CKEditor 5 GitHub](https://github.com/ckeditor/ckeditor5)

## 維護注意事項

1. **版本更新**: 定期檢查並更新 CKEditor 5 版本
2. **配置管理**: 將編輯器配置集中管理，避免重複代碼
3. **性能優化**: 對於多個編輯器實例，考慮使用懶加載
4. **錯誤處理**: 確保編輯器初始化失敗時有適當的錯誤提示
