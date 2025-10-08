/**
 * 圖片懶加載工具
 * 使用 Intersection Observer API 實作圖片延遲載入
 * 
 * @author AlleyNote Team
 * @version 1.0.0
 */

/**
 * LazyLoad 類別
 * 管理圖片的延遲載入功能
 */
export class LazyLoad {
  constructor(options = {}) {
    this.options = {
      root: null,
      rootMargin: '50px',
      threshold: 0.01,
      loadingClass: 'lazy-loading',
      loadedClass: 'lazy-loaded',
      errorClass: 'lazy-error',
      placeholder: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect fill="%23f3f4f6" width="400" height="300"/%3E%3C/svg%3E',
      ...options
    };

    this.observer = null;
    this.images = new Set();
    this.init();
  }

  /**
   * 初始化 Intersection Observer
   */
  init() {
    if (!('IntersectionObserver' in window)) {
      // 不支援 IntersectionObserver，直接載入所有圖片
      console.warn('IntersectionObserver not supported, loading all images immediately');
      this.loadAllImages();
      return;
    }

    this.observer = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.loadImage(entry.target);
          }
        });
      },
      {
        root: this.options.root,
        rootMargin: this.options.rootMargin,
        threshold: this.options.threshold
      }
    );
  }

  /**
   * 觀察圖片元素
   * @param {HTMLImageElement|string} target - 圖片元素或選擇器
   */
  observe(target) {
    let elements = [];

    if (typeof target === 'string') {
      elements = Array.from(document.querySelectorAll(target));
    } else if (target instanceof HTMLImageElement) {
      elements = [target];
    } else if (target instanceof NodeList || Array.isArray(target)) {
      elements = Array.from(target);
    }

    elements.forEach(img => {
      if (this.images.has(img)) return;

      // 設定 data-src 屬性
      if (!img.dataset.src && img.src) {
        img.dataset.src = img.src;
      }

      // 設定 placeholder
      if (img.dataset.src && img.src !== this.options.placeholder) {
        img.src = this.options.placeholder;
      }

      // 加入到觀察列表
      this.images.add(img);
      
      if (this.observer) {
        this.observer.observe(img);
      }
    });
  }

  /**
   * 停止觀察圖片元素
   * @param {HTMLImageElement} img - 圖片元素
   */
  unobserve(img) {
    if (this.observer && this.images.has(img)) {
      this.observer.unobserve(img);
      this.images.delete(img);
    }
  }

  /**
   * 載入圖片
   * @param {HTMLImageElement} img - 圖片元素
   */
  loadImage(img) {
    const src = img.dataset.src || img.src;
    
    if (!src || src === this.options.placeholder) {
      this.unobserve(img);
      return;
    }

    // 加入載入中樣式
    img.classList.add(this.options.loadingClass);

    // 建立臨時圖片物件來預載入
    const tempImage = new Image();
    
    tempImage.onload = () => {
      // 設定實際圖片
      img.src = src;
      
      // 如果有 srcset，也設定它
      if (img.dataset.srcset) {
        img.srcset = img.dataset.srcset;
      }

      // 移除載入中樣式，加入載入完成樣式
      img.classList.remove(this.options.loadingClass);
      img.classList.add(this.options.loadedClass);

      // 觸發自訂事件
      img.dispatchEvent(new CustomEvent('lazyloaded', { 
        detail: { src } 
      }));

      // 停止觀察
      this.unobserve(img);
    };

    tempImage.onerror = () => {
      // 載入失敗
      img.classList.remove(this.options.loadingClass);
      img.classList.add(this.options.errorClass);

      // 觸發錯誤事件
      img.dispatchEvent(new CustomEvent('lazyerror', { 
        detail: { src } 
      }));

      // 停止觀察
      this.unobserve(img);
    };

    // 開始載入
    tempImage.src = src;
  }

  /**
   * 載入所有圖片（用於不支援 IntersectionObserver 的瀏覽器）
   */
  loadAllImages() {
    this.images.forEach(img => {
      this.loadImage(img);
    });
  }

  /**
   * 強制載入特定圖片
   * @param {HTMLImageElement|string} target - 圖片元素或選擇器
   */
  load(target) {
    let elements = [];

    if (typeof target === 'string') {
      elements = Array.from(document.querySelectorAll(target));
    } else if (target instanceof HTMLImageElement) {
      elements = [target];
    }

    elements.forEach(img => {
      if (this.images.has(img)) {
        this.loadImage(img);
      }
    });
  }

  /**
   * 重新檢查所有圖片
   * 用於動態新增內容後
   */
  update() {
    // 取消所有觀察
    this.images.forEach(img => {
      if (this.observer) {
        this.observer.unobserve(img);
      }
    });
    
    // 清空列表
    this.images.clear();

    // 重新掃描並觀察
    this.observe('img[data-src]');
  }

  /**
   * 銷毀 LazyLoad 實例
   */
  destroy() {
    if (this.observer) {
      this.observer.disconnect();
      this.observer = null;
    }
    
    this.images.clear();
  }
}

/**
 * 全域 LazyLoad 實例
 */
let globalLazyLoad = null;

/**
 * 初始化全域懶加載
 * @param {Object} options - 配置選項
 * @returns {LazyLoad} LazyLoad 實例
 */
export function initLazyLoad(options = {}) {
  if (!globalLazyLoad) {
    globalLazyLoad = new LazyLoad(options);
  }
  return globalLazyLoad;
}

/**
 * 取得全域 LazyLoad 實例
 * @returns {LazyLoad} LazyLoad 實例
 */
export function getLazyLoad() {
  if (!globalLazyLoad) {
    globalLazyLoad = initLazyLoad();
  }
  return globalLazyLoad;
}

/**
 * 懶加載工具函式
 * 快速設定懶加載
 * @param {string} selector - 圖片選擇器
 * @param {Object} options - 配置選項
 */
export function lazyLoad(selector = 'img[data-src]', options = {}) {
  const instance = getLazyLoad();
  instance.observe(selector);
  return instance;
}

/**
 * 為背景圖片設定懶加載
 * @param {string} selector - 元素選擇器
 * @param {Object} options - 配置選項
 */
export function lazyLoadBackground(selector, options = {}) {
  const defaultOptions = {
    root: null,
    rootMargin: '50px',
    threshold: 0.01,
    ...options
  };

  if (!('IntersectionObserver' in window)) {
    // 不支援，直接載入
    document.querySelectorAll(selector).forEach(el => {
      const bgSrc = el.dataset.bg;
      if (bgSrc) {
        el.style.backgroundImage = `url(${bgSrc})`;
      }
    });
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const bgSrc = el.dataset.bg;
          
          if (bgSrc) {
            // 預載入背景圖片
            const tempImg = new Image();
            tempImg.onload = () => {
              el.style.backgroundImage = `url(${bgSrc})`;
              el.classList.add('bg-loaded');
              observer.unobserve(el);
            };
            tempImg.src = bgSrc;
          }
        }
      });
    },
    defaultOptions
  );

  document.querySelectorAll(selector).forEach(el => {
    observer.observe(el);
  });
}

// 預設匯出
export default {
  LazyLoad,
  initLazyLoad,
  getLazyLoad,
  lazyLoad,
  lazyLoadBackground
};
