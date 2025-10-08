import { ApiClient } from "../api/ApiClient.js";

/**
 * 統計儀表板頁面
 *
 * 負責向後端 API 取得統計資料，並以視覺化卡片、列表的形式呈現。
 */
export class StatisticsDashboard {
    /**
     * @param {{
     *   rootSelector?: string,
     *   apiClient?: ApiClient
     * }} options
     */
    constructor({
        rootSelector = "#statistics-dashboard",
        apiClient = new ApiClient("/api"),
    } = {}) {
        /** @type {HTMLElement | null} */
        this.root = document.querySelector(rootSelector);
        this.apiClient = apiClient;
        this.elements = {
            overview: null,
            sources: null,
            popularPosts: null,
            trendingCategories: null,
            activeUsers: null,
            error: null,
            loadingOverlay: null,
            lastUpdated: null,
        };
    }

    /**
     * 初始化儀表板
     */
    init() {
        if (!this.root) {
            console.warn("找不到統計儀表板容器，略過初始化");

            return;
        }

        this.renderLayout();
        this.attachEventListeners();
        void this.loadAllStatistics();
    }

    /**
     * 渲染基本版面
     */
    renderLayout() {
        const today = new Date();
        const startDate = new Date();
        startDate.setDate(today.getDate() - 6);

        this.root.innerHTML = `
            <section class="py-20 bg-accent-50" aria-labelledby="statistics-dashboard-title">
                <div class="max-w-6xl mx-auto px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
                        <div>
                            <p class="text-sm uppercase tracking-widest text-accent-600 font-semibold">Statistics</p>
                            <h2 id="statistics-dashboard-title" class="text-3xl md:text-4xl font-bold text-modern-900 mt-2">
                                文章統計即時儀表板
                            </h2>
                            <p class="text-modern-600 mt-2 text-base leading-relaxed">
                                透過統計查詢 API 即時掌握文章產出、來源分布、熱門內容與活躍使用者指標。
                            </p>
                        </div>
                        <div class="bg-white border border-modern-200 rounded-2xl shadow-sm p-4 md:w-96">
                            <form id="statistics-filter-form" class="grid grid-cols-1 gap-3" novalidate>
                                <div>
                                    <label for="start-date" class="block text-sm font-medium text-modern-600">開始日期</label>
                                    <input
                                        id="start-date"
                                        name="start-date"
                                        type="date"
                                        class="mt-1 block w-full rounded-lg border border-modern-200 px-3 py-2 text-modern-700 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-200"
                                        value="${startDate
                                            .toISOString()
                                            .slice(0, 10)}"
                                        max="${today
                                            .toISOString()
                                            .slice(0, 10)}"
                                    />
                                </div>
                                <div>
                                    <label for="end-date" class="block text-sm font-medium text-modern-600">結束日期</label>
                                    <input
                                        id="end-date"
                                        name="end-date"
                                        type="date"
                                        class="mt-1 block w-full rounded-lg border border-modern-200 px-3 py-2 text-modern-700 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-200"
                                        value="${today
                                            .toISOString()
                                            .slice(0, 10)}"
                                        max="${today
                                            .toISOString()
                                            .slice(0, 10)}"
                                    />
                                </div>
                                <div>
                                    <label for="auth-token" class="block text-sm font-medium text-modern-600">Bearer Token（選填）</label>
                                    <input
                                        id="auth-token"
                                        name="auth-token"
                                        type="password"
                                        autocomplete="off"
                                        placeholder="輸入具有 statistics.read 權限的 JWT"
                                        class="mt-1 block w-full rounded-lg border border-modern-200 px-3 py-2 text-modern-700 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-200"
                                    />
                                </div>
                                <div class="flex items-center gap-3">
                                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-accent-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-accent-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-500 transition">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m21 21-4.35-4.35"></path>
                                            <circle cx="11" cy="11" r="7"></circle>
                                        </svg>
                                        重新載入統計資料
                                    </button>
                                    <button type="button" id="reset-filter" class="rounded-lg border border-modern-200 px-3 py-2 text-sm font-medium text-modern-600 hover:bg-modern-100 transition">
                                        重設
                                    </button>
                                </div>
                            </form>
                            <p id="statistics-last-updated" class="mt-3 text-xs text-modern-500"></p>
                        </div>
                    </div>

                    <div id="statistics-error" class="hidden mb-8 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"></div>

                    <div class="relative">
                        <div id="statistics-loading" class="hidden absolute inset-0 flex items-center justify-center rounded-2xl bg-white/80 backdrop-blur">
                            <div class="flex items-center gap-3 text-modern-600">
                                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span>載入統計資料中...</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-8" aria-live="polite">
                            <section aria-labelledby="statistics-overview-title" class="rounded-2xl border border-modern-200 bg-white p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 id="statistics-overview-title" class="text-lg font-semibold text-modern-900">核心指標總覽</h3>
                                    <span class="text-xs font-medium text-modern-500">根據所選期間彙整</span>
                                </div>
                                <div id="statistics-overview" class="grid grid-cols-1 gap-4 md:grid-cols-3"></div>
                            </section>

                            <section class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                                <article aria-labelledby="statistics-sources-title" class="rounded-2xl border border-modern-200 bg-white p-6 shadow-sm">
                                    <h3 id="statistics-sources-title" class="text-lg font-semibold text-modern-900 mb-4">文章來源分布</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-modern-200 text-sm">
                                            <thead class="bg-modern-50 text-modern-500 uppercase tracking-wide">
                                                <tr>
                                                    <th scope="col" class="px-4 py-2 text-left font-semibold">來源</th>
                                                    <th scope="col" class="px-4 py-2 text-right font-semibold">文章數量</th>
                                                    <th scope="col" class="px-4 py-2 text-right font-semibold">比例</th>
                                                </tr>
                                            </thead>
                                            <tbody id="statistics-sources" class="divide-y divide-modern-100 text-modern-700"></tbody>
                                        </table>
                                    </div>
                                </article>

                                <article aria-labelledby="statistics-popular-title" class="rounded-2xl border border-modern-200 bg-white p-6 shadow-sm">
                                    <h3 id="statistics-popular-title" class="text-lg font-semibold text-modern-900 mb-4">熱門內容觀察</h3>
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="text-sm font-semibold text-modern-600 mb-3">最多瀏覽文章</h4>
                                            <ul id="statistics-popular-posts" class="space-y-3 text-sm text-modern-700"></ul>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-modern-600 mb-3">熱門分類</h4>
                                            <ul id="statistics-trending-categories" class="space-y-2 text-sm text-modern-700"></ul>
                                        </div>
                                    </div>
                                </article>
                            </section>

                            <section aria-labelledby="statistics-users-title" class="rounded-2xl border border-modern-200 bg-white p-6 shadow-sm">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 id="statistics-users-title" class="text-lg font-semibold text-modern-900">活躍使用者概況</h3>
                                    <span class="text-xs font-medium text-modern-500">取前 5 名作為示意</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-modern-200 text-sm">
                                        <thead class="bg-modern-50 text-modern-500 uppercase tracking-wide">
                                            <tr>
                                                <th scope="col" class="px-4 py-2 text-left font-semibold">使用者</th>
                                                <th scope="col" class="px-4 py-2 text-right font-semibold">發文數</th>
                                                <th scope="col" class="px-4 py-2 text-right font-semibold">瀏覽數</th>
                                                <th scope="col" class="px-4 py-2 text-right font-semibold">按讚數</th>
                                                <th scope="col" class="px-4 py-2 text-right font-semibold">最後活躍</th>
                                            </tr>
                                        </thead>
                                        <tbody id="statistics-active-users" class="divide-y divide-modern-100 text-modern-700"></tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </section>
        `;

        this.cacheElements();
    }

