/**
 * 快取標籤管理頁面.
 */
export default class CacheTagsPage {
  constructor() {
    this.currentPage = 1;
    this.currentSearch = "";
    this.allTags = [];
  }

  init() {
    this.loadStatistics();
    this.loadTags();

    document
      .getElementById("btnRefresh")
      .addEventListener("click", () => this.refreshData());
    document
      .getElementById("btnBulkDelete")
      .addEventListener("click", () => this.showBulkDeleteModal());
    document
      .getElementById("btnExecuteBulkDelete")
      .addEventListener("click", () => this.executeBulkDelete());
    document
      .getElementById("searchInput")
      .addEventListener("keyup", (e) => this.handleSearch(e));

    document.getElementById("tagsTableBody").addEventListener("click", (e) => {
      const btnDetails = e.target.closest(".btn-tag-details");
      if (btnDetails) {
        const tagName = btnDetails.getAttribute("data-tag");
        this.showTagDetails(tagName);
        return;
      }
      const btnFlush = e.target.closest(".btn-flush-tag");
      if (btnFlush) {
        const tagName = btnFlush.getAttribute("data-tag");
        this.flushTag(tagName);
        return;
      }
    });

    document.getElementById("pagination").addEventListener("click", (e) => {
      const link = e.target.closest(".page-nav-link");
      if (link) {
        e.preventDefault();
        const page = parseInt(link.getAttribute("data-page"), 10);
        this.loadTags(page, this.currentSearch);
      }
    });
  }

  async loadStatistics() {
    try {
      const response = await fetch("/api/admin/cache/tags/statistics");
      const data = await response.json();

      if (data.success) {
        const stats = data.data;
        document.getElementById("totalTags").textContent =
          stats.summary.total_tags || 0;
        document.getElementById("totalKeys").textContent =
          stats.summary.total_keys || 0;
        document.getElementById("totalGroups").textContent =
          stats.groups?.total_groups || 0;
        document.getElementById("memoryUsage").textContent = this.formatBytes(
          stats.summary.total_memory_usage || 0,
        );
      }
    } catch (error) {
      console.error("載入統計失敗:", error);
      this.showAlert("載入統計失敗", "danger");
    }
  }

  async loadTags(page = 1, search = "") {
    this.showLoading(true);
    this.currentPage = page;
    this.currentSearch = search;

    try {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: "20",
        search: search,
      });

      const response = await fetch("/api/admin/cache/tags?" + params);
      const data = await response.json();

