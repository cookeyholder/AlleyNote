import Navigo from 'navigo';
import { globalGetters } from '../store/globalStore.js';

/**
 * 路由實例
 */
export const router = new Navigo('/');

/**
 * 路由守衛 - 需要認證
 */
export function requireAuth() {
  if (!globalGetters.isAuthenticated()) {
    router.navigate('/login');
    return false;
  }
  return true;
}

/**
 * 初始化路由
 */
export function initRouter() {
  // 首頁
  router.on('/', () => {
    import('../pages/home.js').then((module) => module.renderHome());
  });

  // 登入頁
  router.on('/login', () => {
    import('../pages/login.js').then((module) => module.renderLogin());
  });

  // 文章內頁
  router.on('/posts/:id', ({ data }) => {
    import('../pages/post.js').then((module) => module.renderPost(data.id));
  });

  // 後台首頁
  router.on('/admin', () => {
    if (requireAuth()) {
      router.navigate('/admin/dashboard');
    }
  });

  // 後台儀表板
  router.on('/admin/dashboard', () => {
    if (requireAuth()) {
      import('../pages/admin/dashboard.js').then((module) => module.renderDashboard());
    }
  });

  // 後台文章列表
  router.on('/admin/posts', () => {
    if (requireAuth()) {
      import('../pages/admin/posts.js').then((module) => module.renderPostsList());
    }
  });

  // 後台新增文章
  router.on('/admin/posts/create', () => {
    if (requireAuth()) {
      import('../pages/admin/postEditor.js').then((module) => module.renderPostEditor());
    }
  });

  // 後台編輯文章
  router.on('/admin/posts/:id/edit', ({ data }) => {
    if (requireAuth()) {
      import('../pages/admin/postEditor.js').then((module) =>
        module.renderPostEditor(data.id)
      );
    }
  });

  // 後台個人資料
  router.on('/admin/profile', () => {
    if (requireAuth()) {
      import('../pages/admin/profile.js').then((module) => {
        const page = new module.default();
        page.init();
      });
    }
  });

  // 後台使用者管理
  router.on('/admin/users', () => {
    if (requireAuth()) {
      import('../pages/admin/users.js').then((module) => {
        const page = new module.default();
        page.init();
      });
    }
  });

  // 後台系統統計
  router.on('/admin/statistics', () => {
    if (requireAuth()) {
      import('../pages/admin/statistics.js').then((module) => {
        const page = new module.default();
        page.init();
      });
    }
  });

  // 404
  router.notFound(() => {
    import('../pages/notFound.js').then((module) => module.render404());
  });

  // 解析當前 URL
  router.resolve();
}
