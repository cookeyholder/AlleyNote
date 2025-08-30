<?php

declare(strict_types=1);

use App\Application\Controllers\Admin\TagManagementController;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use Psr\Container\ContainerInterface;

/**
 * 標籤管理路由設定
 *
 * 提供快取標籤管理的 REST API 端點
 */
return function (RouterInterface $router): void {

    // =========================================
    // 標籤管理 API 路由
    // =========================================

    // 標籤管理路由群組
    $router->group('/api/admin/cache/tags', function (RouterInterface $router) {
        
        // 取得所有標籤列表
        // GET /api/admin/cache/tags
        // 查詢參數: page, limit, search
        $router->get('', [TagManagementController::class, 'listTags']);
        
        // 取得標籤統計資訊
        // GET /api/admin/cache/tags/statistics
        $router->get('/statistics', [TagManagementController::class, 'getTagStatistics']);
        
        // 批量清空多個標籤
        // DELETE /api/admin/cache/tags
        // Body: {"tags": ["tag1", "tag2", ...]}
        $router->delete('', [TagManagementController::class, 'flushTags']);
        
        // 取得特定標籤詳細資訊
        // GET /api/admin/cache/tags/{tag}
        $router->get('/{tag}', [TagManagementController::class, 'getTag']);
        
        // 清空特定標籤的所有快取
        // DELETE /api/admin/cache/tags/{tag}
        $router->delete('/{tag}', [TagManagementController::class, 'flushTag']);
    });

    // =========================================
    // 快取分組管理 API 路由
    // =========================================

    // 分組管理路由群組
    $router->group('/api/admin/cache/groups', function (RouterInterface $router) {
        
        // 取得所有分組列表
        // GET /api/admin/cache/groups
        $router->get('', [TagManagementController::class, 'listGroups']);
        
        // 建立快取分組
        // POST /api/admin/cache/groups
        // Body: {"name": "group_name", "tags": ["tag1", "tag2"], "dependencies": [...]}
        $router->post('', [TagManagementController::class, 'createGroup']);
        
        // 清空特定分組
        // DELETE /api/admin/cache/groups/{group}
        // 查詢參數: cascade (是否級聯清空子分組，預設 true)
        $router->delete('/{group}', [TagManagementController::class, 'flushGroup']);
    });

    // =========================================
    // HTML 管理界面路由
    // =========================================

    // 標籤管理主頁面
    // GET /admin/cache/tags
    $router->get('/admin/cache/tags', function ($request, $response) {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>快取標籤管理 - AlleyNote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .tag-badge {
            font-size: 0.875rem;
            margin: 2px;
        }
        .tag-type-user { background-color: #198754; }
        .tag-type-module { background-color: #0d6efd; }
        .tag-type-temporal { background-color: #ffc107; color: #000; }
        .tag-type-group { background-color: #6f42c1; }
        .tag-type-custom { background-color: #6c757d; }
        .card-metric {
            text-align: center;
            padding: 1rem;
        }
        .metric-number {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .metric-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        .search-input {
            max-width: 300px;
        }
        .table-actions {
            white-space: nowrap;
        }
        .loading {
            display: none;
        }
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin">
                <i class="bi bi-house-door"></i> AlleyNote 管理
            </a>
            <div class="navbar-nav">
                <a class="nav-link" href="/admin/cache">快取監控</a>
                <a class="nav-link active" href="/admin/cache/tags">標籤管理</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-tags"></i> 快取標籤管理</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise"></i> 重新整理
                        </button>
                        <button class="btn btn-danger" onclick="showBulkDeleteModal()">
                            <i class="bi bi-trash"></i> 批量清空
                        </button>
                    </div>
                </div>

                <!-- 統計卡片 -->
                <div class="row mb-4" id="statisticsCards">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body card-metric">
                                <div class="metric-number" id="totalTags">-</div>
                                <div class="metric-label">總標籤數</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body card-metric">
                                <div class="metric-number" id="totalKeys">-</div>
                                <div class="metric-label">總快取鍵數</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body card-metric">
                                <div class="metric-number" id="totalGroups">-</div>
                                <div class="metric-label">分組數</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body card-metric">
                                <div class="metric-number" id="memoryUsage">-</div>
                                <div class="metric-label">記憶體使用</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 搜尋和分頁控制 -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control search-input" id="searchInput" 
                               placeholder="搜尋標籤..." onkeyup="handleSearch()">
                    </div>
                    <div class="col-md-6 text-end">
                        <nav aria-label="分頁">
                            <ul class="pagination pagination-sm" id="pagination">
                                <!-- 分頁項目將由 JavaScript 動態產生 -->
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- 標籤列表 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-tag"></i> 標籤列表
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="loadingIndicator" class="text-center py-5 loading">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">載入中...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped" id="tagsTable">
                                <thead>
                                    <tr>
                                        <th>標籤名稱</th>
                                        <th>類型</th>
                                        <th>快取鍵數</th>
                                        <th>驅動</th>
                                        <th>建立時間</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="tagsTableBody">
                                    <!-- 資料將由 JavaScript 動態產生 -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 標籤詳細資訊 Modal -->
    <div class="modal fade" id="tagDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">標籤詳細資訊</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="tagDetailsContent">
                    <!-- 詳細資訊內容 -->
                </div>
            </div>
        </div>
    </div>

    <!-- 批量清空確認 Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">批量清空標籤</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>選擇要清空的標籤：</p>
                    <div id="bulkDeleteTagsList">
                        <!-- 標籤清單 -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" onclick="executeBulkDelete()">確認清空</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 全域變數
        let currentPage = 1;
        let currentSearch = '';
        let allTags = [];

        // 初始化頁面
        document.addEventListener('DOMContentLoaded', function() {
            loadStatistics();
            loadTags();
        });

        // 載入統計資訊
        async function loadStatistics() {
            try {
                const response = await fetch('/api/admin/cache/tags/statistics');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('totalTags').textContent = stats.summary.total_tags || 0;
                    document.getElementById('totalKeys').textContent = stats.summary.total_keys || 0;
                    document.getElementById('totalGroups').textContent = stats.groups?.total_groups || 0;
                    document.getElementById('memoryUsage').textContent = formatBytes(stats.summary.total_memory_usage || 0);
                }
            } catch (error) {
                console.error('載入統計失敗:', error);
                showAlert('載入統計失敗', 'danger');
            }
        }

        // 載入標籤列表
        async function loadTags(page = 1, search = '') {
            showLoading(true);
            currentPage = page;
            currentSearch = search;
            
            try {
                const params = new URLSearchParams({
                    page: page.toString(),
                    limit: '20',
                    search: search
                });
                
                const response = await fetch('/api/admin/cache/tags?' + params);
                const data = await response.json();
                
                if (data.success) {
                    allTags = data.data.tags;
                    renderTagsTable(data.data.tags);
                    renderPagination(data.data.pagination);
                } else {
                    showAlert('載入標籤失敗: ' + data.error.message, 'danger');
                }
            } catch (error) {
                console.error('載入標籤失敗:', error);
                showAlert('載入標籤失敗', 'danger');
            } finally {
                showLoading(false);
            }
        }

        // 渲染標籤表格
        function renderTagsTable(tags) {
            const tbody = document.getElementById('tagsTableBody');
            tbody.innerHTML = '';
            
            if (tags.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">沒有找到標籤</td></tr>';
                return;
            }
            
            tags.forEach(tag => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <code>${escapeHtml(tag.name)}</code>
                        ${tag.sample_keys.length > 0 ? 
                            `<small class="text-muted d-block">範例: ${tag.sample_keys.slice(0, 2).map(k => k).join(', ')}</small>` : 
                            ''
                        }
                    </td>
                    <td>
                        <span class="badge tag-badge tag-type-${tag.type}">${getTypeLabel(tag.type)}</span>
                    </td>
                    <td>
                        <span class="badge bg-info">${tag.key_count}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">${tag.driver}</span>
                    </td>
                    <td>
                        <small class="text-muted">${tag.created_at}</small>
                    </td>
                    <td class="table-actions">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="showTagDetails('${escapeHtml(tag.name)}')">
                                <i class="bi bi-info-circle"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="flushTag('${escapeHtml(tag.name)}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // 渲染分頁
        function renderPagination(pagination) {
            const paginationEl = document.getElementById('pagination');
            paginationEl.innerHTML = '';
            
            if (pagination.pages <= 1) return;
            
            // 上一頁
            if (pagination.page > 1) {
                paginationEl.innerHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadTags(${pagination.page - 1}, '${currentSearch}')">上一頁</a></li>`;
            }
            
            // 頁碼
            const start = Math.max(1, pagination.page - 2);
            const end = Math.min(pagination.pages, pagination.page + 2);
            
            for (let i = start; i <= end; i++) {
                const active = i === pagination.page ? 'active' : '';
                paginationEl.innerHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadTags(${i}, '${currentSearch}')">${i}</a></li>`;
            }
            
            // 下一頁
            if (pagination.page < pagination.pages) {
                paginationEl.innerHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadTags(${pagination.page + 1}, '${currentSearch}')">下一頁</a></li>`;
            }
        }

        // 搜尋處理
        function handleSearch() {
            const search = document.getElementById('searchInput').value.trim();
            if (search !== currentSearch) {
                loadTags(1, search);
            }
        }

        // 顯示標籤詳細資訊
        async function showTagDetails(tagName) {
            try {
                const response = await fetch('/api/admin/cache/tags/' + encodeURIComponent(tagName));
                const data = await response.json();
                
                if (data.success) {
                    const tag = data.data;
                    const content = document.getElementById('tagDetailsContent');
                    content.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>基本資訊</h6>
                                <table class="table table-sm">
                                    <tr><th>標籤名稱</th><td><code>${escapeHtml(tag.name)}</code></td></tr>
                                    <tr><th>類型</th><td><span class="badge tag-type-${tag.type}">${getTypeLabel(tag.type)}</span></td></tr>
                                    <tr><th>快取鍵數</th><td>${tag.key_count}</td></tr>
                                    <tr><th>總記憶體使用</th><td>${formatBytes(tag.total_memory_usage)}</td></tr>
                                    <tr><th>驅動</th><td>${tag.driver}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>快取鍵列表</h6>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    ${tag.keys.length > 0 ? 
                                        tag.keys.map(k => `<div class="mb-1"><code class="small">${escapeHtml(k.key)}</code> <span class="badge bg-light text-dark">${formatBytes(k.value_size)}</span></div>`).join('') :
                                        '<p class="text-muted">沒有快取鍵</p>'
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                    
                    const modal = new bootstrap.Modal(document.getElementById('tagDetailsModal'));
                    modal.show();
                } else {
                    showAlert('載入標籤詳細資訊失敗: ' + data.error.message, 'danger');
                }
            } catch (error) {
                console.error('載入標籤詳細資訊失敗:', error);
                showAlert('載入標籤詳細資訊失敗', 'danger');
            }
        }

        // 清空單一標籤
        async function flushTag(tagName) {
            if (!confirm(`確定要清空標籤 "${tagName}" 的所有快取嗎？此操作無法撤銷。`)) {
                return;
            }
            
            try {
                const response = await fetch('/api/admin/cache/tags/' + encodeURIComponent(tagName), {
                    method: 'DELETE'
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert(`標籤 "${tagName}" 已清空 ${data.data.cleared_count} 個項目`, 'success');
                    refreshData();
                } else {
                    showAlert('清空標籤失敗: ' + data.error.message, 'danger');
                }
            } catch (error) {
                console.error('清空標籤失敗:', error);
                showAlert('清空標籤失敗', 'danger');
            }
        }

        // 顯示批量刪除對話框
        function showBulkDeleteModal() {
            const listEl = document.getElementById('bulkDeleteTagsList');
            listEl.innerHTML = '';
            
            allTags.forEach(tag => {
                listEl.innerHTML += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${escapeHtml(tag.name)}" id="tag_${tag.name}">
                        <label class="form-check-label" for="tag_${tag.name}">
                            <code>${escapeHtml(tag.name)}</code> 
                            <span class="badge bg-info">${tag.key_count} 鍵</span>
                        </label>
                    </div>
                `;
            });
            
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();
        }

        // 執行批量刪除
        async function executeBulkDelete() {
            const checkboxes = document.querySelectorAll('#bulkDeleteTagsList input[type="checkbox"]:checked');
            const tags = Array.from(checkboxes).map(cb => cb.value);
            
            if (tags.length === 0) {
                showAlert('請選擇要清空的標籤', 'warning');
                return;
            }
            
            if (!confirm(`確定要清空 ${tags.length} 個標籤的所有快取嗎？此操作無法撤銷。`)) {
                return;
            }
            
            try {
                const response = await fetch('/api/admin/cache/tags', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ tags: tags })
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert(`批量清空完成，共清空 ${data.data.total_cleared} 個項目`, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'));
                    modal.hide();
                    refreshData();
                } else {
                    showAlert('批量清空失敗: ' + data.error.message, 'danger');
                }
            } catch (error) {
                console.error('批量清空失敗:', error);
                showAlert('批量清空失敗', 'danger');
            }
        }

        // 重新整理資料
        function refreshData() {
            loadStatistics();
            loadTags(currentPage, currentSearch);
        }

        // 顯示/隱藏載入指示器
        function showLoading(show) {
            document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
            document.getElementById('tagsTable').style.display = show ? 'none' : 'table';
        }

        // 顯示警告訊息
        function showAlert(message, type = 'info') {
            const alertEl = document.createElement('div');
            alertEl.className = `alert alert-${type} alert-dismissible fade show`;
            alertEl.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.insertBefore(alertEl, document.body.firstChild);
            
            setTimeout(() => {
                alertEl.remove();
            }, 5000);
        }

        // 工具函式
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function getTypeLabel(type) {
            const labels = {
                user: '使用者',
                module: '模組',
                temporal: '時間',
                group: '分組',
                custom: '自訂'
            };
            return labels[type] || type;
        }
    </script>
</body>
</html>
HTML;
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    });
};