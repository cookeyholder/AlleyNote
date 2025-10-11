import { PasswordValidator } from '../utils/passwordValidator.js';

/**
 * 密碼強度指示器組件
 */
export class PasswordStrengthIndicator {
  /**
   * 建立密碼強度指示器
   * @param {HTMLInputElement} passwordInput - 密碼輸入框
   * @param {Object} options - 選項
   */
  constructor(passwordInput, options = {}) {
    this.passwordInput = passwordInput;
    this.options = {
      username: null,
      email: null,
      showRequirements: true,
      showSuggestions: true,
      ...options
    };
    
    this.container = null;
    this.init();
  }

  /**
   * 初始化組件
   */
  init() {
    this.createIndicator();
    this.attachEventListeners();
  }

  /**
   * 建立指示器 HTML
   */
  createIndicator() {
    const wrapper = document.createElement('div');
    wrapper.className = 'password-strength-indicator mt-2';
    wrapper.innerHTML = `
      <div class="strength-bar-container hidden">
        <div class="flex justify-between items-center mb-1">
          <span class="text-sm font-medium text-modern-700">密碼強度</span>
          <span class="text-sm font-medium strength-text">未知</span>
        </div>
        <div class="w-full h-2 bg-modern-200 rounded-full overflow-hidden">
          <div class="strength-bar h-full transition-all duration-300" style="width: 0%"></div>
        </div>
      </div>
      
      ${this.options.showRequirements ? `
        <div class="requirements-list hidden mt-3">
          <p class="text-sm font-medium text-modern-700 mb-2">密碼要求：</p>
          <ul class="space-y-1">
            <li class="requirement-item flex items-center text-sm" data-rule="length">
              <span class="requirement-icon mr-2">○</span>
              <span>至少 8 個字元</span>
            </li>
            <li class="requirement-item flex items-center text-sm" data-rule="lowercase">
              <span class="requirement-icon mr-2">○</span>
              <span>包含小寫字母</span>
            </li>
            <li class="requirement-item flex items-center text-sm" data-rule="uppercase">
              <span class="requirement-icon mr-2">○</span>
              <span>包含大寫字母</span>
            </li>
            <li class="requirement-item flex items-center text-sm" data-rule="number">
              <span class="requirement-icon mr-2">○</span>
              <span>包含數字</span>
            </li>
            <li class="requirement-item flex items-center text-sm" data-rule="special">
              <span class="requirement-icon mr-2">○</span>
              <span>包含特殊符號（建議）</span>
            </li>
            <li class="requirement-item flex items-center text-sm" data-rule="no-sequential">
              <span class="requirement-icon mr-2">○</span>
              <span>不包含連續字元</span>
            </li>
            <li class="requirement-item flex items-center text-sm" data-rule="no-repeating">
              <span class="requirement-icon mr-2">○</span>
              <span>不包含重複字元</span>
            </li>
          </ul>
        </div>
      ` : ''}

      ${this.options.showSuggestions ? `
        <div class="suggestions-container hidden mt-3">
          <p class="text-sm font-medium text-modern-700 mb-2">建議：</p>
          <ul class="suggestions-list space-y-1"></ul>
        </div>
      ` : ''}
    `;

    // 插入到密碼輸入框後面
    this.passwordInput.parentNode.insertBefore(wrapper, this.passwordInput.nextSibling);
    this.container = wrapper;
  }

  /**
   * 附加事件監聽器
   */
  attachEventListeners() {
    this.passwordInput.addEventListener('input', () => this.updateIndicator());
    this.passwordInput.addEventListener('focus', () => this.show());
    // 當失去焦點且密碼為空時隱藏
    this.passwordInput.addEventListener('blur', () => {
      if (!this.passwordInput.value) {
        this.hide();
      }
    });
  }

  /**
   * 更新指示器
   */
  updateIndicator() {
    const password = this.passwordInput.value;
    
    if (!password) {
      this.hide();
      return;
    }

    this.show();

    const result = PasswordValidator.validate(password, {
      username: this.options.username,
      email: this.options.email
    });

    this.updateStrengthBar(result);
    if (this.options.showRequirements) {
      this.updateRequirements(password, result);
    }
    if (this.options.showSuggestions) {
      this.updateSuggestions(result);
    }
  }

  /**
   * 更新強度條
   */
  updateStrengthBar(result) {
    const bar = this.container.querySelector('.strength-bar');
    const text = this.container.querySelector('.strength-text');
    const color = PasswordValidator.getStrengthColor(result.strength);
    
    bar.style.width = `${result.score}%`;
    bar.className = `strength-bar h-full transition-all duration-300 ${color}`;
    text.textContent = PasswordValidator.getStrengthText(result.strength);
    text.className = `text-sm font-medium strength-text ${color.replace('bg-', 'text-')}`;
  }

  /**
   * 更新要求清單
   */
  updateRequirements(password, result) {
    const requirements = {
      'length': password.length >= 8,
      'lowercase': /[a-z]/.test(password),
      'uppercase': /[A-Z]/.test(password),
      'number': /[0-9]/.test(password),
      'special': /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password),
      'no-sequential': !PasswordValidator.hasSequentialChars(password),
      'no-repeating': !PasswordValidator.hasRepeatingChars(password)
    };

    Object.entries(requirements).forEach(([rule, isMet]) => {
      const item = this.container.querySelector(`[data-rule="${rule}"]`);
      if (item) {
        const icon = item.querySelector('.requirement-icon');
        if (isMet) {
          item.classList.add('text-green-600');
          item.classList.remove('text-modern-600');
          icon.textContent = '✓';
        } else {
          item.classList.remove('text-green-600');
          item.classList.add('text-modern-600');
          icon.textContent = '○';
        }
      }
    });
  }

  /**
   * 更新建議
   */
  updateSuggestions(result) {
    const suggestionsContainer = this.container.querySelector('.suggestions-container');
    const suggestionsList = this.container.querySelector('.suggestions-list');

    if (result.suggestions.length > 0) {
      suggestionsList.innerHTML = result.suggestions
        .map(s => `<li class="text-sm text-modern-600">• ${s}</li>`)
        .join('');
      suggestionsContainer.classList.remove('hidden');
    } else {
      suggestionsContainer.classList.add('hidden');
    }
  }

  /**
   * 顯示指示器
   */
  show() {
    this.container.querySelector('.strength-bar-container')?.classList.remove('hidden');
    if (this.options.showRequirements) {
      this.container.querySelector('.requirements-list')?.classList.remove('hidden');
    }
  }

  /**
   * 隱藏指示器
   */
  hide() {
    this.container.querySelector('.strength-bar-container')?.classList.add('hidden');
    this.container.querySelector('.requirements-list')?.classList.add('hidden');
    this.container.querySelector('.suggestions-container')?.classList.add('hidden');
  }

  /**
   * 更新選項
   */
  updateOptions(options) {
    this.options = { ...this.options, ...options };
    if (this.passwordInput.value) {
      this.updateIndicator();
    }
  }

  /**
   * 銷毀組件
   */
  destroy() {
    if (this.container) {
      this.container.remove();
    }
  }
}
