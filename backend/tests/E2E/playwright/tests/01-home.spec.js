// @ts-check
const { test, expect } = require("@playwright/test");

const DEFAULT_SITE_SETTINGS = {
    siteName: "AlleyNote",
    heroTitle: "現代化公布欄系統",
    siteDescription: "現代化公布欄系統",
    footerCopyright: "© 2024 AlleyNote. All rights reserved.",
    footerDescription: "基於 Domain-Driven Design 的企業級公布欄系統",
};

function extractValue(raw, fallback) {
    if (raw && typeof raw === "object" && "value" in raw) {
        return raw.value ?? fallback;
    }

    return raw ?? fallback;
}

async function fetchSiteSettings(page) {
    try {
        const response = await page.request.get(
            "http://localhost:8080/api/settings"
        );
        if (!response.ok()) {
            const errorBody = await response.text();
            process.stdout.write(
                `settings fetch failed: ${response.status()} ${errorBody}\n`
            );

            return { ...DEFAULT_SITE_SETTINGS };
        }

        const payload = await response.json();
        const apiData =
            payload && typeof payload === "object" && "data" in payload
                ? payload.data ?? {}
                : payload ?? {};

        const siteName = extractValue(
            apiData.site_name,
            DEFAULT_SITE_SETTINGS.siteName
        );
        const heroTitle = extractValue(
            apiData.hero_title,
            extractValue(apiData.site_tagline, DEFAULT_SITE_SETTINGS.heroTitle)
        );

        const normalized = {
            siteName,
            heroTitle,
            siteDescription: extractValue(
                apiData.site_description,
                DEFAULT_SITE_SETTINGS.siteDescription
            ),
            footerCopyright: extractValue(
                apiData.footer_copyright,
                DEFAULT_SITE_SETTINGS.footerCopyright
            ),
            footerDescription: extractValue(
                apiData.footer_description,
                DEFAULT_SITE_SETTINGS.footerDescription
            ),
        };

        process.stdout.write(
            `settings fetched: ${JSON.stringify({
                siteName: normalized.siteName,
                heroTitle: normalized.heroTitle,
                siteDescription: normalized.siteDescription,
            })}\n`
        );

        return normalized;
    } catch (error) {
        process.stdout.write(`settings fetch error: ${error}\n`);

        return { ...DEFAULT_SITE_SETTINGS };
    }
}

function normalizeHtmlText(value = "") {
    return value
        .replace(/<[^>]*>/g, "")
        .replace(/&nbsp;/gi, " ")
        .trim();
}

/**
 * 首頁測試套件
 */
test.describe("首頁功能測試", () => {
    test.beforeEach(async ({ page }) => {
        await page.addInitScript(() => {
            if (typeof window !== "undefined") {
                window.localStorage?.clear?.();
                window.sessionStorage?.clear?.();
            }
        });

        await page.goto("/");
    });

    test("應該正確顯示首頁標題和導航", async ({ page }) => {
        const siteSettings = await fetchSiteSettings(page);

        const navBrand = page.locator("nav [data-site-name]").first();
        await expect(navBrand).toContainText(siteSettings.siteName);

        const heroTagline = page.locator("[data-site-tagline]").first();
        await expect(heroTagline).toContainText(siteSettings.heroTitle);

        const heroDescription = page.locator("[data-site-description]").first();
        await expect(heroDescription).toContainText(
            siteSettings.siteDescription
        );

        await expect(page.locator('button:has-text("登入")')).toBeVisible();
    });

    test("應該顯示最新文章列表", async ({ page }) => {
        // 等待文章載入
        await page.waitForSelector("text=最新文章", { timeout: 5000 });

        // 檢查文章數量提示
        const countText = await page
            .locator("text=/共 \\d+ 篇文章/")
            .textContent();
        expect(countText).toMatch(/共 \d+ 篇文章/);

        // 如果有文章，檢查文章卡片
        const postsCount = await page.locator("article.card").count();
        if (postsCount > 0) {
            // 檢查第一篇文章是否有標題和日期
            const firstPost = page.locator("article.card").first();
            await expect(firstPost.locator("h4")).toBeVisible();
            await expect(firstPost.locator("time")).toBeVisible();
        }
    });

    test.skip("應該能夠搜尋文章", async ({ page }) => {
        // 填寫搜尋關鍵字
        const searchInput = page.locator('input[placeholder*="搜尋"]').first();
        await searchInput.fill("測試");
        await searchInput.press("Enter");

        // 等待搜尋結果
        await page.waitForTimeout(1000);

        // 驗證 URL 或搜尋結果
        // 這裡的驗證取決於實際的搜尋實作
    });

    test("點擊登入按鈕應該導航到登入頁面", async ({ page }) => {
        await page.click('button:has-text("登入")');
        await expect(page).toHaveURL(/\/login/);
    });

    test("應該顯示頁腳資訊", async ({ page }) => {
        const siteSettings = await fetchSiteSettings(page);

        await expect(page.locator("footer")).toContainText(
            siteSettings.footerCopyright
        );

        const footerDescription = normalizeHtmlText(
            siteSettings.footerDescription
        );
        if (footerDescription.length > 0) {
            await expect(page.locator("#footer-description")).toContainText(
                footerDescription
            );
        } else {
            await expect(page.locator("#footer-description")).not.toBeEmpty();
        }
    });
});
