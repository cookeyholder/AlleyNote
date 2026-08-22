/**
 * 非同步動態載入外部腳本與樣式表工具
 */

const loadedScripts = new Map();
const loadedStylesheets = new Map();

/**
 * 動態載入 JavaScript 腳本
 * @param {string} src 腳本 URL
 * @param {Object} [options] 選項 (integrity, crossorigin)
 * @returns {Promise<void>}
 */
export function loadScript(src, options = {}) {
  if (loadedScripts.has(src)) {
    return loadedScripts.get(src);
  }

  const promise = new Promise((resolve, reject) => {
    // 檢查 DOM 是否已有該腳本
    const existing = document.querySelector(`script[src="${src}"]`);
    if (existing) {
      resolve();
      return;
    }

    const script = document.createElement("script");
    script.src = src;
    if (options.integrity) {
      script.integrity = options.integrity;
    }
    if (options.crossOrigin || options.crossorigin) {
      script.crossOrigin = options.crossOrigin || options.crossorigin;
    }

    script.onload = () => resolve();
    script.onerror = (err) => reject(new Error(`載入腳本失敗: ${src}`));

    document.head.appendChild(script);
  });

  loadedScripts.set(src, promise);
  return promise;
}

/**
 * 動態載入 CSS 樣式表
 * @param {string} href 樣式表 URL
 * @param {Object} [options] 選項
 * @returns {Promise<void>}
 */
export function loadStylesheet(href, options = {}) {
  if (loadedStylesheets.has(href)) {
    return loadedStylesheets.get(href);
  }

  const promise = new Promise((resolve, reject) => {
    const existing = document.querySelector(`link[href="${href}"]`);
    if (existing) {
      resolve();
      return;
    }

    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = href;
    if (options.integrity) {
      link.integrity = options.integrity;
    }
    if (options.crossOrigin || options.crossorigin) {
      link.crossOrigin = options.crossOrigin || options.crossorigin;
    }

    link.onload = () => resolve();
    link.onerror = (err) => reject(new Error(`載入樣式表失敗: ${href}`));

    document.head.appendChild(link);
  });

  loadedStylesheets.set(href, promise);
  return promise;
}

/**
 * 依需求延遲載入 CKEditor 5
 * @returns {Promise<void>}
 */
export async function loadCKEditor() {
  if (typeof CKEDITOR !== "undefined") {
    return;
  }

  await Promise.all([
    loadStylesheet("https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css"),
    loadScript("https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js", {
      integrity:
        "sha384-dGdZf/aoHt7asPteBMUYsi3u4YubJS1UyBECddW3/xTiZAbPowYtrb42ySxNK5G7",
      crossorigin: "anonymous",
    }),
  ]);
}

/**
 * 依需求延遲載入 Chart.js
 * @returns {Promise<void>}
 */
export async function loadChartJs() {
  if (typeof Chart !== "undefined") {
    return;
  }

  await loadScript(
    "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js",
    {
      integrity:
        "sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g",
      crossorigin: "anonymous",
    },
  );
}
