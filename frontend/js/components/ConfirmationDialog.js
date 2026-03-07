/**
 * 確認對話框組件
 * 用於刪除操作等需要用戶確認的場景
 */

import { notification } from "../utils/notification.js";

/**
 * 顯示刪除確認對話框
 * @param {string} itemName - 要刪除的項目名稱
 * @param {Function|Object|null} onConfirmOrOptions - 舊版確認回調或新式選項
 * @param {Object} options - 舊版回調模式的額外選項
 * @returns {Promise<boolean|*>} Promise 化的確認結果
 */
export function confirmDelete(
  itemName,
  onConfirmOrOptions = null,
  options = {},
) {
  const isLegacyCallback = typeof onConfirmOrOptions === "function";
  const resolvedOptions = isLegacyCallback ? options : onConfirmOrOptions || {};
  const { title = "確認刪除", message = null } = resolvedOptions;

  const defaultMessage = `確定要刪除「${itemName}」嗎？此操作無法復原。`;
  const finalMessage = message || defaultMessage;

  if (isLegacyCallback) {
    return notification
      .confirm({
        title,
        message: `${finalMessage}\n\n此操作無法復原`,
        confirmText: "確認刪除",
        cancelText: "保留",
        tone: "danger",
      })
      .then((confirmed) => {
        if (confirmed) {
          return onConfirmOrOptions();
        }

        return false;
      });
  }

  return notification.confirmDelete(itemName, {
    title,
    message: finalMessage,
  });
}

/**
 * 顯示一般確認對話框
 * @param {string} title - 對話框標題
 * @param {string} message - 訊息內容
 * @param {Function} onConfirm - 確認後的回調函數
 * @param {Function} onCancel - 取消後的回調函數
 * @returns {Promise<*>} Promise 化的確認流程
 */
export function confirm(title, message, onConfirm, onCancel = null) {
  return notification.confirm({ title, message }).then((confirmed) => {
    if (confirmed) {
      return onConfirm?.();
    }

    return onCancel?.();
  });
}

/**
 * 顯示警告對話框
 * @param {string} title - 對話框標題
 * @param {string} message - 訊息內容
 * @param {Function} onClose - 關閉後的回調函數
 * @returns {Promise<void|*>} Promise 化的告知流程
 */
export function alert(title, message, onClose = null) {
  return notification.notice({ title, message }).then(() => onClose?.());
}

/**
 * 顯示批量刪除確認對話框
 * @param {number} count - 要刪除的項目數量
 * @param {Function|Object|null} onConfirmOrOptions - 舊版確認回調或新式選項
 * @param {Object} options - 舊版回調模式的額外選項
 * @returns {Promise<boolean|*>} Promise 化的確認結果
 */
export function confirmBatchDelete(
  count,
  onConfirmOrOptions = null,
  options = {},
) {
  const isLegacyCallback = typeof onConfirmOrOptions === "function";
  const resolvedOptions = isLegacyCallback ? options : onConfirmOrOptions || {};
  const { title = "確認批量刪除", type = "項目" } = resolvedOptions;

  const message = `確定要刪除選中的 ${count} 個${type}嗎？此操作無法復原。`;

  if (isLegacyCallback) {
    return notification
      .confirm({
        title,
        message: `${message}\n\n此操作無法復原`,
        confirmText: "確認刪除",
        cancelText: "保留",
        tone: "danger",
      })
      .then((confirmed) => {
        if (confirmed) {
          return onConfirmOrOptions();
        }

        return false;
      });
  }

  return notification.confirm({
    title,
    message: `${message}\n\n此操作無法復原`,
    confirmText: "確認刪除",
    cancelText: "保留",
    tone: "danger",
  });
}

/**
 * 顯示放棄變更確認對話框
 * @param {Function|Object|null} onConfirmOrOptions - 舊版確認回調或新式選項
 * @param {Object} options - 舊版回調模式的額外選項
 * @returns {Promise<boolean|*>} Promise 化的確認結果
 */
export function confirmDiscard(onConfirmOrOptions = null, options = {}) {
  const isLegacyCallback = typeof onConfirmOrOptions === "function";
  const resolvedOptions = isLegacyCallback ? options : onConfirmOrOptions || {};
  const {
    title = "確認放棄變更",
    message = "您有未保存的變更。確定要放棄這些變更嗎？",
  } = resolvedOptions;

  if (isLegacyCallback) {
    return notification
      .confirm({
        title,
        message: `${message}\n\n未保存的變更將會遺失`,
        confirmText: "放棄變更",
        cancelText: "繼續編輯",
        tone: "warning",
      })
      .then((confirmed) => {
        if (confirmed) {
          return onConfirmOrOptions();
        }

        return false;
      });
  }

  return notification.confirmDiscard({ title, message });
}
