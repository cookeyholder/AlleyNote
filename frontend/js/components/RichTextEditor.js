/**
 * CKEditor 5 富文本編輯器組件
 */

// 編輯器實例映射
const editorInstances = new Map();

/**
 * 初始化 CKEditor 5
 * @param {string} elementId - 編輯器元素 ID
 * @param {Object} options - 配置選項
 * @returns {Promise<Object>} - 編輯器實例
 */
export async function initRichTextEditor(elementId, options = {}) {
  // 確保 CKEditor 5 已載入
  if (typeof CKEDITOR === 'undefined') {
    throw new Error('CKEditor 5 尚未載入，請先在 HTML 中引入 CKEditor 5 CDN');
  }

  const element = document.getElementById(elementId);
  if (!element) {
    throw new Error(`找不到元素 #${elementId}`);
  }

  // 如果已有實例，先銷毀
  if (editorInstances.has(elementId)) {
    await destroyRichTextEditor(elementId);
  }

  const {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Underline,
    Strikethrough,
    Code,
    Link,
    Paragraph,
    Heading,
    List,
    Alignment,
    BlockQuote,
    Font,
    Highlight,
    RemoveFormat,
    HorizontalLine,
    SpecialCharacters,
    SpecialCharactersEssentials,
    GeneralHtmlSupport,
    SourceEditing,
    Table,
    TableToolbar,
    TableProperties,
    TableCellProperties,
    Image,
    ImageToolbar,
    ImageCaption,
    ImageStyle,
    ImageResize,
    LinkImage,
    AutoImage,
    MediaEmbed,
    Indent,
    IndentBlock,
    CodeBlock,
    TodoList,
    Autoformat,
    TextTransformation,
    WordCount
  } = CKEDITOR;

  // 預設配置
  const defaultConfig = {
    plugins: [
      Essentials,
      Bold,
      Italic,
      Underline,
      Strikethrough,
      Code,
      Link,
      Paragraph,
      Heading,
      List,
      Alignment,
      BlockQuote,
      Font,
      Highlight,
      RemoveFormat,
      HorizontalLine,
      SpecialCharacters,
      SpecialCharactersEssentials,
      GeneralHtmlSupport,
      SourceEditing,
      Table,
      TableToolbar,
      TableProperties,
      TableCellProperties,
      Image,
      ImageToolbar,
      ImageCaption,
      ImageStyle,
      ImageResize,
      LinkImage,
      AutoImage,
      MediaEmbed,
      Indent,
      IndentBlock,
      CodeBlock,
      TodoList,
      Autoformat,
      TextTransformation,
      WordCount
    ],
    toolbar: {
      items: [
        'heading',
        '|',
        'bold',
        'italic',
        'underline',
        'strikethrough',
        '|',
        'fontSize',
        'fontFamily',
        'fontColor',
        'fontBackgroundColor',
        '|',
        'alignment',
        '|',
        'numberedList',
        'bulletedList',
        'todoList',
        '|',
        'outdent',
        'indent',
        '|',
        'link',
        'blockQuote',
        'insertTable',
        'mediaEmbed',
        '|',
        'code',
        'codeBlock',
        '|',
        'highlight',
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
    heading: {
      options: [
        { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
        { model: 'heading1', view: 'h1', title: '標題 1', class: 'ck-heading_heading1' },
        { model: 'heading2', view: 'h2', title: '標題 2', class: 'ck-heading_heading2' },
        { model: 'heading3', view: 'h3', title: '標題 3', class: 'ck-heading_heading3' },
        { model: 'heading4', view: 'h4', title: '標題 4', class: 'ck-heading_heading4' }
      ]
    },
    fontSize: {
      options: [10, 12, 14, 'default', 18, 20, 24, 28, 32, 36],
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
        '新細明體, PMingLiU, serif'
      ],
      supportAllValues: true
    },
    table: {
      contentToolbar: [
        'tableColumn',
        'tableRow',
        'mergeTableCells',
        'tableCellProperties',
        'tableProperties'
      ]
    },
    image: {
      toolbar: [
        'imageTextAlternative',
        'toggleImageCaption',
        '|',
        'imageStyle:inline',
        'imageStyle:block',
        'imageStyle:side',
        '|',
        'linkImage'
      ],
      resizeOptions: [
        {
          name: 'resizeImage:original',
          label: '原始大小',
          value: null
        },
        {
          name: 'resizeImage:50',
          label: '50%',
          value: '50'
        },
        {
          name: 'resizeImage:75',
          label: '75%',
          value: '75'
        }
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
    wordCount: {
      onUpdate: stats => {
        if (options.onWordCountUpdate) {
          options.onWordCountUpdate(stats);
        }
      }
    },
    placeholder: options.placeholder || '請輸入內容...',
    language: 'zh'
  };

  // 合併用戶配置
  const config = { ...defaultConfig, ...options.config };

  try {
    // 創建編輯器實例
    const editor = await ClassicEditor.create(element, config);

    // 儲存實例
    editorInstances.set(elementId, editor);

    // 如果有初始值，設置內容
    if (options.initialValue) {
      editor.setData(options.initialValue);
    }

    // 監聽變更事件
    if (options.onChange) {
      editor.model.document.on('change:data', () => {
        const data = editor.getData();
        options.onChange(data);
      });
    }

    return editor;
  } catch (error) {
    console.error('初始化編輯器失敗:', error);
    throw error;
  }
}

/**
 * 銷毀編輯器實例
 * @param {string} elementId - 編輯器元素 ID
 */
export async function destroyRichTextEditor(elementId) {
  const editor = editorInstances.get(elementId);
  if (editor) {
    await editor.destroy();
    editorInstances.delete(elementId);
  }
}

/**
 * 獲取編輯器內容
 * @param {string} elementId - 編輯器元素 ID
 * @returns {string|null} - HTML 內容
 */
export function getRichTextEditorContent(elementId) {
  const editor = editorInstances.get(elementId);
  return editor ? editor.getData() : null;
}

/**
 * 設置編輯器內容
 * @param {string} elementId - 編輯器元素 ID
 * @param {string} content - HTML 內容
 */
export function setRichTextEditorContent(elementId, content) {
  const editor = editorInstances.get(elementId);
  if (editor) {
    editor.setData(content);
  }
}

/**
 * 獲取編輯器實例
 * @param {string} elementId - 編輯器元素 ID
 * @returns {Object|null} - 編輯器實例
 */
export function getRichTextEditorInstance(elementId) {
  return editorInstances.get(elementId) || null;
}

/**
 * 清空所有編輯器實例
 */
export async function destroyAllRichTextEditors() {
  const promises = Array.from(editorInstances.keys()).map(id => destroyRichTextEditor(id));
  await Promise.all(promises);
}
