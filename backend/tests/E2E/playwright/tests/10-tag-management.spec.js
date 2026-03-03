// @ts-check
const { test, expect } = require("./fixtures/page-objects");

/**
 * 標籤管理功能測試套件
 */
test.describe("標籤管理功能測試", () => {
    test.beforeEach(async ({ authenticatedPage }) => {
        await authenticatedPage.goto("/admin/tags");
        await authenticatedPage.waitForTimeout(1000); // 等待資料載入
    });

    test("應該正確顯示標籤管理頁面", async ({ authenticatedPage: page }) => {
        // 檢查標題
        await expect(
            page.locator('main h1:has-text("標籤管理")')
        ).toBeVisible();

        // 檢查新增標籤按鈕
        await expect(page.locator('button:has-text("新增標籤")')).toBeVisible();
    });

    test("應該顯示標籤列表或空狀態", async ({ authenticatedPage: page }) => {
        // 檢查是否有標籤卡片或空狀態訊息
        const hasTags = (await page.locator(".grid").count()) > 0;
        const hasEmptyState =
            (await page.locator("text=尚無標籤資料").count()) > 0;

        expect(hasTags || hasEmptyState).toBeTruthy();
    });

    test("點擊新增標籤應該顯示新增對話框", async ({
        authenticatedPage: page,
    }) => {
        // 點擊新增標籤按鈕
        await page.click('button:has-text("新增標籤")');

        // 等待 modal 出現
        await page.waitForTimeout(500);

        // 檢查 modal 標題
        await expect(
            page.locator('.fixed.inset-0 h3:has-text("新增標籤")')
        ).toBeVisible();

        // 檢查表單欄位
        await expect(page.locator('input[name="name"]')).toBeVisible();
        await expect(page.locator('input[name="slug"]')).toBeVisible();
        await expect(
            page.locator('textarea[name="description"]')
        ).toBeVisible();

        // 檢查按鈕
        await expect(page.locator("#cancelModalBtn")).toBeVisible();
        await expect(
            page.locator('button[type="submit"]:has-text("新增標籤")')
        ).toBeVisible();
    });

    test("應該能夠取消新增標籤", async ({ authenticatedPage: page }) => {
        // 開啟新增對話框
        await page.click('button:has-text("新增標籤")');
        await page.waitForTimeout(500);

        // 點擊取消
        await page.click("#cancelModalBtn");
        await page.waitForTimeout(500);

        // Modal 應該關閉
        await expect(
            page.locator('.fixed.inset-0 h3:has-text("新增標籤")')
        ).not.toBeVisible();
    });

    test("新增標籤時應該驗證必填欄位", async ({ authenticatedPage: page }) => {
        // 開啟新增對話框
        await page.click('button:has-text("新增標籤")');
        await page.waitForTimeout(500);

        // 不填寫任何資料，直接提交
        await page
            .locator('button[type="submit"]:has-text("新增標籤")')
            .click();

        // 因為有 HTML5 驗證，表單不會提交
        // 檢查 modal 仍然存在
        await expect(page.locator('input[name="name"]')).toBeVisible();
    });

    test("應該能夠成功新增標籤", async ({ authenticatedPage: page }) => {
        // 開啟新增對話框
        await page.click('button:has-text("新增標籤")');

        // 等待 Modal 完全載入
        await page.waitForSelector("form#tagForm", {
            state: "visible",
            timeout: 5000,
        });
        await page.waitForTimeout(500);

        // 填寫表單
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 10000);
        const tagName = `test_tag_${timestamp}_${random}`;

        // 確認欄位存在
        await expect(page.locator('input[name="name"]')).toBeVisible();
        await page.fill('input[name="name"]', tagName);
        console.log(`填寫標籤名稱: ${tagName}`);

        // 提交表單，等待建立 API 響應
        const createResponsePromise = page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/tags") &&
                resp.request().method() === "POST"
        );

        // 監聽網路請求以查看發送的資料
        const requestPromise = page.waitForRequest(
            (req) => req.url().includes("/api/tags") && req.method() === "POST"
        );

        await page
            .locator('button[type="submit"]:has-text("新增標籤")')
            .click();

        const request = await requestPromise;
        console.log("發送的資料:", await request.postData());

        const createResponse = await createResponsePromise;

        // 確認建立成功 (標籤 API 回傳 200)
        const createStatus = createResponse.status();
        console.log(`標籤建立 API 狀態: ${createStatus}`);

        const responseText = await createResponse.text();
        console.log("API 響應內容:", responseText.substring(0, 500));

        if (createStatus !== 200 && createStatus !== 201) {
            console.error("標籤建立失敗");
        }

        expect([200, 201]).toContain(createStatus);

        // 等待列表重新載入
        const getResponsePromise = page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/tags") &&
                resp.request().method() === "GET"
        );
        const getResponse = await getResponsePromise;
        const listText = await getResponse.text();
        console.log("標籤列表 API 響應:", listText.substring(0, 500));
        await page.waitForTimeout(1000);

        // Modal 應該關閉
        await expect(
            page.locator('.fixed.inset-0 h3:has-text("新增標籤")')
        ).not.toBeVisible();

        // 頁面應該顯示新增的標籤 - 使用更明確的選擇器
        await expect(
            page
                .locator("h3.text-lg.font-semibold")
                .filter({ hasText: tagName })
        ).toBeVisible({
            timeout: 5000,
        });
    });

    test("應該能夠編輯標籤", async ({ authenticatedPage: page }) => {
        // 等待標籤列表載入完成
        await page.waitForTimeout(1000);

        // 先確保有至少一個標籤
        const tagCards = page.locator(".edit-tag-btn");
        const count = await tagCards.count();

        if (count === 0) {
            // 如果沒有標籤，先新增一個
            await page.click('button:has-text("新增標籤")');
            await page.waitForTimeout(500);
            const timestamp = Date.now();
            const random = Math.floor(Math.random() * 10000);
            await page.fill(
                'input[name="name"]',
                `測試標籤_${timestamp}_${random}`
            );

            const createResponsePromise = page.waitForResponse(
                (resp) =>
                    resp.url().includes("/api/tags") &&
                    resp.request().method() === "POST"
            );

            await page
                .locator('button[type="submit"]:has-text("新增標籤")')
                .click();

            await createResponsePromise;
            await page.waitForResponse(
                (resp) =>
                    resp.url().includes("/api/tags") &&
                    resp.request().method() === "GET"
            );
            await page.waitForTimeout(1000);
        }

        // 點擊第一個標籤的編輯按鈕
        await page.locator(".edit-tag-btn").first().click();
        await page.waitForTimeout(500);

        // 檢查編輯 modal
        await expect(
            page.locator('.fixed.inset-0 h3:has-text("編輯標籤")')
        ).toBeVisible();

        // 修改標籤名稱
        const newName = `編輯後的標籤 ${Date.now()}`;
        await page.fill('input[name="name"]', newName);

        // 提交
        await page
            .locator('button[type="submit"]:has-text("儲存變更")')
            .click();
        await page.waitForTimeout(1500);

        // 檢查成功訊息
        await expect(
            page.locator("text=標籤更新成功").or(page.locator("text=成功"))
        ).toBeVisible();

        // Modal 應該關閉
        await expect(
            page.locator('.fixed.inset-0 h3:has-text("編輯標籤")')
        ).not.toBeVisible();
    });

    test("應該能夠刪除標籤", async ({ authenticatedPage: page }) => {
        // 等待標籤列表載入完成
        await page.waitForTimeout(1000);

        // 先確保有至少一個可刪除的標籤
        const deleteButtons = page.locator(".delete-tag-btn");
        const count = await deleteButtons.count();

        if (count === 0) {
            // 如果沒有標籤，先新增一個
            await page.click('button:has-text("新增標籤")');
            await page.waitForTimeout(500);
            const timestamp = Date.now();
            const random = Math.floor(Math.random() * 10000);
            await page.fill(
                'input[name="name"]',
                `待刪除標籤_${timestamp}_${random}`
            );

            const createResponsePromise = page.waitForResponse(
                (resp) =>
                    resp.url().includes("/api/tags") &&
                    resp.request().method() === "POST"
            );

            await page
                .locator('button[type="submit"]:has-text("新增標籤")')
                .click();

            await createResponsePromise;
            await page.waitForResponse(
                (resp) =>
                    resp.url().includes("/api/tags") &&
                    resp.request().method() === "GET"
            );
            await page.waitForTimeout(1000);
        }

        // 獲取刪除前的標籤數量
        const beforeCount = await page.locator(".delete-tag-btn").count();

        // 監聽對話框
        page.on("dialog", async (dialog) => {
            expect(dialog.type()).toBe("confirm");
            await dialog.accept();
        });

        // 點擊刪除按鈕
        await page.locator(".delete-tag-btn").first().click();
        await page.waitForTimeout(1500);

        // 檢查成功訊息
        await expect(
            page.locator("text=標籤刪除成功").or(page.locator("text=成功"))
        ).toBeVisible();

        // 檢查標籤數量是否減少（或顯示空狀態）
        const afterCount = await page.locator(".delete-tag-btn").count();
        const hasEmptyState =
            (await page.locator("text=尚無標籤資料").count()) > 0;

        expect(afterCount < beforeCount || hasEmptyState).toBeTruthy();
    });
});
