/**
 * 全域 Banner 管理器
 * 用於顯示持續性的系統狀態，例如離線或同步問題。
 */

class BannerManager {
  constructor() {
    this.container = null;
    this.activeBanner = null;
    this.ensureContainer();
  }

  ensureContainer() {
    this.container = document.getElementById("global-banner-container");

    if (!this.container) {
      this.container = document.createElement("div");
      this.container.id = "global-banner-container";
      this.container.className =
        "fixed inset-x-0 top-0 z-[60] flex justify-center px-4 pt-4 pointer-events-none";
      document.body.appendChild(this.container);
    }
  }

  show(message, type = "info", options = {}) {
    const {
      title = "系統通知",
      dismissible = true,
      actionLabel = null,
      onAction = null,
    } = options;

    this.hide();

    const palette = {
      success: {
        shell: "border-emerald-200 bg-emerald-50 text-emerald-900",
        badge: "bg-emerald-600 text-white",
      },
      error: {
        shell: "border-rose-200 bg-rose-50 text-rose-900",
        badge: "bg-rose-600 text-white",
      },
      warning: {
        shell: "border-amber-200 bg-amber-50 text-amber-900",
        badge: "bg-amber-500 text-white",
      },
      info: {
        shell: "border-sky-200 bg-sky-50 text-sky-900",
        badge: "bg-sky-600 text-white",
      },
    };

    const colors = palette[type] || palette.info;
    const banner = document.createElement("div");
    banner.className = `pointer-events-auto w-full max-w-4xl rounded-2xl border shadow-lg shadow-modern-900/10 backdrop-blur-sm ${colors.shell}`;
    banner.innerHTML = `
      <div class="flex items-start gap-4 px-5 py-4">
        <div class="mt-0.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.2em] ${colors.badge}">
          ${title}
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold leading-6">${message}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2" data-banner-actions></div>
      </div>
    `;

    const actions = banner.querySelector("[data-banner-actions]");

    if (actionLabel && typeof onAction === "function") {
      const actionButton = document.createElement("button");
      actionButton.type = "button";
      actionButton.className =
        "rounded-xl bg-white/80 px-3 py-2 text-xs font-bold text-modern-800 transition-all hover:bg-white";
      actionButton.textContent = actionLabel;
      actionButton.addEventListener("click", onAction);
      actions.appendChild(actionButton);
    }

    if (dismissible) {
      const dismissButton = document.createElement("button");
      dismissButton.type = "button";
      dismissButton.className =
        "rounded-xl px-2 py-2 text-modern-500 transition-colors hover:bg-white/70 hover:text-modern-900";
      dismissButton.setAttribute("aria-label", "關閉通知");
      dismissButton.innerHTML = `
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      `;
      dismissButton.addEventListener("click", () => this.hide());
      actions.appendChild(dismissButton);
    }

    this.container.appendChild(banner);
    this.activeBanner = banner;
    return banner;
  }

  hide() {
    if (!this.activeBanner) return;
    this.activeBanner.remove();
    this.activeBanner = null;
  }
}

export const banner = new BannerManager();
