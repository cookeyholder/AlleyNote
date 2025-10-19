// @ts-check
const { test, expect } = require("@playwright/test");

/**
 * 系統設定頁面功能測試
 */
test.describe("系統設定頁面測試", () => {
    test.beforeEach(async ({ page }) => {
        // 登入
        await page.goto("/login");
        await page.fill('input[name="email"]', "admin@example.com");
        await page.fill('input[name="password"]', "password");
        await page.click('button[type="submit"]');
        await page.waitForURL("**/admin/dashboard", { timeout: 10000 });

        // 訪問系統設定頁面
        await page.goto("/admin/settings");
        await page.waitForLoadState("networkidle", { timeout: 15000 });
        await page.waitForTimeout(2000); // 等待設定載入
    });

    test("應該正確載入系統設定頁面", async ({ page }) => {
        // 檢查頁面標題
        const heading = page.locator('h1:has-text("系統設定")');
        await expect(heading).toBeVisible();

        // 檢查各個設定區塊標題
        await expect(page.locator('h2:has-text("基本設定")')).toBeVisible();
        await expect(page.locator('h2:has-text("文章設定")')).toBeVisible();
        await expect(page.locator('h2:has-text("時區設定")')).toBeVisible();
        await expect(page.locator('h2:has-text("使用者設定")')).toBeVisible();
    });

    test("應該顯示所有必要的表單欄位", async ({ page }) => {
        // 基本設定欄位
        await expect(page.locator("#site-name")).toBeVisible();
        await expect(page.locator("#site-description")).toBeVisible();

        // 文章設定欄位
        await expect(page.locator("#enable-comments")).toBeVisible();
        await expect(page.locator("#posts-per-page")).toBeVisible();

        // 時區設定欄位
        await expect(page.locator("#site-timezone")).toBeVisible();
        await expect(page.locator("#current-site-time")).toBeVisible();

        // 使用者設定欄位
        await expect(page.locator("#enable-registration")).toBeVisible();
        await expect(page.locator("#max-upload-size")).toBeVisible();
    });

    test("應該顯示儲存和重置按鈕", async ({ page }) => {
        const saveBtn = page.locator("#save-btn");
        const resetBtn = page.locator("#reset-btn");

        await expect(saveBtn).toBeVisible();
        await expect(saveBtn).toHaveText("儲存設定");

        await expect(resetBtn).toBeVisible();
        await expect(resetBtn).toHaveText("重置");
    });

    test("應該從資料庫載入現有設定", async ({ page }) => {
        // 等待設定載入（增加等待時間）
        await page.waitForTimeout(3000);

        // 網站名稱應該有值
        const siteName = page.locator("#site-name");
        await expect(siteName).toBeVisible();
        const siteNameValue = await siteName.inputValue();
        expect(siteNameValue).toBeTruthy();
        expect(siteNameValue.length).toBeGreaterThan(0);

        // 每頁文章數應該有值
        const postsPerPage = page.locator("#posts-per-page");
        await expect(postsPerPage).toBeVisible();
        const postsValue = await postsPerPage.inputValue();
        expect(postsValue).toBeTruthy();
        expect(parseInt(postsValue)).toBeGreaterThan(0);
    });

    test("時區選擇器應該有選項", async ({ page }) => {
        const timezoneSelect = page.locator("#site-timezone");

        // 等待選項載入
        await page.waitForTimeout(1000);

        // 檢查有選項
        const options = await timezoneSelect.locator("option").count();
        expect(options).toBeGreaterThan(1);

        // 檢查選項不是「載入中...」
        const firstOptionText = await timezoneSelect
            .locator("option")
            .first()
            .textContent();
        expect(firstOptionText).not.toBe("載入中...");
    });

    test("當前時間應該持續更新", async ({ page }) => {
        const timeElement = page.locator("#current-site-time");

        // 取得初始時間
        const initialTime = await timeElement.textContent();
        expect(initialTime).not.toBe("--:--:--");

        // 等待 2 秒
        await page.waitForTimeout(2000);

        // 時間應該已更新
        const updatedTime = await timeElement.textContent();
        expect(updatedTime).not.toBe(initialTime);
    });

    test("應該能夠修改網站名稱", async ({ page }) => {
        const siteName = page.locator("#site-name");

        // 清空並輸入新值
        await siteName.clear();
        await siteName.fill("測試網站名稱");

        // 確認值已更新
        const value = await siteName.inputValue();
        expect(value).toBe("測試網站名稱");
    });

    test("應該能夠切換留言功能開關", async ({ page }) => {
        const enableComments = page.locator("#enable-comments");

        // 等待元素可見
        await expect(enableComments).toBeVisible();
        await page.waitForTimeout(1000);

        // 取得初始狀態
        const initialState = await enableComments.isChecked();

        // 點擊父層的 label 來切換（因為 checkbox 是隱藏的）
        const label = page.locator("label:has(#enable-comments)");
        await label.click();
        await page.waitForTimeout(500);

        // 狀態應該改變
        const newState = await enableComments.isChecked();
        expect(newState).toBe(!initialState);
    });

    test("應該能夠修改每頁文章數", async ({ page }) => {
        const postsPerPage = page.locator("#posts-per-page");

        // 清空並輸入新值
        await postsPerPage.clear();
        await postsPerPage.fill("25");

        // 確認值已更新
        const value = await postsPerPage.inputValue();
        expect(value).toBe("25");
    });

    test("應該能夠更改時區設定", async ({ page }) => {
        const timezoneSelect = page.locator("#site-timezone");

        // 等待選項載入
        await page.waitForTimeout(1000);

        // 取得初始值
        const initialValue = await timezoneSelect.inputValue();

        // 選擇不同的時區
        const options = await timezoneSelect.locator("option").all();
        if (options.length > 1) {
            const secondOption = await options[1].getAttribute("value");
            if (secondOption && secondOption !== initialValue) {
                await timezoneSelect.selectOption(secondOption);

                // 確認值已更新
                const newValue = await timezoneSelect.inputValue();
                expect(newValue).toBe(secondOption);
            }
        }
    });

    test("重置按鈕應該恢復原始設定", async ({ page }) => {
        const siteName = page.locator("#site-name");
        const resetBtn = page.locator("#reset-btn");

        // 等待設定載入
        await page.waitForTimeout(1000);

        // 記錄原始值
        const originalValue = await siteName.inputValue();

        // 修改值
        await siteName.clear();
        await siteName.fill("臨時修改的名稱");

        // 點擊重置
        await resetBtn.click();
        await page.waitForTimeout(500);

        // 確認已恢復原始值
        const restoredValue = await siteName.inputValue();
        expect(restoredValue).toBe(originalValue);
    });

    test("應該能夠成功儲存設定", async ({ page }) => {
        const siteName = page.locator("#site-name");
        const saveBtn = page.locator("#save-btn");

        // 等待設定載入
        await page.waitForTimeout(1000);

        // 修改設定
        const testName = `測試網站 ${Date.now()}`;
        await siteName.clear();
        await siteName.fill(testName);

        // 監聽 API 請求
        let apiCalled = false;
        page.on("response", (response) => {
            if (
                response.url().includes("/api/settings") &&
                response.request().method() === "PUT"
            ) {
                apiCalled = true;
            }
        });

        // 點擊儲存
        await saveBtn.click();

        // 等待儲存完成
        await page.waitForTimeout(2000);

        // 確認 API 被呼叫
        expect(apiCalled).toBeTruthy();

        // 檢查成功訊息
        const successFeedback = page.locator("#settings-save-feedback");
        await expect(successFeedback).toBeVisible({ timeout: 5000 });
        await expect(successFeedback).toContainText(/設定已儲存.*成功/);
    });

    test("儲存後重新載入應該保留設定", async ({ page }) => {
        const siteName = page.locator("#site-name");
        const saveBtn = page.locator("#save-btn");

        // 等待設定載入
        await page.waitForTimeout(1000);

        // 修改並儲存設定
        const testName = `重載測試 ${Date.now()}`;
        await siteName.clear();
        await siteName.fill(testName);

        // 等待保存完成
        await Promise.all([
            page.waitForResponse(
                (resp) =>
                    resp.url().includes("/api/settings") &&
                    resp.request().method() === "PUT"
            ),
            page.waitForResponse(
                (resp) =>
                    resp.url().includes("/api/settings") &&
                    resp.request().method() === "GET"
            ),
            saveBtn.click(),
        ]);
        await page.waitForTimeout(500);

        // 重新載入頁面
        await page.reload();
        await page.waitForLoadState("networkidle");
        await page.waitForTimeout(1000);

        // 確認設定保留
        const loadedValue = await page.locator("#site-name").inputValue();
        expect(loadedValue).toBe(testName);
    });

    test("數字輸入欄位應該驗證範圍", async ({ page }) => {
        const postsPerPage = page.locator("#posts-per-page");

        // 檢查 min 和 max 屬性
        const min = await postsPerPage.getAttribute("min");
        const max = await postsPerPage.getAttribute("max");

        expect(parseInt(min || "0")).toBe(5);
        expect(parseInt(max || "0")).toBe(50);
    });

    test("應該正確處理空值", async ({ page }) => {
        const siteDesc = page.locator("#site-description");
        const saveBtn = page.locator("#save-btn");

        // 等待設定載入
        await page.waitForTimeout(1000);

        // 清空網站描述
        await siteDesc.clear();

        // 儲存（應該不會報錯）
        await saveBtn.click();
        await page.waitForTimeout(2000);

        // 檢查沒有錯誤訊息
        const errorToast = page.locator("text=/錯誤|失敗|Error/i");
        const errorCount = await errorToast.count();
        expect(errorCount).toBe(0);
    });

    test("切換多個開關應該正常運作", async ({ page }) => {
        const enableComments = page.locator("#enable-comments");
        const enableRegistration = page.locator("#enable-registration");

        // 等待設定載入
        await expect(enableComments).toBeVisible();
        await expect(enableRegistration).toBeVisible();
        await page.waitForTimeout(2000);

        // 記錄初始狀態
        const commentsInitial = await enableComments.isChecked();
        const registrationInitial = await enableRegistration.isChecked();

        // 透過點擊父層 label 來切換
        const commentsLabel = page.locator("label:has(#enable-comments)");
        const registrationLabel = page.locator(
            "label:has(#enable-registration)"
        );

        await commentsLabel.click();
        await page.waitForTimeout(300);
        await registrationLabel.click();
        await page.waitForTimeout(300);

        // 確認狀態已改變
        expect(await enableComments.isChecked()).toBe(!commentsInitial);
        expect(await enableRegistration.isChecked()).toBe(!registrationInitial);
    });

    test("上傳檔案大小應該正確轉換", async ({ page }) => {
        const maxUploadSize = page.locator("#max-upload-size");

        // 等待設定載入
        await expect(maxUploadSize).toBeVisible();
        await page.waitForTimeout(3000);

        // 取得當前值（應該是 MB）
        const currentValue = await maxUploadSize.inputValue();

        // 如果沒有值，跳過這個測試
        if (!currentValue || currentValue === "") {
            test.skip();
            return;
        }

        const mbValue = parseInt(currentValue);

        // 值應該是合理的（1-100 MB）
        expect(mbValue).toBeGreaterThanOrEqual(1);
        expect(mbValue).toBeLessThanOrEqual(100);
    });

    test("頁面載入時不應該有 JavaScript 錯誤", async ({ page }) => {
        const errors = [];
        page.on("pageerror", (error) => errors.push(error.message));
        page.on("console", (msg) => {
            if (msg.type() === "error") {
                errors.push(msg.text());
            }
        });

        // 重新載入頁面
        await page.reload();
        await page.waitForLoadState("networkidle");
        await page.waitForTimeout(2000);

        // 過濾無害錯誤
        const significantErrors = errors.filter(
            (err) =>
                !err.includes("favicon") &&
                !err.includes("ERR_FAILED") &&
                !err.includes("net::")
        );

        expect(significantErrors).toHaveLength(0);
    });
});
