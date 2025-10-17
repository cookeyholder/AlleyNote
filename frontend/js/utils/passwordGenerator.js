import { PasswordValidator } from "./passwordValidator.js";

/**
 * 安全密碼生成器
 */
export class PasswordGenerator {
    static DEFAULT_LENGTH = 12;
    static LOWERCASE = "abcdefghjkmnpqrstuvwxyz"; // 移除易混淆字元 i, l, o
    static UPPERCASE = "ABCDEFGHJKLMNPQRSTUVWXYZ"; // 移除易混淆字元 I, O
    static NUMBERS = "23456789"; // 移除易混淆數字 0, 1
    static SPECIAL = "!@#$%^&*()_+-=[]{}";

    /**
     * 生成安全密碼
     * @param {Object} options - 選項
     * @param {number} options.length - 密碼長度
     * @param {boolean} options.lowercase - 包含小寫字母
     * @param {boolean} options.uppercase - 包含大寫字母
     * @param {boolean} options.numbers - 包含數字
     * @param {boolean} options.special - 包含特殊符號
     * @param {string|null} options.username - 避免的使用者名稱
     * @param {string|null} options.email - 避免的電子郵件
     * @returns {string} 生成的密碼
     */
    static generate(options = {}) {
        const {
            length = this.DEFAULT_LENGTH,
            lowercase = true,
            uppercase = true,
            numbers = true,
            special = true,
            username = null,
            email = null,
        } = options;

        let charset = "";
        const requirements = [];

        if (lowercase) {
            charset += this.LOWERCASE;
            requirements.push(this.LOWERCASE);
        }
        if (uppercase) {
            charset += this.UPPERCASE;
            requirements.push(this.UPPERCASE);
        }
        if (numbers) {
            charset += this.NUMBERS;
            requirements.push(this.NUMBERS);
        }
        if (special) {
            charset += this.SPECIAL;
            requirements.push(this.SPECIAL);
        }

        if (charset.length === 0) {
            throw new Error("至少需要選擇一種字元類型");
        }

        let attempts = 0;
        const maxAttempts = 100;

        while (attempts < maxAttempts) {
            let password = "";

            // 確保每種類型至少有一個字元
            for (const req of requirements) {
                password += req.charAt(Math.floor(Math.random() * req.length));
            }

            // 填充剩餘長度
            for (let i = password.length; i < length; i++) {
                password += charset.charAt(
                    Math.floor(Math.random() * charset.length)
                );
            }

            // 打亂順序
            password = this.shuffle(password);

            // 驗證密碼
            const validation = PasswordValidator.validate(password, {
                username,
                email,
            });

            if (validation.isValid) {
                return password;
            }

            attempts++;
        }

        throw new Error("無法生成符合要求的密碼，請調整選項");
    }

    /**
     * 打亂字串
     */
    static shuffle(str) {
        const arr = str.split("");
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr.join("");
    }

    /**
     * 生成多個密碼供選擇
     */
    static generateMultiple(count = 5, options = {}) {
        const passwords = [];
        for (let i = 0; i < count; i++) {
            try {
                passwords.push(this.generate(options));
            } catch (error) {
                console.error("生成密碼失敗:", error);
            }
        }
        return passwords;
    }

    /**
     * 複製到剪貼簿
     */
    static async copyToClipboard(text) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            } else {
                // Fallback for older browsers
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    const successful = document.execCommand("copy");
                    document.body.removeChild(textArea);
                    return successful;
                } catch (err) {
                    document.body.removeChild(textArea);
                    return false;
                }
            }
        } catch (err) {
            console.error("複製失敗:", err);
            return false;
        }
    }
}
