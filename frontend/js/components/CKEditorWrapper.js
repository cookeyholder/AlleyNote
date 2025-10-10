/**
 * CKEditor 5 包裝器
 * 用於初始化和管理 CKEditor 實例
 */

/**
 * 初始化 CKEditor
 * @param {string} elementId - 編輯器容器的 ID
 * @param {string} initialContent - 初始內容
 * @returns {Promise<Object>} CKEditor 實例
 */
export async function initCKEditor(elementId, initialContent = '') {
  // 等待 CKEditor 載入
  if (typeof ClassicEditor === 'undefined') {
    throw new Error('CKEditor 尚未載入');
  }

  try {
    const editor = await ClassicEditor.create(document.getElementById(elementId), {
      toolbar: {
        items: [
          'heading',
          '|',
          'bold',
          'italic',
          'link',
          'bulletedList',
          'numberedList',
          '|',
          'outdent',
          'indent',
          '|',
          'imageUpload',
          'blockQuote',
          'insertTable',
          'mediaEmbed',
          '|',
          'undo',
          'redo'
        ]
      },
      language: 'zh',
      heading: {
        options: [
          { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
          { model: 'heading1', view: 'h1', title: '標題 1', class: 'ck-heading_heading1' },
          { model: 'heading2', view: 'h2', title: '標題 2', class: 'ck-heading_heading2' },
          { model: 'heading3', view: 'h3', title: '標題 3', class: 'ck-heading_heading3' }
        ]
      },
      link: {
        decorators: {
          openInNewTab: {
            mode: 'manual',
            label: '在新分頁開啟',
            attributes: {
              target: '_blank',
              rel: 'noopener noreferrer'
            }
          }
        }
      },
      table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
      }
    });

    // 設定初始內容
    if (initialContent) {
      editor.setData(initialContent);
    }

    // 監聽內容變化
    editor.model.document.on('change:data', () => {
      console.log('[CKEditor] 內容已變更');
    });

    return editor;
  } catch (error) {
    console.error('[CKEditor] 初始化失敗:', error);
    throw error;
  }
}

/**
 * 銷毀 CKEditor 實例
 * @param {Object} editor - CKEditor 實例
 */
export async function destroyCKEditor(editor) {
  if (editor) {
    try {
      await editor.destroy();
      console.log('[CKEditor] 實例已銷毀');
    } catch (error) {
      console.error('[CKEditor] 銷毀失敗:', error);
    }
  }
}

/**
 * 取得編輯器內容
 * @param {Object} editor - CKEditor 實例
 * @returns {string} HTML 內容
 */
export function getEditorContent(editor) {
  if (!editor) {
    return '';
  }
  return editor.getData();
}

/**
 * 設定編輯器內容
 * @param {Object} editor - CKEditor 實例
 * @param {string} content - HTML 內容
 */
export function setEditorContent(editor, content) {
  if (editor) {
    editor.setData(content);
  }
}

/**
 * CKEditorWrapper 類別（用於向後相容）
 */
export class CKEditorWrapper {
  constructor() {
    this.editor = null;
  }

  async init(elementId, initialContent = '') {
    this.editor = await initCKEditor(elementId, initialContent);
    return this.editor;
  }

  async destroy() {
    await destroyCKEditor(this.editor);
    this.editor = null;
  }

  getData() {
    return getEditorContent(this.editor);
  }

  setData(content) {
    setEditorContent(this.editor, content);
  }
}

export default CKEditorWrapper;
