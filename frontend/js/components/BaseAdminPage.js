/**
 * 管理後台頁面基底類別
 *
 * 封裝共用生命週期與工具方法，所有管理後台的 class-based 頁面皆應繼承此類別。
 */
import { escapeHtml } from "../utils/security.js";

export default class BaseAdminPage {
  constructor() {
    this.items = [];
    this.loading = false;
  }

  /**
   * 初始化頁面生命週期
   * 子類別可覆寫此方法，並在必要時呼叫 super.init()
   */
  async init() {
    await this.render();
    this.attachEventListeners();
  }

  /**
   * 渲染頁面內容
   * 子類別必須覆寫此方法
   */
  async render() {
    // 由子類別實作
  }

  /**
   * 綁定頁面事件監聽器
   * 子類別必須覆寫此方法
   */
  attachEventListeners() {
    // 由子類別實作
  }

  /**
   * 顯示載入中狀態
   */
  showLoading() {
    this.loading = true;
  }

  /**
   * 隱藏載入中狀態
   */
  hideLoading() {
    this.loading = false;
  }

  /**
   * HTML 跳脫，防止 XSS 攻擊
   * @param {string} str - 需要跳脫的文字
   * @returns {string} 跳脫後的文字
   */
  escapeHtml(str) {
    return escapeHtml(str);
  }
}
