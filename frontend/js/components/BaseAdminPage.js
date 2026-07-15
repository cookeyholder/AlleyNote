/**
 * 管理後台頁面基底類別
 *
 * 封裝共用生命週期與工具方法，所有管理後台的 class-based 頁面皆應繼承此類別。
 */
import { escapeHtml } from "../utils/security.js";

export default class BaseAdminPage {
  constructor() {
    this.loading = false;
  }

  /**
   * 初始化頁面生命週期（模板方法模式）
   * 子類別不應覆寫此方法，應改為覆寫 loadData()
   */
  async init() {
    this.showLoading();
    try {
      await this.loadData();
    } catch (error) {
      console.error("載入頁面資料失敗:", error);
    } finally {
      this.hideLoading();
    }
    await this.render();
    this.attachEventListeners();
    this.afterRender();
  }

  /**
   * 載入頁面所需的資料
   * 子類別應覆寫此方法，不應覆寫 init()
   */
  async loadData() {
    // 由子類別實作
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
   * 渲染完成後處理（例如初始化圖表）
   * 子類別可選擇性覆寫
   */
  afterRender() {
    // 由子類別選擇性實作
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
