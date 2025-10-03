/**
 * 頁面級狀態管理
 * 用於管理單一頁面的臨時狀態
 */
import { Store } from './Store.js';

/**
 * 頁面 Store 工廠
 * 為每個頁面建立獨立的狀態管理實例
 */
export class PageStoreFactory {
    constructor() {
        this.stores = new Map();
    }

    /**
     * 獲取或建立頁面 Store
     * @param {string} pageId - 頁面唯一識別碼
     * @param {Object} initialState - 初始狀態
     * @returns {Store} Store 實例
     */
    getStore(pageId, initialState = {}) {
        if (!this.stores.has(pageId)) {
            this.stores.set(pageId, new Store(initialState));
        }
        return this.stores.get(pageId);
    }

    /**
     * 清除頁面 Store
     * @param {string} pageId - 頁面唯一識別碼
     */
    clearStore(pageId) {
        if (this.stores.has(pageId)) {
            const store = this.stores.get(pageId);
            store.clearAll();
            this.stores.delete(pageId);
        }
    }

    /**
     * 清除所有頁面 Store
     */
    clearAll() {
        this.stores.forEach((store) => store.clearAll());
        this.stores.clear();
    }
}

// 建立全域 PageStoreFactory 實例
export const pageStoreFactory = new PageStoreFactory();

/**
 * 文章編輯器頁面 Store
 * 包含草稿自動儲存、變更追蹤等功能
 */
export class PostEditorStore extends Store {
    constructor() {
        super({
            // 文章資料
            postId: null,
            title: '',
            content: '',
            status: 'draft',
            categoryId: null,
            tags: [],
            featuredImage: null,

            // 編輯狀態
            isDirty: false, // 是否有未儲存的變更
            isSaving: false, // 是否正在儲存
            lastSaved: null, // 最後儲存時間
            autoSaveEnabled: true, // 是否啟用自動儲存
            autoSaveInterval: 30000, // 自動儲存間隔（毫秒）

            // 錯誤狀態
            errors: {},
        });

        this.autoSaveTimer = null;
        this.originalData = null;
    }

    /**
     * 初始化編輯器
     * @param {Object} postData - 文章資料
     */
    initialize(postData = {}) {
        this.setState({
            postId: postData.id || null,
            title: postData.title || '',
            content: postData.content || '',
            status: postData.status || 'draft',
            categoryId: postData.category_id || null,
            tags: postData.tags || [],
            featuredImage: postData.featured_image || null,
            isDirty: false,
            errors: {},
        });

        // 儲存原始資料
        this.originalData = { ...this.getState() };

        // 啟動自動儲存
        if (this.getState('autoSaveEnabled')) {
            this.startAutoSave();
        }
    }

    /**
     * 更新文章欄位
     * @param {string} field - 欄位名稱
     * @param {*} value - 欄位值
     */
    updateField(field, value) {
        this.setState({ [field]: value, isDirty: true });
    }

    /**
     * 批次更新欄位
     * @param {Object} fields - 欄位物件
     */
    updateFields(fields) {
        this.setState({ ...fields, isDirty: true });
    }

    /**
     * 標記為已儲存
     */
    markAsSaved() {
        this.setState({
            isDirty: false,
            lastSaved: new Date().toISOString(),
        });

        // 更新原始資料
        this.originalData = { ...this.getState() };
    }

    /**
     * 檢查是否有變更
     * @returns {boolean}
     */
    hasChanges() {
        return this.getState('isDirty');
    }

    /**
     * 重置為原始狀態
     */
    reset() {
        if (this.originalData) {
            this.setState({ ...this.originalData, isDirty: false });
        }
    }

    /**
     * 啟動自動儲存
     */
    startAutoSave() {
        this.stopAutoSave();

        const interval = this.getState('autoSaveInterval');
        this.autoSaveTimer = setInterval(() => {
            if (this.hasChanges() && !this.getState('isSaving')) {
                this.emit('autoSave');
            }
        }, interval);
    }

