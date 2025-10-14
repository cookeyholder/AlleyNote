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
          'fontSize',
          'fontFamily',
          'fontColor',
          'fontBackgroundColor',
          '|',
          'bold',
          'italic',
          'underline',
          'strikethrough',
          'subscript',
          'superscript',
          'code',
          '|',
          'alignment',
          '|',
          'link',
          'bulletedList',
          'numberedList',
          'todoList',
          '|',
          'outdent',
          'indent',
          '|',
          'blockQuote',
          'insertTable',
          'mediaEmbed',
          'codeBlock',
          '|',
          'horizontalLine',
          'specialCharacters',
          '|',
          'removeFormat',
          '|',
          'sourceEditing',
          '|',
          'undo',
          'redo'
        ],
        shouldNotGroupWhenFull: true
      },
      language: 'zh',
      heading: {
        options: [
          { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
          { model: 'heading1', view: 'h1', title: '標題 1', class: 'ck-heading_heading1' },
          { model: 'heading2', view: 'h2', title: '標題 2', class: 'ck-heading_heading2' },
          { model: 'heading3', view: 'h3', title: '標題 3', class: 'ck-heading_heading3' },
          { model: 'heading4', view: 'h4', title: '標題 4', class: 'ck-heading_heading4' },
          { model: 'heading5', view: 'h5', title: '標題 5', class: 'ck-heading_heading5' },
          { model: 'heading6', view: 'h6', title: '標題 6', class: 'ck-heading_heading6' }
        ]
      },
      fontSize: {
        options: [
          'tiny',
          'small',
          'default',
          'big',
          'huge'
        ],
        supportAllValues: true
      },
      fontFamily: {
        options: [
          'default',
          'Arial, Helvetica, sans-serif',
          'Courier New, Courier, monospace',
          'Georgia, serif',
          'Lucida Sans Unicode, Lucida Grande, sans-serif',
          'Tahoma, Geneva, sans-serif',
          'Times New Roman, Times, serif',
          'Trebuchet MS, Helvetica, sans-serif',
          'Verdana, Geneva, sans-serif',
          '微軟正黑體, Microsoft JhengHei, sans-serif',
          '新細明體, PMingLiU, serif',
          '標楷體, DFKai-SB, serif'
        ],
        supportAllValues: true
      },
      fontColor: {
        columns: 5,
        documentColors: 10
      },
      fontBackgroundColor: {
        columns: 5,
        documentColors: 10
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
        },
        addTargetToExternalLinks: true
      },
      table: {
        contentToolbar: [
          'tableColumn',
          'tableRow',
          'mergeTableCells',
          'tableProperties',
          'tableCellProperties'
        ]
      },
      htmlSupport: {
        allow: [
          {
            name: /.*/,
            attributes: true,
            classes: true,
            styles: true
          }
        ]
      },
      codeBlock: {
        languages: [
          { language: 'plaintext', label: 'Plain text' },
          { language: 'c', label: 'C' },
          { language: 'cs', label: 'C#' },
          { language: 'cpp', label: 'C++' },
          { language: 'css', label: 'CSS' },
          { language: 'diff', label: 'Diff' },
          { language: 'html', label: 'HTML' },
          { language: 'java', label: 'Java' },
          { language: 'javascript', label: 'JavaScript' },
          { language: 'php', label: 'PHP' },
          { language: 'python', label: 'Python' },
          { language: 'ruby', label: 'Ruby' },
          { language: 'typescript', label: 'TypeScript' },
          { language: 'xml', label: 'XML' }
        ]
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
