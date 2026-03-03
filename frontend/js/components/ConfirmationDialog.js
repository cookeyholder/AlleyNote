/**
 * 確認對話框組件
 * 用於刪除操作等需要用戶確認的場景
 */

import { modal } from './Modal.js';

/**
 * 顯示刪除確認對話框
 * @param {string} itemName - 要刪除的項目名稱
 * @param {Function} onConfirm - 確認後的回調函數
 * @param {Object} options - 額外選項
 * @returns {Object} Modal 實例
 */
export function confirmDelete(itemName, onConfirm, options = {}) {
  const {
    title = '確認刪除',
    message = null,
    type = '項目',
  } = options;

  const defaultMessage = `確定要刪除「${itemName}」嗎？此操作無法復原。`;
  const finalMessage = message || defaultMessage;

  return modal.confirm(
    title,
    `<div class="text-modern-700">
      <p class="mb-2">${finalMessage}</p>
      <p class="text-sm text-red-600">⚠️ 此操作無法復原</p>
    </div>`,
    onConfirm
  );
}

/**
 * 顯示一般確認對話框
 * @param {string} title - 對話框標題
 * @param {string} message - 訊息內容
 * @param {Function} onConfirm - 確認後的回調函數
 * @param {Function} onCancel - 取消後的回調函數
 * @returns {Object} Modal 實例
 */
export function confirm(title, message, onConfirm, onCancel = null) {
  return modal.confirm(title, message, onConfirm, onCancel);
}

/**
 * 顯示警告對話框
 * @param {string} title - 對話框標題
 * @param {string} message - 訊息內容
 * @param {Function} onClose - 關閉後的回調函數
 * @returns {Object} Modal 實例
 */
export function alert(title, message, onClose = null) {
  return modal.alert(title, message, onClose);
}

/**
 * 顯示批量刪除確認對話框
 * @param {number} count - 要刪除的項目數量
 * @param {Function} onConfirm - 確認後的回調函數
 * @param {Object} options - 額外選項
 * @returns {Object} Modal 實例
 */
export function confirmBatchDelete(count, onConfirm, options = {}) {
  const {
    title = '確認批量刪除',
    type = '項目',
  } = options;

  const message = `確定要刪除選中的 ${count} 個${type}嗎？此操作無法復原。`;

  return modal.confirm(
    title,
    `<div class="text-modern-700">
      <p class="mb-2">${message}</p>
      <p class="text-sm text-red-600">⚠️ 此操作無法復原</p>
    </div>`,
    onConfirm
  );
}

/**
 * 顯示放棄變更確認對話框
 * @param {Function} onConfirm - 確認後的回調函數
 * @param {Object} options - 額外選項
 * @returns {Object} Modal 實例
 */
export function confirmDiscard(onConfirm, options = {}) {
  const {
    title = '確認放棄變更',
    message = '您有未保存的變更。確定要放棄這些變更嗎？',
  } = options;

  return modal.confirm(
    title,
    `<div class="text-modern-700">
      <p class="mb-2">${message}</p>
      <p class="text-sm text-orange-600">⚠️ 未保存的變更將會遺失</p>
    </div>`,
    onConfirm
  );
}
