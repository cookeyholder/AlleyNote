/**
 * 密碼驗證工具類別
 *
 * 提供密碼強度驗證、評分和建議功能
 */
export class PasswordValidator {
    static MIN_LENGTH = 8;
    static MAX_LENGTH = 128;

    /**
     * 驗證密碼並回傳結果
     * @param {string} password - 密碼
     * @param {Object} options - 選項
     * @param {string|null} options.username - 使用者名稱
     * @param {string|null} options.email - 電子郵件
     * @returns {Object} 驗證結果
     */
    static validate(password, options = {}) {
        const { username = null, email = null } = options;
        const errors = [];
        const warnings = [];
        let score = 0;

        // 長度檢查
        if (password.length < this.MIN_LENGTH) {
            errors.push(`密碼長度至少需要 ${this.MIN_LENGTH} 個字元`);
        } else {
            score += 20;
        }

        if (password.length > this.MAX_LENGTH) {
            errors.push(`密碼長度不能超過 ${this.MAX_LENGTH} 個字元`);
        }

        // 包含小寫字母
        if (!/[a-z]/.test(password)) {
            errors.push("密碼必須包含至少一個小寫字母");
        } else {
            score += 15;
        }

        // 包含大寫字母
        if (!/[A-Z]/.test(password)) {
            errors.push("密碼必須包含至少一個大寫字母");
        } else {
            score += 15;
        }

        // 包含數字
        if (!/[0-9]/.test(password)) {
            errors.push("密碼必須包含至少一個數字");
        } else {
            score += 15;
        }

        // 包含特殊符號（加分項）
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
            score += 20;
        } else {
            warnings.push("建議包含至少一個特殊符號以增加安全性");
        }

        // 連續字元檢查
        if (this.hasSequentialChars(password)) {
            errors.push("密碼不能包含連續的英文字母或數字（如 abc, 123）");
            score -= 10;
        }

        // 重複字元檢查
        if (this.hasRepeatingChars(password)) {
            errors.push("密碼不能包含重複的字元（如 aaa, 111）");
            score -= 10;
        }

        // 常見密碼檢查
        if (this.isCommonPassword(password)) {
            errors.push("此密碼過於常見，請使用更安全的密碼");
            score -= 20;
        }

        // 個人資訊檢查
        if (this.containsPersonalInfo(password, username, email)) {
            errors.push("密碼不能包含使用者名稱或電子郵件");
            score -= 15;
        }

        // 長度加分
        if (password.length >= 12) score += 10;
        if (password.length >= 16) score += 10;

        // 確保分數在 0-100 之間
        score = Math.max(0, Math.min(100, score));

        return {
            isValid: errors.length === 0,
            score,
            strength: this.getStrengthLevel(score),
            errors,
            warnings,
            suggestions: this.getSuggestions(errors, warnings),
        };
    }

    /**
     * 檢查是否包含連續字元
     */
    static hasSequentialChars(password) {
        const lower = password.toLowerCase();

        for (let i = 0; i < lower.length - 2; i++) {
            const char1 = lower.charCodeAt(i);
            const char2 = lower.charCodeAt(i + 1);
            const char3 = lower.charCodeAt(i + 2);

            if (
                (char2 === char1 + 1 && char3 === char2 + 1) ||
                (char2 === char1 - 1 && char3 === char2 - 1)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查是否包含重複字元
     */
    static hasRepeatingChars(password) {
        return /(.)\1{2,}/.test(password);
    }

    /**
     * 檢查是否為常見密碼
     */
    static isCommonPassword(password) {
        const commonPasswords = [
            "password",
            "password123",
            "12345678",
            "qwerty",
            "abc123",
            "monkey",
            "1234567890",
            "letmein",
            "trustno1",
            "dragon",
            "baseball",
            "iloveyou",
            "master",
            "sunshine",
            "ashley",
            "bailey",
            "welcome",
            "login",
            "admin",
            "princess",
            "solo",
            "hello",
            "freedom",
            "whatever",
            "qwertyuiop",
            "passw0rd",
            "password1",
            "welcome123",
            "admin123",
            "test1234",
        ];

        return commonPasswords.includes(password.toLowerCase());
    }

    /**
     * 檢查是否包含個人資訊
     */
    static containsPersonalInfo(password, username, email) {
        const lower = password.toLowerCase();

        if (username && username.length >= 3) {
            if (lower.includes(username.toLowerCase())) {
                return true;
            }
        }

        if (email) {
            const emailPrefix = email.split("@")[0];
            if (emailPrefix && emailPrefix.length >= 3) {
                if (lower.includes(emailPrefix.toLowerCase())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 獲取強度等級
     */
    static getStrengthLevel(score) {
        if (score >= 80) return "very-strong";
        if (score >= 60) return "strong";
        if (score >= 40) return "medium";
        if (score >= 20) return "weak";
        return "very-weak";
    }

    /**
     * 獲取建議
     */
    static getSuggestions(errors, warnings) {
        const suggestions = [];

        if (errors.some((e) => e.includes("長度"))) {
            suggestions.push("使用更長的密碼（建議 12 個字元以上）");
        }

        if (errors.some((e) => e.includes("字母") || e.includes("數字"))) {
            suggestions.push("混合使用大小寫字母、數字和特殊符號");
        }

        if (errors.some((e) => e.includes("連續") || e.includes("重複"))) {
            suggestions.push("避免使用簡單的模式或重複字元");
        }

        if (errors.some((e) => e.includes("常見"))) {
            suggestions.push("使用獨特的密碼組合，不要使用常見單字");
        }

        if (suggestions.length === 0 && warnings.length > 0) {
            suggestions.push("已經很好！可以加入特殊符號讓密碼更安全");
        }

        return suggestions;
    }

    /**
     * 獲取強度文字
     */
    static getStrengthText(strength) {
        const texts = {
            "very-weak": "非常弱",
            weak: "弱",
            medium: "中等",
            strong: "強",
            "very-strong": "非常強",
        };
        return texts[strength] || "未知";
    }

    /**
     * 獲取強度顏色
     */
    static getStrengthColor(strength) {
        const colors = {
            "very-weak": "bg-red-500",
            weak: "bg-orange-500",
            medium: "bg-yellow-500",
            strong: "bg-blue-500",
            "very-strong": "bg-green-500",
        };
        return colors[strength] || "bg-gray-500";
    }
}
