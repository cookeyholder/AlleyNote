/**
 * 登入頁面
 */

import { authApi } from '../../api/auth.js';
import { toast } from '../../utils/toast.js';
import { validator } from '../../utils/validator.js';
import { router } from '../../utils/router.js';

export function renderLoginPage() {
    const content = document.getElementById('content');
    content.innerHTML = `
        <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8">
                <div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        AlleyNote 公布欄系統
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        請登入您的帳號
                    </p>
                </div>
                <form id="login-form" class="mt-8 space-y-6">
                    <div class="rounded-md shadow-sm -space-y-px">
                        <div>
                            <label for="username" class="sr-only">帳號</label>
                            <input
                                id="username"
                                name="username"
                                type="text"
                                required
                                class="form-input appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                placeholder="帳號"
                            />
                            <div id="username-error" class="form-error hidden"></div>
                        </div>
                        <div>
                            <label for="password" class="sr-only">密碼</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                class="form-input appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                placeholder="密碼"
                            />
                            <div id="password-error" class="form-error hidden"></div>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="btn btn-primary w-full flex justify-center py-2 px-4"
                        >
                            登入
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // 綁定表單提交事件
    const form = document.getElementById('login-form');
    form.addEventListener('submit', handleLogin);
}

async function handleLogin(e) {
    e.preventDefault();
    
    validator.clearErrors('login-form');
    
    const formData = {
        username: document.getElementById('username').value,
        password: document.getElementById('password').value
    };

    // 驗證表單
    const { isValid, errors } = validator.validateForm(formData, {
        username: [
            (v) => validator.required(v, '帳號')
        ],
        password: [
            (v) => validator.required(v, '密碼')
        ]
    });

    if (!isValid) {
        validator.displayErrors(errors);
        return;
    }

    try {
        const response = await authApi.login(formData.username, formData.password);
        toast.success('登入成功！');
        
        // 根據使用者角色導航到不同頁面
        if (response.user && response.user.is_admin) {
            router.navigate('/admin/dashboard');
        } else {
            router.navigate('/');
        }
    } catch (error) {
        toast.error(error.message || '登入失敗，請檢查帳號密碼');
    }
}
