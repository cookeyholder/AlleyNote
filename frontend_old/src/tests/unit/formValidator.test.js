import { describe, it, expect, beforeEach } from 'vitest';
import { formValidator } from '../../utils/formValidator.js';

describe('FormValidator', () => {
  describe('validateEmail', () => {
    it('應該驗證有效的電子郵件', () => {
      const validEmails = [
        'test@example.com',
        'user.name@example.com',
        'user+tag@example.co.uk',
        'test123@test-domain.com',
      ];

      validEmails.forEach((email) => {
        const result = formValidator.validateEmail(email);
        expect(result.isValid).toBe(true);
        expect(result.error).toBeUndefined();
      });
    });

    it('應該拒絕無效的電子郵件', () => {
      const invalidEmails = [
        '',
        'not-an-email',
        '@example.com',
        'user@',
        'user@example',
        'user @example.com',
      ];

      invalidEmails.forEach((email) => {
        const result = formValidator.validateEmail(email);
        expect(result.isValid).toBe(false);
        expect(result.error).toBeDefined();
      });
    });
  });

  describe('validatePassword', () => {
    it('應該驗證有效的密碼', () => {
      const validPasswords = [
        'password123',
        'MyP@ssw0rd',
        '12345678',
        'abcdefgh',
      ];

      validPasswords.forEach((password) => {
        const result = formValidator.validatePassword(password);
        expect(result.isValid).toBe(true);
        expect(result.error).toBeUndefined();
      });
    });

    it('應該拒絕太短的密碼', () => {
      const shortPasswords = ['', '1234', '12345', '1234567'];

      shortPasswords.forEach((password) => {
        const result = formValidator.validatePassword(password);
        expect(result.isValid).toBe(false);
        expect(result.error).toContain('8');
      });
    });

    it('應該拒絕太長的密碼', () => {
      const longPassword = 'a'.repeat(129);
      const result = formValidator.validatePassword(longPassword);
      expect(result.isValid).toBe(false);
      expect(result.error).toContain('128');
    });
  });

  describe('validateUsername', () => {
    it('應該驗證有效的使用者名稱', () => {
      const validUsernames = [
        'user',
        'user123',
        'user_name',
        'user-name',
        'User123',
      ];

      validUsernames.forEach((username) => {
        const result = formValidator.validateUsername(username);
        expect(result.isValid).toBe(true);
        expect(result.error).toBeUndefined();
      });
    });

    it('應該拒絕太短的使用者名稱', () => {
      const shortUsernames = ['', 'a', 'ab'];

      shortUsernames.forEach((username) => {
        const result = formValidator.validateUsername(username);
        expect(result.isValid).toBe(false);
        expect(result.error).toContain('3');
      });
    });

    it('應該拒絕太長的使用者名稱', () => {
      const longUsername = 'a'.repeat(51);
      const result = formValidator.validateUsername(longUsername);
      expect(result.isValid).toBe(false);
      expect(result.error).toContain('50');
    });

    it('應該拒絕包含特殊字元的使用者名稱', () => {
      const invalidUsernames = [
        'user@name',
        'user name',
        'user#123',
        'user$name',
      ];

      invalidUsernames.forEach((username) => {
        const result = formValidator.validateUsername(username);
        expect(result.isValid).toBe(false);
        expect(result.error).toBeDefined();
      });
    });
  });

  describe('validateRequired', () => {
    it('應該驗證非空值', () => {
      const validValues = [
        'test',
        '0',
        'false',
        ' text ',
      ];

      validValues.forEach((value) => {
        const result = formValidator.validateRequired(value, 'field');
        expect(result.isValid).toBe(true);
        expect(result.error).toBeUndefined();
      });
    });

    it('應該拒絕空值', () => {
      const emptyValues = [
        '',
        null,
        undefined,
        '   ',
      ];

      emptyValues.forEach((value) => {
        const result = formValidator.validateRequired(value, 'field');
        expect(result.isValid).toBe(false);
        expect(result.error).toContain('必填');
      });
    });
  });

  describe('validateMinLength', () => {
    it('應該驗證符合最小長度的值', () => {
      const result = formValidator.validateMinLength('12345', 5);
      expect(result.isValid).toBe(true);
      expect(result.error).toBeUndefined();
    });

    it('應該拒絕不符合最小長度的值', () => {
      const result = formValidator.validateMinLength('1234', 5);
      expect(result.isValid).toBe(false);
      expect(result.error).toContain('5');
    });
  });

  describe('validateMaxLength', () => {
    it('應該驗證符合最大長度的值', () => {
      const result = formValidator.validateMaxLength('12345', 10);
      expect(result.isValid).toBe(true);
      expect(result.error).toBeUndefined();
    });

    it('應該拒絕超過最大長度的值', () => {
      const result = formValidator.validateMaxLength('12345678901', 10);
      expect(result.isValid).toBe(false);
      expect(result.error).toContain('10');
    });
  });

  describe('validateForm', () => {
    it('應該驗證整個表單', () => {
      const formData = {
        email: 'test@example.com',
        password: 'password123',
        username: 'testuser',
      };

      const rules = {
        email: [(value) => formValidator.validateEmail(value)],
        password: [(value) => formValidator.validatePassword(value)],
        username: [(value) => formValidator.validateUsername(value)],
      };

      const result = formValidator.validateForm(formData, rules);
      expect(result.isValid).toBe(true);
      expect(result.errors).toEqual({});
    });

    it('應該回傳所有欄位的錯誤', () => {
      const formData = {
        email: 'invalid-email',
        password: '123',
        username: 'ab',
      };

      const rules = {
        email: [(value) => formValidator.validateEmail(value)],
        password: [(value) => formValidator.validatePassword(value)],
        username: [(value) => formValidator.validateUsername(value)],
      };

      const result = formValidator.validateForm(formData, rules);
      expect(result.isValid).toBe(false);
      expect(result.errors).toHaveProperty('email');
      expect(result.errors).toHaveProperty('password');
      expect(result.errors).toHaveProperty('username');
    });

    it('應該支援多重驗證規則', () => {
      const formData = {
        password: '123',
      };

      const rules = {
        password: [
          (value) => formValidator.validateRequired(value, 'password'),
          (value) => formValidator.validateMinLength(value, 8),
          (value) => formValidator.validateMaxLength(value, 128),
        ],
      };

      const result = formValidator.validateForm(formData, rules);
      expect(result.isValid).toBe(false);
      expect(result.errors.password).toBeDefined();
    });
  });
});