    /**
     * 停止自動儲存
     */
    stopAutoSave() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
            this.autoSaveTimer = null;
        }
    }

    /**
     * 設定錯誤
     * @param {Object} errors - 錯誤物件
     */
    setErrors(errors) {
        this.setState({ errors });
    }

    /**
     * 清除錯誤
     */
    clearErrors() {
        this.setState({ errors: {} });
    }

    /**
     * 清理
     */
    destroy() {
        this.stopAutoSave();
        this.clearAll();
    }
}

/**
 * 列表頁面 Store
 * 包含分頁、篩選、排序等功能
 */
export class ListPageStore extends Store {
    constructor(initialState = {}) {
        super({
            // 列表資料
            items: [],
            total: 0,
            loading: false,
            error: null,

            // 分頁
            page: 1,
            perPage: 20,
            totalPages: 0,

            // 篩選
            filters: {},

            // 排序
            sortBy: null,
            sortOrder: 'desc',

            // 搜尋
            searchQuery: '',

            // 選擇
            selectedIds: [],
            selectAll: false,

            ...initialState,
        });
    }

    /**
     * 設定列表資料
     * @param {Array} items - 項目陣列
     * @param {Object} pagination - 分頁資訊
     */
    setItems(items, pagination = {}) {
        this.setState({
            items,
            total: pagination.total || items.length,
            totalPages: pagination.last_page || 1,
            page: pagination.current_page || 1,
            loading: false,
            error: null,
        });
    }

    /**
     * 設定載入狀態
     * @param {boolean} loading - 是否載入中
     */
    setLoading(loading) {
        this.setState({ loading });
    }

    /**
     * 設定錯誤
     * @param {Error} error - 錯誤物件
     */
    setError(error) {
        this.setState({ error: error.message, loading: false });
    }

    /**
     * 設定分頁
     * @param {number} page - 頁碼
     */
    setPage(page) {
        this.setState({ page });
        this.emit('pageChange', page);
    }

    /**
     * 設定每頁數量
     * @param {number} perPage - 每頁數量
     */
    setPerPage(perPage) {
        this.setState({ perPage, page: 1 });
        this.emit('perPageChange', perPage);
    }

    /**
     * 設定篩選
     * @param {Object} filters - 篩選物件
     */
    setFilters(filters) {
        this.setState({ filters, page: 1 });
        this.emit('filtersChange', filters);
    }

    /**
     * 設定排序
     * @param {string} sortBy - 排序欄位
     * @param {string} sortOrder - 排序方向
     */
    setSort(sortBy, sortOrder = 'desc') {
        this.setState({ sortBy, sortOrder });
        this.emit('sortChange', { sortBy, sortOrder });
    }

    /**
     * 設定搜尋
     * @param {string} query - 搜尋關鍵字
     */
    setSearch(query) {
        this.setState({ searchQuery: query, page: 1 });
        this.emit('searchChange', query);
    }

    /**
     * 切換項目選擇
     * @param {string|number} id - 項目 ID
     */
    toggleSelect(id) {
        const selectedIds = this.getState('selectedIds');
        const index = selectedIds.indexOf(id);

        if (index > -1) {
            selectedIds.splice(index, 1);
        } else {
            selectedIds.push(id);
        }

        this.setState({
            selectedIds: [...selectedIds],
            selectAll: false,
        });
    }

    /**
     * 切換全選
     */
    toggleSelectAll() {
        const selectAll = !this.getState('selectAll');
        const items = this.getState('items');

        this.setState({
            selectAll,
            selectedIds: selectAll ? items.map((item) => item.id) : [],
        });
    }

    /**
     * 清除選擇
     */
    clearSelection() {
        this.setState({ selectedIds: [], selectAll: false });
    }

    /**
     * 獲取已選擇的項目
     * @returns {Array}
     */
    getSelectedItems() {
        const items = this.getState('items');
        const selectedIds = this.getState('selectedIds');
        return items.filter((item) => selectedIds.includes(item.id));
    }
}

// 便捷方法
export function createPageStore(pageId, initialState = {}) {
    return pageStoreFactory.getStore(pageId, initialState);
}

export function createPostEditorStore() {
    return new PostEditorStore();
}

export function createListPageStore(initialState = {}) {
    return new ListPageStore(initialState);
}
