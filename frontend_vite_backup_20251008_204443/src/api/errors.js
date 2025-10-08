/**
 * API 錯誤類別
 */
export class APIError extends Error {
  constructor(code, message, status, details = null) {
    super(message);
    this.name = 'APIError';
    this.code = code;
    this.status = status;
    this.details = details;
  }

  /**
   * 是否為驗證錯誤
   */
  isValidationError() {
    return this.code === 'VALIDATION_ERROR' && this.details !== null;
  }

  /**
   * 是否為網路錯誤
   */
  isNetworkError() {
    return this.code === 'NETWORK_ERROR';
  }

  /**
   * 是否為認證錯誤
   */
  isAuthError() {
    return this.code === 'UNAUTHORIZED';
  }

  /**
   * 取得使用者友善的錯誤訊息
   */
  getUserMessage() {
    return this.message;
  }

  /**
   * 取得驗證錯誤欄位
   */
  getValidationErrors() {
    return this.details || {};
  }
}

/**
 * 錯誤代碼對照表
 */
export const ERROR_CODES = {
  NETWORK_ERROR: 'NETWORK_ERROR',
  TIMEOUT: 'TIMEOUT',
  UNAUTHORIZED: 'UNAUTHORIZED',
  FORBIDDEN: 'FORBIDDEN',
  NOT_FOUND: 'NOT_FOUND',
  CONFLICT: 'CONFLICT',
  VALIDATION_ERROR: 'VALIDATION_ERROR',
  SERVER_ERROR: 'SERVER_ERROR',
  RATE_LIMIT: 'RATE_LIMIT',
  UNKNOWN_ERROR: 'UNKNOWN_ERROR',
};
