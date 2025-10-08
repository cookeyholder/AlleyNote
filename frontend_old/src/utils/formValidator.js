import validator from 'validator';

/**
 * 表單驗證器
 */
export class FormValidator {
  constructor(form, rules) {
    this.form = form;
    this.rules = rules;
    this.errors = {};
  }

  /**
   * 驗證表單
   */
  validate() {
    this.errors = {};
    let isValid = true;

    for (const [field, fieldRules] of Object.entries(this.rules)) {
      const input = this.form.elements[field];
      if (!input) continue;

      const value = input.value;
      const fieldErrors = [];

      for (const rule of fieldRules) {
        const error = this._validateRule(value, rule, field);
        if (error) {
          fieldErrors.push(error);
          isValid = false;
        }
      }

      if (fieldErrors.length > 0) {
        this.errors[field] = fieldErrors;
      }
    }

    return isValid;
  }

  /**
   * 驗證單一規則
   * @private
   */
  _validateRule(value, rule, field) {
    const { type, message, params } = rule;

    switch (type) {
      case 'required':
        if (!value || value.trim() === '') {
          return message || `${field} 為必填欄位`;
        }
        break;

      case 'email':
        if (value && !validator.isEmail(value)) {
          return message || '請輸入有效的電子郵件';
        }
        break;

      case 'url':
        if (value && !validator.isURL(value)) {
          return message || '請輸入有效的網址';
        }
        break;

      case 'min':
        if (value && value.length < params) {
          return message || `最少需要 ${params} 個字元`;
        }
        break;

      case 'max':
        if (value && value.length > params) {
          return message || `最多只能 ${params} 個字元`;
        }
        break;

      case 'minValue':
        if (value && Number(value) < params) {
          return message || `最小值為 ${params}`;
        }
        break;

      case 'maxValue':
        if (value && Number(value) > params) {
          return message || `最大值為 ${params}`;
        }
        break;

      case 'pattern':
        if (value && !new RegExp(params).test(value)) {
          return message || '格式不正確';
        }
        break;

      case 'custom':
        const customResult = params(value);
        if (customResult !== true) {
          return customResult || message || '驗證失敗';
        }
        break;

      default:
        console.warn(`Unknown validation type: ${type}`);
    }

    return null;
  }

  /**
   * 取得錯誤訊息
   */
  getErrors() {
    return this.errors;
  }

  /**
   * 取得特定欄位的錯誤
   */
  getError(field) {
    return this.errors[field]?.[0] || null;
  }

  /**
   * 顯示錯誤訊息
   */
  showErrors() {
    for (const [field, errors] of Object.entries(this.errors)) {
      const errorEl = document.querySelector(`[data-error-for="${field}"]`);
      if (errorEl) {
        errorEl.textContent = errors[0];
        errorEl.classList.remove('hidden');
      }

      const input = this.form.elements[field];
      if (input) {
        input.classList.add('border-red-500');
      }
    }
  }

  /**
   * 清除錯誤訊息
   */
  clearErrors() {
    document.querySelectorAll('[data-error-for]').forEach((el) => {
      el.textContent = '';
      el.classList.add('hidden');
    });

    this.form.querySelectorAll('input, textarea, select').forEach((input) => {
      input.classList.remove('border-red-500');
    });

    this.errors = {};
  }
}

/**
 * 常用驗證規則
 */
export const ValidationRules = {
  required: (message) => ({ type: 'required', message }),
  email: (message) => ({ type: 'email', message }),
  url: (message) => ({ type: 'url', message }),
  min: (length, message) => ({ type: 'min', params: length, message }),
  max: (length, message) => ({ type: 'max', params: length, message }),
  minValue: (value, message) => ({ type: 'minValue', params: value, message }),
  maxValue: (value, message) => ({ type: 'maxValue', params: value, message }),
  pattern: (regex, message) => ({ type: 'pattern', params: regex, message }),
  custom: (fn, message) => ({ type: 'custom', params: fn, message }),
};