      if (data.success) {
        this.allTags = data.data.tags;
        this.renderTable(data.data.tags);
        this.renderPagination(data.data.pagination);
      } else {
        this.showAlert("載入標籤失敗: " + data.error.message, "danger");
      }
    } catch (error) {
      console.error("載入標籤失敗:", error);
      this.showAlert("載入標籤失敗", "danger");
    } finally {
      this.showLoading(false);
    }
  }

  renderTable(tags) {
    const tbody = document.getElementById("tagsTableBody");
    tbody.innerHTML = "";

    if (tags.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="6" class="text-center text-muted">沒有找到標籤</td></tr>';
      return;
    }

    tags.forEach((tag) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>
          <code>${this.escapeHtml(tag.name)}</code>
          ${
            tag.sample_keys.length > 0
              ? `<small class="text-muted d-block">範例: ${tag.sample_keys
                  .slice(0, 2)
                  .map((k) => this.escapeHtml(k))
                  .join(", ")}</small>`
              : ""
          }
        </td>
        <td>
          <span class="badge tag-badge tag-type-${tag.type}">${this.getTypeLabel(tag.type)}</span>
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
            <button class="btn btn-outline-primary btn-tag-details" data-tag="${this.escapeHtml(tag.name)}">
              <i class="bi bi-info-circle"></i>
            </button>
            <button class="btn btn-outline-danger btn-flush-tag" data-tag="${this.escapeHtml(tag.name)}">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(row);
    });
  }

  renderPagination(pagination) {
    const paginationEl = document.getElementById("pagination");
    paginationEl.innerHTML = "";

    if (pagination.pages <= 1) return;

    if (pagination.page > 1) {
      paginationEl.innerHTML += `<li class="page-item"><a class="page-link page-nav-link" href="#" data-page="${pagination.page - 1}">上一頁</a></li>`;
    }

    const start = Math.max(1, pagination.page - 2);
    const end = Math.min(pagination.pages, pagination.page + 2);

    for (let i = start; i <= end; i++) {
      const active = i === pagination.page ? "active" : "";
      paginationEl.innerHTML += `<li class="page-item ${active}"><a class="page-link page-nav-link" href="#" data-page="${i}">${i}</a></li>`;
    }

    if (pagination.page < pagination.pages) {
      paginationEl.innerHTML += `<li class="page-item"><a class="page-link page-nav-link" href="#" data-page="${pagination.page + 1}">下一頁</a></li>`;
    }
  }

  handleSearch() {
    const search = document.getElementById("searchInput").value.trim();
    if (search !== this.currentSearch) {
      this.loadTags(1, search);
    }
  }

  async showTagDetails(tagName) {
    try {
      const response = await fetch(
        "/api/admin/cache/tags/" + encodeURIComponent(tagName),
      );
      const data = await response.json();

      if (data.success) {
        const tag = data.data;
        const content = document.getElementById("tagDetailsContent");
        content.innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <h6>基本資訊</h6>
              <table class="table table-sm">
                <tr><th>標籤名稱</th><td><code>${this.escapeHtml(tag.name)}</code></td></tr>
                <tr><th>類型</th><td><span class="badge tag-type-${tag.type}">${this.getTypeLabel(tag.type)}</span></td></tr>
                <tr><th>快取鍵數</th><td>${tag.key_count}</td></tr>
                <tr><th>總記憶體使用</th><td>${this.formatBytes(tag.total_memory_usage)}</td></tr>
                <tr><th>驅動</th><td>${tag.driver}</td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6>快取鍵列表</h6>
              <div style="max-height: 300px; overflow-y: auto;">
                ${
                  tag.keys.length > 0
                    ? tag.keys
                        .map(
                          (k) =>
                            `<div class="mb-1"><code class="small">${this.escapeHtml(k.key)}</code> <span class="badge bg-light text-dark">${this.formatBytes(k.value_size)}</span></div>`,
                        )
                        .join("")
                    : '<p class="text-muted">沒有快取鍵</p>'
                }
              </div>
            </div>
          </div>
        `;

        const modal = new window.bootstrap.Modal(
          document.getElementById("tagDetailsModal"),
        );
        modal.show();
      } else {
        this.showAlert("載入標籤詳細資訊失敗: " + data.error.message, "danger");
      }
    } catch (error) {
      console.error("載入標籤詳細資訊失敗:", error);
      this.showAlert("載入標籤詳細資訊失敗", "danger");
    }
  }

  async flushTag(tagName) {
    const confirmed = await this.confirmAction(
      "確認清空標籤",
      `確定要清空標籤 "${tagName}" 的所有快取嗎？此操作無法撤銷。`,
      "確認清空",
    );

    if (!confirmed) {
      return;
    }

    try {
      const response = await fetch(
        "/api/admin/cache/tags/" + encodeURIComponent(tagName),
        {
          method: "DELETE",
        },
      );
      const data = await response.json();

      if (data.success) {
        this.showAlert(
          `標籤 "${tagName}" 已清空 ${data.data.cleared_count} 個項目`,
          "success",
        );
        this.refreshData();
      } else {
        this.showAlert("清空標籤失敗: " + data.error.message, "danger");
      }
    } catch (error) {
      console.error("清空標籤失敗:", error);
      this.showAlert("清空標籤失敗", "danger");
    }
  }

  showBulkDeleteModal() {
    const listEl = document.getElementById("bulkDeleteTagsList");
    listEl.innerHTML = "";

    this.allTags.forEach((tag, index) => {
      listEl.innerHTML += `
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="${this.escapeHtml(tag.name)}" id="bulkTag_${index}">
          <label class="form-check-label" for="bulkTag_${index}">
            <code>${this.escapeHtml(tag.name)}</code>
            <span class="badge bg-info">${tag.key_count} 鍵</span>
          </label>
        </div>
      `;
    });

    const modal = new window.bootstrap.Modal(
      document.getElementById("bulkDeleteModal"),
    );
    modal.show();
  }

  async executeBulkDelete() {
    const checkboxes = document.querySelectorAll(
      '#bulkDeleteTagsList input[type="checkbox"]:checked',
    );
    const tags = Array.from(checkboxes).map((cb) => cb.value);

    if (tags.length === 0) {
      this.showAlert("請選擇要清空的標籤", "warning");
      return;
    }

    const confirmed = await this.confirmAction(
      "確認批量清空",
      `確定要清空 ${tags.length} 個標籤的所有快取嗎？此操作無法撤銷。`,
      "確認批量清空",
    );

    if (!confirmed) {
      return;
    }

    try {
      const response = await fetch("/api/admin/cache/tags", {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ tags: tags }),
      });
      const data = await response.json();

      if (data.success) {
        this.showAlert(
          `批量清空完成，共清空 ${data.data.total_cleared} 個項目`,
          "success",
        );
        const modal = window.bootstrap.Modal.getInstance(
          document.getElementById("bulkDeleteModal"),
        );
        modal.hide();
        this.refreshData();
      } else {
        this.showAlert("批量清空失敗: " + data.error.message, "danger");
      }
    } catch (error) {
      console.error("批量清空失敗:", error);
      this.showAlert("批量清空失敗", "danger");
    }
  }

  refreshData() {
    this.loadStatistics();
    this.loadTags(this.currentPage, this.currentSearch);
  }

  showLoading(show) {
    document.getElementById("loadingIndicator").style.display = show
      ? "block"
      : "none";
    document.getElementById("tagsTable").style.display = show
      ? "none"
      : "table";
  }

  confirmAction(title, message, confirmText = "確認") {
    return new Promise((resolve) => {
      const modalId = "runtimeConfirmModal";
      const existing = document.getElementById(modalId);
      if (existing) {
        existing.remove();
      }

      const element = document.createElement("div");
      element.className = "modal fade";
      element.id = modalId;
      element.tabIndex = -1;
      element.setAttribute("aria-hidden", "true");

      const dialog = document.createElement("div");
      dialog.className = "modal-dialog modal-dialog-centered";

      const content = document.createElement("div");
      content.className = "modal-content border-0 shadow-lg";

      const header = document.createElement("div");
      header.className = "modal-header border-0 pb-0";

      const titleElement = document.createElement("h5");
      titleElement.className = "modal-title";
      titleElement.textContent = title;

      const closeButton = document.createElement("button");
      closeButton.type = "button";
      closeButton.className = "btn-close";
      closeButton.setAttribute("data-bs-dismiss", "modal");
      closeButton.setAttribute("aria-label", "Close");

      header.appendChild(titleElement);
      header.appendChild(closeButton);

      const body = document.createElement("div");
      body.className = "modal-body text-secondary pt-2";
      body.textContent = message;

      const footer = document.createElement("div");
      footer.className = "modal-footer border-0 pt-0";

      const cancelButton = document.createElement("button");
      cancelButton.type = "button";
      cancelButton.className = "btn btn-outline-secondary";
      cancelButton.setAttribute("data-action", "cancel");
      cancelButton.textContent = "取消";

      const confirmButton = document.createElement("button");
      confirmButton.type = "button";
      confirmButton.className = "btn btn-danger";
      confirmButton.setAttribute("data-action", "confirm");
      confirmButton.textContent = confirmText;

      footer.appendChild(cancelButton);
      footer.appendChild(confirmButton);

      content.appendChild(header);
      content.appendChild(body);
      content.appendChild(footer);
      dialog.appendChild(content);
      element.appendChild(dialog);

      document.body.appendChild(element);
      const runtimeModal = new window.bootstrap.Modal(element);
      let settled = false;

      const finish = (result) => {
        if (settled) {
          return;
        }

        settled = true;
        resolve(result);
        runtimeModal.hide();
      };

      element
        .querySelector('[data-action="cancel"]')
        .addEventListener("click", () => finish(false));
      element
        .querySelector('[data-action="confirm"]')
        .addEventListener("click", () => finish(true));
      element.addEventListener("hidden.bs.modal", () => {
        if (!settled) {
          settled = true;
          resolve(false);
        }
        element.remove();
      });

      runtimeModal.show();
    });
  }

  showAlert(message, type = "info") {
    const alertEl = document.createElement("div");
    alertEl.className = `alert alert-${type} alert-dismissible fade show`;

    const messageNode = document.createTextNode(message);
    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.className = "btn-close";
    closeButton.setAttribute("data-bs-dismiss", "alert");

    alertEl.appendChild(messageNode);
    alertEl.appendChild(closeButton);

    document.body.insertBefore(alertEl, document.body.firstChild);

    setTimeout(() => {
      alertEl.remove();
    }, 5000);
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  formatBytes(bytes) {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  }

  getTypeLabel(type) {
    const labels = {
      user: "使用者",
      module: "模組",
      temporal: "時間",
      group: "分組",
      custom: "自訂",
    };
    return labels[type] || type;
  }
}

new CacheTagsPage().init();
