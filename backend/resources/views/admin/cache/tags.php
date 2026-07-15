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
                        <button class="btn btn-outline-primary" id="btnRefresh">
                            <i class="bi bi-arrow-clockwise"></i> 重新整理
                        </button>
                        <button class="btn btn-danger" id="btnBulkDelete">
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
                               placeholder="搜尋標籤...">
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
                    <button type="button" class="btn btn-danger" id="btnExecuteBulkDelete">確認清空</button>
                </div>
            </div>
        </div>
    </div>

    <script nonce="<?= $nonce ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" nonce="<?= $nonce ?>" src="/js/pages/admin/cacheTags.js"></script>
</body>
</html>
