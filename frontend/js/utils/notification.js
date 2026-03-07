import { modal } from "../components/Modal.js";
import { toast } from "./toast.js";
import { banner } from "./banner.js";
import { inlineErrors } from "./inlineErrors.js";

class NotificationService {
  constructor() {
    this.toast = {
      success: (message, duration) => toast.success(message, duration),
      error: (message, duration) => toast.error(message, duration),
      warning: (message, duration) => toast.warning(message, duration),
      info: (message, duration) => toast.info(message, duration),
      show: (message, type, duration) => toast.show(message, type, duration),
      clearAll: () => toast.clearAll(),
    };

    this.banner = {
      show: (message, type, options) => banner.show(message, type, options),
      hide: () => banner.hide(),
    };

    this.inline = {
      show: (target, message, options) =>
        inlineErrors.show(target, message, options),
      clear: (target, options) => inlineErrors.clear(target, options),
      clearAll: (container, selector) =>
        inlineErrors.clearAll(container, selector),
    };
  }

  success(message, duration) {
    return this.toast.success(message, duration);
  }

  error(message, duration) {
    return this.toast.error(message, duration);
  }

  warning(message, duration) {
    return this.toast.warning(message, duration);
  }

  info(message, duration) {
    return this.toast.info(message, duration);
  }

  confirm(options) {
    return modal.confirmPromise(options);
  }

  notice(options) {
    return modal.noticePromise(options);
  }

  async confirmDelete(itemName, options = {}) {
    const {
      title = "確認刪除",
      message = `確定要刪除「${itemName}」嗎？此操作無法復原。`,
      confirmText = "確認刪除",
      cancelText = "保留",
      dangerText = "此操作無法復原",
    } = options;

    return this.confirm({
      title,
      message: `${message}\n\n${dangerText}`,
      confirmText,
      cancelText,
      tone: "danger",
    });
  }

  async confirmDiscard(options = {}) {
    const {
      title = "確認放棄變更",
      message = "您有未保存的變更。確定要放棄這些變更嗎？",
      confirmText = "放棄變更",
      cancelText = "繼續編輯",
    } = options;

    return this.confirm({
      title,
      message: `${message}\n\n未保存的變更將會遺失`,
      confirmText,
      cancelText,
      tone: "warning",
    });
  }
}

export const notification = new NotificationService();
