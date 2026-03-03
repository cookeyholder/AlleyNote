/**
 * 表單驗證工具
 */

export class FormValidator {
    constructor(form, rules = {}) {
        this.form = form;
        this.rules = rules;
        this.errors = {};
    }

    /**
     * 驗證電子郵件
     */
    static isEmail(value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    }

    /**
     * 驗證必填欄位
     */
    static isRequired(value) {
        if (typeof value === "string") {
            return value.trim().length > 0;
        }
        return value !== null && value !== undefined;
    }

    /**
     * 驗證最小長度
     */
    static minLength(value, min) {
        return value && value.length >= min;
    }

    /**
     * 驗證最大長度
     */
    static maxLength(value, max) {
        return value && value.length <= max;
    }

    /**
     * 驗證數字
     */
    static isNumber(value) {
        return !isNaN(parseFloat(value)) && isFinite(value);
    }

    /**
     * 驗證 URL
     */
    static isUrl(value) {
        try {
            new URL(value);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * 驗證密碼強度
     */
    static isStrongPassword(value) {
        // 至少 8 個字元，包含大小寫字母和數字
        const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
        return strongPasswordRegex.test(value);
    }

    /**
     * 驗證安全密碼（進階版）
     * 包含更嚴格的安全檢查
     */
    static isSecurePassword(value, username = null, email = null) {
        // 導入 PasswordValidator（需要確保已載入）
        if (typeof PasswordValidator !== "undefined") {
            const result = PasswordValidator.validate(value, {
                username,
                email,
            });
            return result.isValid;
        }
        // Fallback 到基本驗證
        return FormValidator.isStrongPassword(value);
    }

    /**
     * 驗證表單
     */
    validate(rules = null) {
        this.errors = {};
        const formData = new FormData(this.form);
        let isValid = true;

        // 使用傳入的 rules 或 constructor 中的 rules
        const validationRules = rules || this.rules;

        // 如果沒有 rules 或 rules 不是有效的物件,直接返回 true
        if (!validationRules) {
            return true;
        }

        if (
            typeof validationRules !== "object" ||
            validationRules === null ||
            Array.isArray(validationRules)
        ) {
            return true;
        }

        for (const [field, validators] of Object.entries(validationRules)) {
            const value = formData.get(field);

            // 確保 validators 是陣列
            if (!Array.isArray(validators)) {
                continue;
            }

            for (const validator of validators) {
                const { rule, message, params } = validator;

                let valid = false;

                switch (rule) {
                    case "required":
                        valid = FormValidator.isRequired(value);
                        break;
                    case "email":
                        valid = value ? FormValidator.isEmail(value) : true;
                        break;
                    case "minLength":
                        valid = FormValidator.minLength(value, params);
                        break;
                    case "maxLength":
                        valid = FormValidator.maxLength(value, params);
                        break;
                    case "number":
                        valid = FormValidator.isNumber(value);
                        break;
                    case "url":
                        valid = value ? FormValidator.isUrl(value) : true;
                        break;
                    case "strongPassword":
                        valid = FormValidator.isStrongPassword(value);
                        break;
                    case "securePassword":
                        // params 應該是 { username, email }
                        valid = FormValidator.isSecurePassword(
                            value,
                            params?.username,
                            params?.email
                        );
                        break;
                    case "custom":
                        valid = params(value, formData);
                        break;
                    default:
                        valid = true;
                }

                if (!valid) {
                    this.errors[field] = message;
                    isValid = false;
                    break; // 停止檢查此欄位的其他規則
                }
            }
        }

        this.displayErrors();
        return isValid;
    }

    /**
     * 顯示錯誤訊息
     */
    displayErrors() {
        // 清除所有錯誤訊息
        this.form.querySelectorAll("[data-error-for]").forEach((el) => {
            el.textContent = "";
            el.classList.add("hidden");
        });

        this.form.querySelectorAll(".input-field").forEach((input) => {
            input.classList.remove("border-red-500");
        });

        // 顯示新的錯誤訊息
        for (const [field, message] of Object.entries(this.errors)) {
            const input = this.form.querySelector(`[name="${field}"]`);
            const errorEl = this.form.querySelector(
                `[data-error-for="${field}"]`
            );

            if (input) {
                input.classList.add("border-red-500");
            }

            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove("hidden");
            }
        }
    }

    /**
     * 清除錯誤訊息
     */
    clearErrors() {
        this.errors = {};
        this.displayErrors();
    }

    /**
     * 顯示錯誤訊息（別名方法）
     */
    showErrors() {
        this.displayErrors();
    }

    /**
     * 取得表單資料
     */
    getData() {
        const formData = new FormData(this.form);
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    }
}

/**
 * 驗證規則快捷方式
 */
export const ValidationRules = {
    required: (message = "此欄位為必填") => ({
        rule: "required",
        message,
    }),

    email: (message = "請輸入有效的電子郵件地址") => ({
        rule: "email",
        message,
    }),

    minLength: (min, message = `至少需要 ${min} 個字元`) => ({
        rule: "minLength",
        params: min,
        message,
    }),

    // 別名：min -> minLength
    min: (min, message = `至少需要 ${min} 個字元`) => ({
        rule: "minLength",
        params: min,
        message,
    }),

    maxLength: (max, message = `最多 ${max} 個字元`) => ({
        rule: "maxLength",
        params: max,
        message,
    }),

    // 別名：max -> maxLength
    max: (max, message = `最多 ${max} 個字元`) => ({
        rule: "maxLength",
        params: max,
        message,
    }),

    strongPassword: (
        message = "密碼至少需要 8 個字元，包含大小寫字母和數字"
    ) => ({
        rule: "strongPassword",
        message,
    }),

    securePassword: (options = {}, message = "密碼不符合安全要求") => ({
        rule: "securePassword",
        params: options,
        message,
    }),

    custom: (validator, message) => ({
        rule: "custom",
        params: validator,
        message,
    }),
};
