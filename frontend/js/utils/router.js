/**
 * 簡單的前端路由器
 */

class Router {
    constructor() {
        this.routes = {};
        this.currentRoute = null;
        this.init();
    }

    init() {
        window.addEventListener('popstate', () => this.handleRoute());
        
        // 攔截所有連結點擊
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-link]');
            if (link) {
                e.preventDefault();
                this.navigate(link.getAttribute('href'));
            }
        });

        this.handleRoute();
    }

    addRoute(path, handler) {
        this.routes[path] = handler;
    }

    navigate(path, replace = false) {
        if (replace) {
            window.history.replaceState({}, '', path);
        } else {
            window.history.pushState({}, '', path);
        }
        this.handleRoute();
    }

    handleRoute() {
        const path = window.location.pathname;
        let matched = false;

        // 嘗試精確匹配
        if (this.routes[path]) {
            this.routes[path]();
            this.currentRoute = path;
            matched = true;
        } else {
            // 嘗試參數匹配
            for (const route in this.routes) {
                const params = this.matchRoute(route, path);
                if (params !== null) {
                    this.routes[route](params);
                    this.currentRoute = route;
                    matched = true;
                    break;
                }
            }
        }

        if (!matched && this.routes['*']) {
            this.routes['*']();
        }
    }

    matchRoute(route, path) {
        const routeParts = route.split('/').filter(Boolean);
        const pathParts = path.split('/').filter(Boolean);

        if (routeParts.length !== pathParts.length) {
            return null;
        }

        const params = {};

        for (let i = 0; i < routeParts.length; i++) {
            if (routeParts[i].startsWith(':')) {
                const paramName = routeParts[i].slice(1);
                params[paramName] = pathParts[i];
            } else if (routeParts[i] !== pathParts[i]) {
                return null;
            }
        }

        return params;
    }

    getQueryParams() {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        for (const [key, value] of params) {
            result[key] = value;
        }
        return result;
    }
}

export const router = new Router();
