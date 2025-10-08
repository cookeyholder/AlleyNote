/**
 * 表單驗證工具
 */

export const validator = {
    required(value, fieldName = '此欄位') {
        if (!value || (typeof value === 'string' && value.trim() === '')) {
            return `${fieldName}為必填`;
        }
        return null;
    },

    email(value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (value && !emailRegex.test(value)) {
            return '請輸入有效的電子郵件地址';
        }
        return null;
    },

    minLength(value, min, fieldName = '此欄位') {
        if (value && value.length < min) {
            return `${fieldName}至少需要 ${min} 個字元`;
        }
        return null;
    },

    maxLength(value, max, fieldName = '此欄位') {
        if (value && value.length > max) {
            return `${fieldName}最多 ${max} 個字元`;
        }
        return null;
    },

    match(value, compareValue, fieldName = '此欄位') {
        if (value !== compareValue) {
            return `${fieldName}不匹配`;
        }
        return null;
    },

    validateForm(formData, rules) {
        const errors = {};
        let isValid = true;

        Object.keys(rules).forEach(field => {
            const fieldRules = rules[field];
            const value = formData[field];
            
            for (const rule of fieldRules) {
                const error = rule(value);
                if (error) {
                    errors[field] = error;
                    isValid = false;
                    break;
                }
            }
        });

        return { isValid, errors };
    },

    displayErrors(errors) {
        Object.keys(errors).forEach(field => {
            const errorElement = document.getElementById(`${field}-error`);
            if (errorElement) {
                errorElement.textContent = errors[field];
                errorElement.classList.remove('hidden');
            }

            const inputElement = document.getElementById(field);
            if (inputElement) {
                inputElement.classList.add('border-red-500');
            }
        });
    },

    clearErrors(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const errorElements = form.querySelectorAll('.form-error');
        errorElements.forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });

        const inputs = form.querySelectorAll('.form-input, .form-textarea, .form-select');
        inputs.forEach(input => {
            input.classList.remove('border-red-500');
        });
    }
};