    cacheElements() {
        this.elements = {
            overview: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-overview")
            ),
            sources: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-sources")
            ),
            popularPosts: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-popular-posts")
            ),
            trendingCategories: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-trending-categories")
            ),
            activeUsers: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-active-users")
            ),
            error: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-error")
            ),
            loadingOverlay: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-loading")
            ),
            lastUpdated: /** @type {HTMLElement} */ (
                this.root?.querySelector("#statistics-last-updated")
            ),
        };
    }

    attachEventListeners() {
        const form = this.root?.querySelector("#statistics-filter-form");
        const resetButton = this.root?.querySelector("#reset-filter");

        form?.addEventListener("submit", (event) => {
            event.preventDefault();
            void this.loadAllStatistics();
        });

        resetButton?.addEventListener("click", () => {
            const today = new Date();
            const startDate = new Date();
            startDate.setDate(today.getDate() - 6);

            const startInput = /** @type {HTMLInputElement | null} */ (
                this.root?.querySelector("#start-date")
            );
            const endInput = /** @type {HTMLInputElement | null} */ (
                this.root?.querySelector("#end-date")
            );
            const tokenInput = /** @type {HTMLInputElement | null} */ (
                this.root?.querySelector("#auth-token")
            );

            if (startInput) {
                startInput.value = startDate.toISOString().slice(0, 10);
            }
            if (endInput) {
                endInput.value = today.toISOString().slice(0, 10);
            }
            if (tokenInput) {
                tokenInput.value = "";
            }

            void this.loadAllStatistics();
        });
    }

    /**
     * 取得目前表單的查詢參數
     * @returns {{ startDate?: string, endDate?: string, token?: string }}
     */
    getFilters() {
        const startInput = /** @type {HTMLInputElement | null} */ (
            this.root?.querySelector("#start-date")
        );
        const endInput = /** @type {HTMLInputElement | null} */ (
            this.root?.querySelector("#end-date")
        );
        const tokenInput = /** @type {HTMLInputElement | null} */ (
            this.root?.querySelector("#auth-token")
        );

        return {
            startDate: startInput?.value
                ? `${startInput.value}T00:00:00`
                : undefined,
            endDate: endInput?.value ? `${endInput.value}T23:59:59` : undefined,
            token: tokenInput?.value?.trim() || undefined,
        };
    }

    async loadAllStatistics() {
        if (!this.elements.loadingOverlay) {
            return;
        }

        this.clearError();
        this.toggleLoading(true);

        try {
            const filters = this.getFilters();
            const params = this.buildQueryParams(filters);
            const headers = this.buildHeaders(filters.token);

            const [overview, sources, popular, users] = await Promise.all([
                this.fetchStatistics(
                    "/v1/statistics/overview",
                    params,
                    headers
                ),
                this.fetchStatistics("/v1/statistics/sources", params, headers),
                this.fetchStatistics("/v1/statistics/popular", params, headers),
                this.fetchStatistics(
                    "/v1/statistics/users",
                    { ...params, limit: 5 },
                    headers
                ),
            ]);

            this.renderOverview(overview?.data ?? null);
            this.renderSources(sources?.data ?? []);
            this.renderPopular(popular?.data ?? {});
            this.renderActiveUsers(users?.data ?? []);
            this.updateTimestamp();
        } catch (error) {
            this.showError(
                error instanceof Error
                    ? error.message
                    : "統計資料載入失敗，請稍後再試。"
            );
        } finally {
            this.toggleLoading(false);
        }
    }

    /**
     * @param {string} endpoint
     * @param {Record<string, string | number | undefined>} params
     * @param {Record<string, string>} headers
     */
    async fetchStatistics(endpoint, params, headers) {
        const queryString = this.buildQueryString(params);

        try {
            return await this.apiClient.request(`${endpoint}${queryString}`, {
                method: "GET",
                headers,
            });
        } catch (error) {
            throw this.normalizeError(error);
        }
    }

    /**
     * 組合查詢參數
     * @param {{ startDate?: string, endDate?: string }} filters
     */
    buildQueryParams(filters) {
        const params = {};
        if (filters.startDate) {
            params.start_date = filters.startDate.slice(0, 10);
        }
        if (filters.endDate) {
            params.end_date = filters.endDate.slice(0, 10);
        }

        return params;
    }

    /**
     * 建立查詢字串
     * @param {Record<string, string | number | undefined>} params
     */
    buildQueryString(params) {
        const searchParams = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== "") {
                searchParams.append(key, String(value));
            }
        });

        const query = searchParams.toString();

        return query ? `?${query}` : "";
    }

    /**
     * 建立請求標頭
     * @param {string | undefined} token
     */
    buildHeaders(token) {
        const headers = {
            "Content-Type": "application/json",
            Accept: "application/json",
        };

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        return headers;
    }

    /**
     * 處理錯誤訊息
     * @param {unknown} error
     */
    normalizeError(error) {
        if (error instanceof Error) {
            if (/(401|403)/.test(error.message)) {
                return new Error(
                    "API 回應需要統計查詢權限，請提供有效的 Bearer Token 後重試。"
                );
            }

            return error;
        }

        return new Error("無法取得統計資料，請確認權限或稍後再試。");
    }

    renderOverview(overview) {
        if (!this.elements.overview) {
            return;
        }

        if (!overview) {
            this.elements.overview.innerHTML = this.renderEmptyState(
                "尚未取得統計概覽",
                "請確認 API 權限或稍後重新整理。"
            );

            return;
        }

        const cards = [
            {
                title: "文章總數",
                value: overview.total_posts ?? 0,
                description: "發布文章總量，含草稿與已發佈。",
            },
            {
                title: "活躍使用者",
                value: overview.active_users ?? 0,
                description: "在所選期間內登入或操作的使用者。",
            },
            {
                title: "新增使用者",
                value: overview.new_users ?? 0,
                description: "期間內新增的註冊使用者數量。",
            },
        ];

        this.elements.overview.innerHTML = cards
            .map(
                (card) => `
                <article class="rounded-xl border border-modern-200 bg-modern-50 p-5 shadow-sm transition hover:shadow-md">
                    <p class="text-sm font-medium text-modern-500">${
                        card.title
                    }</p>
                    <p class="mt-2 text-3xl font-bold text-modern-900">${this.formatNumber(
                        card.value
                    )}</p>
                    <p class="mt-2 text-xs text-modern-500">${
                        card.description
                    }</p>
                </article>
            `
            )
            .join("");
    }

    renderSources(sources) {
        if (!this.elements.sources) {
            return;
        }

        if (!Array.isArray(sources) || sources.length === 0) {
            this.elements.sources.innerHTML = `
                <tr>
                    <td colspan="3" class="px-4 py-6 text-center text-sm text-modern-500">
                        尚未取得來源統計資料
                    </td>
                </tr>
            `;

            return;
        }

        this.elements.sources.innerHTML = sources
            .map(
                (item) => `
                <tr class="hover:bg-modern-50 transition">
                    <td class="px-4 py-3 font-medium text-modern-800">${
                        item.source ?? "N/A"
                    }</td>
                    <td class="px-4 py-3 text-right tabular-nums">${this.formatNumber(
                        item.count ?? 0
                    )}</td>
                    <td class="px-4 py-3 text-right tabular-nums">${(
                        item.percentage ?? 0
                    ).toFixed(1)}%</td>
                </tr>
            `
            )
            .join("");
    }

    renderPopular(popular) {
        const posts = Array.isArray(popular?.most_viewed_posts)
            ? popular.most_viewed_posts
            : [];
        const categories = Array.isArray(popular?.trending_categories)
            ? popular.trending_categories
            : [];

        if (this.elements.popularPosts) {
            this.elements.popularPosts.innerHTML = posts.length
                ? posts
                      .map(
                          (post, index) => `
                          <li class="flex items-start justify-between rounded-lg border border-modern-200 px-4 py-3">
                              <div>
                                  <p class="font-medium text-modern-800">${
                                      index + 1
                                  }. ${post.title ?? "未命名文章"}</p>
                                  <p class="text-xs text-modern-500">文章 ID：${
                                      post.id ?? "-"
                                  }</p>
                              </div>
                              <span class="ml-4 text-sm font-semibold text-accent-600 tabular-nums">${this.formatNumber(
                                  post.views ?? 0
                              )} 次瀏覽</span>
                          </li>
                      `
                      )
                      .join("")
                : this.renderListEmptyState("尚未取得熱門文章資料");
        }

        if (this.elements.trendingCategories) {
            this.elements.trendingCategories.innerHTML = categories.length
                ? categories
                      .map(
                          (category) => `
                          <li class="flex items-center justify-between rounded-lg bg-modern-50 px-4 py-2">
                              <span class="font-medium text-modern-700">${
                                  category.name ?? "未命名分類"
                              }</span>
                              <span class="text-sm font-semibold text-modern-600 tabular-nums">${this.formatNumber(
                                  category.posts ?? 0
                              )} 篇</span>
                          </li>
                      `
                      )
                      .join("")
                : this.renderListEmptyState("尚未取得熱門分類資料");
        }
    }

    renderActiveUsers(users) {
        if (!this.elements.activeUsers) {
            return;
        }

        if (!Array.isArray(users) || users.length === 0) {
            this.elements.activeUsers.innerHTML = `
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-modern-500">
                        尚未取得使用者統計資料
                    </td>
                </tr>
            `;

            return;
        }

        this.elements.activeUsers.innerHTML = users
            .map(
                (user) => `
                <tr class="hover:bg-modern-50 transition">
                    <td class="px-4 py-3 font-medium text-modern-800">${
                        user.username ?? user.id ?? "未知使用者"
                    }</td>
                    <td class="px-4 py-3 text-right tabular-nums">${this.formatNumber(
                        user.post_count ?? 0
                    )}</td>
                    <td class="px-4 py-3 text-right tabular-nums">${this.formatNumber(
                        user.view_count ?? 0
                    )}</td>
                    <td class="px-4 py-3 text-right tabular-nums">${this.formatNumber(
                        user.like_count ?? 0
                    )}</td>
                    <td class="px-4 py-3 text-right text-xs text-modern-500">${this.formatDateTime(
                        user.last_active_at
                    )}</td>
                </tr>
            `
            )
            .join("");
    }

    updateTimestamp() {
        if (!this.elements.lastUpdated) {
            return;
        }

        const timestamp = new Date().toLocaleString("zh-TW", {
            hour12: false,
        });

        this.elements.lastUpdated.textContent = `最近更新：${timestamp}`;
    }

    /**
     * 顯示載入指示
     * @param {boolean} isLoading
     */
    toggleLoading(isLoading) {
        if (!this.elements.loadingOverlay) {
            return;
        }

        this.elements.loadingOverlay.classList.toggle("hidden", !isLoading);
    }

    /**
     * 顯示錯誤訊息
     * @param {string} message
     */
    showError(message) {
        if (!this.elements.error) {
            return;
        }

        this.elements.error.textContent = message;
        this.elements.error.classList.remove("hidden");
    }

    clearError() {
        if (!this.elements.error) {
            return;
        }

        this.elements.error.classList.add("hidden");
        this.elements.error.textContent = "";
    }

    /**
     * 共用清單空狀態
     * @param {string} message
     */
    renderListEmptyState(message) {
        return `<li class="rounded-lg border border-dashed border-modern-200 px-4 py-3 text-center text-sm text-modern-500">${message}</li>`;
    }

    renderEmptyState(title, description) {
        return `
            <article class="rounded-xl border border-dashed border-modern-200 bg-white p-6 text-center text-modern-500">
                <h4 class="text-sm font-semibold text-modern-600">${title}</h4>
                <p class="mt-2 text-xs text-modern-500">${description}</p>
            </article>
        `;
    }

    formatNumber(value) {
        return Number.isFinite(Number(value))
            ? Number(value).toLocaleString("zh-TW")
            : "0";
    }

    formatDateTime(value) {
        if (!value) {
            return "-";
        }

        try {
            const date = new Date(value);

            return Number.isNaN(date.getTime())
                ? String(value)
                : date.toLocaleString("zh-TW", { hour12: false });
        } catch (error) {
            return String(value);
        }
    }
}
