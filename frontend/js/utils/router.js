/**
 * 前端路由器
 * 使用 Navigo 提供 SPA 路由功能
 */

import { globalGetters } from '../store/globalStore.js';

// 路由器實例
let routerInstance = null;

/**
 * 等待 Navigo 載入
 */
function waitForNavigo() {
  return new Promise((resolve) => {
    if (typeof window.Navigo !== 'undefined') {
      resolve(window.Navigo);
      return;
    }
    
    let attempts = 0;
    const maxAttempts = 100; // 5 秒
    
    const checkInterval = setInterval(() => {
      attempts++;
      if (typeof window.Navigo !== 'undefined') {
        clearInterval(checkInterval);
        resolve(window.Navigo);
      } else if (attempts >= maxAttempts) {
        clearInterval(checkInterval);
        console.error('Navigo 載入超時');
        resolve(null);
      }
    }, 50);
  });
}

/**
 * 取得路由器實例
 */
export async function getRouter() {
  if (!routerInstance) {
    const Navigo = await waitForNavigo();
    if (Navigo) {
      routerInstance = new Navigo('/', { hash: false });
    } else {
      throw new Error('無法初始化路由器：Navigo 未載入');
    }
  }
  return routerInstance;
}

/**
 * 路由守衛 - 需要認證
 */
export async function requireAuth() {
  const router = await getRouter();
  if (!globalGetters.isAuthenticated()) {
    router.navigate('/login');
    return false;
  }
  return true;
}

/**
 * 路由守衛 - 僅訪客
 */
export async function guestOnly() {
  const router = await getRouter();
  if (globalGetters.isAuthenticated()) {
    router.navigate('/admin/dashboard');
    return false;
  }
  return true;
}

/**
 * 初始化路由
 */
export async function initRouter() {
  console.log('🛣️ 初始化路由系統...');
  
  const router = await getRouter();

  // 首頁
  router.on('/', () => {
    import('../pages/public/home.js').then(module => module.renderHome());
  });

  // 登入頁
  router.on('/login', () => {
    import('../pages/public/login.js').then(module => module.renderLogin());
  });

  // 忘記密碼頁
  router.on('/forgot-password', () => {
    import('../pages/public/forgotPassword.js').then(module => module.renderForgotPassword());
  });

  // 文章內頁
  router.on('/posts/:id', ({ data }) => {
    import('../pages/public/post.js').then(module => module.renderPost(data.id));
  });

  // 後台首頁（重導向到儀表板）
  router.on('/admin', async () => {
    if (await requireAuth()) {
      router.navigate('/admin/dashboard');
    }
  });

  // 後台儀表板
  router.on('/admin/dashboard', async () => {
    if (await requireAuth()) {
      import('../pages/admin/dashboard.js').then(module => module.renderDashboard());
    }
  });

  // 後台文章列表
  router.on('/admin/posts', async () => {
    if (await requireAuth()) {
      import('../pages/admin/posts.js').then(module => module.renderPostsList());
    }
  });

  // 後台新增文章
  router.on('/admin/posts/create', async () => {
    if (await requireAuth()) {
      import('../pages/admin/postEditor.js').then(module => module.renderPostEditor());
    }
  });

  // 後台編輯文章
  router.on('/admin/posts/:id/edit', async ({ data }) => {
    if (await requireAuth()) {
      import('../pages/admin/postEditor.js').then(module => module.renderPostEditor(data.id));
    }
  });

  // 後台個人資料
  router.on('/admin/profile', async () => {
    if (await requireAuth()) {
      import('../pages/admin/profile.js').then(module => module.renderProfile());
    }
  });

  // 後台使用者管理
  router.on('/admin/users', async () => {
    if (await requireAuth()) {
      import('../pages/admin/users.js').then(module => module.renderUsers());
    }
  });

  // 後台角色管理
  router.on('/admin/roles', async () => {
    if (await requireAuth()) {
      import('../pages/admin/roles.js').then(module => module.renderRoles());
    }
  });

  // 後台標籤管理
  router.on('/admin/tags', async () => {
    if (await requireAuth()) {
      import('../pages/admin/tags.js').then(module => module.renderTags());
    }
  });

  // 後台系統統計
  router.on('/admin/statistics', async () => {
    if (await requireAuth()) {
      import('../pages/admin/statistics.js').then(module => module.renderStatistics());
    }
  });

  // 後台系統設定
  router.on('/admin/settings', async () => {
    if (await requireAuth()) {
      import('../pages/admin/settings.js').then(module => module.renderSettings());
    }
  });

  // 錯誤頁面
  router.on('/403', () => {
    import('../pages/public/errors.js').then(module => module.render403());
  });

  router.on('/500', () => {
    import('../pages/public/errors.js').then(module => module.render500());
  });

  // 404 頁面
  router.notFound(() => {
    import('../pages/public/errors.js').then(module => module.render404());
  });

  // 解析當前 URL
  router.resolve();

  console.log('✅ 路由系統初始化完成');
}

// 匯出別名供向後相容
export const router = {
  get instance() {
    return routerInstance;
  },
  navigate: async (path) => {
    const r = await getRouter();
    r.navigate(path);
  },
  updatePageLinks: async () => {
    const r = await getRouter();
    r.updatePageLinks();
  }
};

export default router;
