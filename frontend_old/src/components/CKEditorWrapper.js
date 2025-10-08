import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { API_CONFIG } from '../api/config.js';
import { tokenManager } from '../utils/tokenManager.js';

/**
 * CKEditor 5 包裝器組件
 */
export class CKEditorWrapper {
  constructor(elementId, options = {}) {
    this.elementId = elementId;
    this.editor = null;
    this.options = {
      uploadUrl: options.uploadUrl || `${API_CONFIG.baseURL}/attachments/upload`,
      onChange: options.onChange || null,
      initialData: options.initialData || '',
      placeholder: options.placeholder || '輸入文章內容...',
      ...options,
    };
  }

  /**
   * 初始化編輯器
   */
  async init() {
    const element = document.getElementById(this.elementId);
    if (!element) {
      throw new Error(`Element with id "${this.elementId}" not found`);
    }

    try {
      this.editor = await ClassicEditor.create(element, {
        placeholder: this.options.placeholder,
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
            'blockQuote',
            'insertTable',
            '|',
            'imageUpload',
            'mediaEmbed',
            '|',
            'undo',
            'redo',
          ],
        },
        image: {
          toolbar: [
            'imageTextAlternative',
            'imageStyle:inline',
            'imageStyle:block',
            'imageStyle:side',
            '|',
            'linkImage',
          ],
        },
        table: {
          contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
        },
      });

      // 設定初始內容
      if (this.options.initialData) {
        this.editor.setData(this.options.initialData);
      }

      // 配置上傳適配器
      this._configureUploadAdapter();

      // 監聽內容變更
      if (this.options.onChange) {
        this.editor.model.document.on('change:data', () => {
          this.options.onChange(this.getData());
        });
      }

      return this.editor;
    } catch (error) {
      console.error('CKEditor initialization failed:', error);
      throw error;
    }
  }

  /**
   * 配置圖片上傳適配器
   * @private
   */
  _configureUploadAdapter() {
    this.editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
      return new UploadAdapter(loader, this.options.uploadUrl);
    };
  }

  /**
   * 取得編輯器內容
   */
  getData() {
    return this.editor ? this.editor.getData() : '';
  }

  /**
   * 設定編輯器內容
   */
  setData(data) {
    if (this.editor) {
      this.editor.setData(data);
    }
  }

  /**
   * 銷毀編輯器
   */
  destroy() {
    if (this.editor) {
      this.editor.destroy().catch((error) => {
        console.error('CKEditor destroy failed:', error);
      });
      this.editor = null;
    }
  }
}

/**
 * 圖片上傳適配器
 */
class UploadAdapter {
  constructor(loader, uploadUrl) {
    this.loader = loader;
    this.uploadUrl = uploadUrl;
  }

  /**
   * 開始上傳
   */
  upload() {
    return this.loader.file.then(
      (file) =>
        new Promise((resolve, reject) => {
          this._initRequest();
          this._initListeners(resolve, reject, file);
          this._sendRequest(file);
        })
    );
  }

  /**
   * 中止上傳
   */
  abort() {
    if (this.xhr) {
      this.xhr.abort();
    }
  }

  /**
   * 初始化 XMLHttpRequest
   * @private
   */
  _initRequest() {
    const xhr = (this.xhr = new XMLHttpRequest());

    xhr.open('POST', this.uploadUrl, true);
    xhr.responseType = 'json';

    // 加入認證標頭
    const token = tokenManager.getToken();
    if (token) {
      xhr.setRequestHeader('Authorization', `Bearer ${token}`);
    }
  }

  /**
   * 初始化事件監聽器
   * @private
   */
  _initListeners(resolve, reject, file) {
    const xhr = this.xhr;
    const loader = this.loader;
    const genericErrorText = `無法上傳檔案: ${file.name}`;

    xhr.addEventListener('error', () => reject(genericErrorText));
    xhr.addEventListener('abort', () => reject());
    xhr.addEventListener('load', () => {
      const response = xhr.response;

      if (!response || response.error) {
        return reject(
          response && response.error ? response.error.message : genericErrorText
        );
      }

      resolve({
        default: response.url,
      });
    });

    if (xhr.upload) {
      xhr.upload.addEventListener('progress', (evt) => {
        if (evt.lengthComputable) {
          loader.uploadTotal = evt.total;
          loader.uploaded = evt.loaded;
        }
      });
    }
  }

  /**
   * 發送請求
   * @private
   */
  _sendRequest(file) {
    const data = new FormData();
    data.append('upload', file);
    this.xhr.send(data);
  }
}
