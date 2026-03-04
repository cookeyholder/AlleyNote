// @ts-check
const { test: base, expect } = require("@playwright/test");

/**
 * Secure-UI Spec 基礎頁面類別.
 */
class SecureBasePage {
  constructor(page) {
    this.page = page;
    this.toast = page.locator(".toast-item");
    this.errorAlert = page.locator(".toast-item.border-red-500");
  }

  /**
   * 檢查頁面是否包含敏感的系統錯誤資訊.
   */
  async assertNoSensitiveInfoLeaked() {
    const content = await this.page.content();
    const sensitivePatterns = [
      /Fatal error:/i,
      /Stack trace:/i,
      /Uncaught Error:/i,
      /SQLSTATE/i,
      /\[PDOException\]/i,
      /Internal Server Error/i,
    ];

    for (const pattern of sensitivePatterns) {
      await expect(this.page.locator("body")).not.toHaveText(pattern);
    }
  }

  /**
   * 檢查常用的安全標頭（如果可見於 Meta 標籤）.
   */
  async assertSecurityMetaTags() {
    const csrfMeta = this.page.locator('meta[name="csrf-token"]');
    await expect(csrfMeta).toBeAttached();
  }

  /**
   * 斷言富文本內容已被正確渲染而非轉義顯示.
   */
  async assertRichTextRendered(selector, expectedText, expectedTags = []) {
    const element = this.page.locator(selector);
    // 檢查可見文字
    await expect(element).toContainText(expectedText);

    // 檢查是否包含 HTML 標籤（確保不是 &lt;p&gt; 這種字串）
    const html = await element.innerHTML();
    for (const tag of expectedTags) {
      expect(html).toContain(`<${tag}`);
    }

    // 確保沒有原始標籤字串
    expect(html).not.toContain("&lt;");
  }
}

module.exports = { SecureBasePage };
