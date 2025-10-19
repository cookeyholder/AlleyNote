/**
 * 角色管理測試
 *
 * 測試角色管理頁面的各項功能：
 * - 檢視角色列表
 * - 新增角色
 * - 編輯角色權限
 * - 刪除角色
 */

import { test, expect } from "@playwright/test";

const BASE_URL = process.env.BASE_URL || "http://localhost:3000";
const TEST_EMAIL = "admin@example.com";
const TEST_PASSWORD = "password";

// 測試前登入
test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[type="email"]', TEST_EMAIL);
    await page.fill('input[type="password"]', TEST_PASSWORD);
    await page.click('button:has-text("登入")');

    // 等待登入成功並導航到儀表板
    await page.waitForURL(`${BASE_URL}/admin/dashboard`);

    // 導航到角色管理頁面
    await page.click('a:has-text("角色管理")');
    await page.waitForURL(`${BASE_URL}/admin/roles`);
});

test.describe("角色管理頁面", () => {
    test("應該顯示角色列表", async ({ page }) => {
        // 檢查頁面標題 (使用更精確的選擇器)
        await expect(
            page.locator('main h1:has-text("角色管理")')
        ).toBeVisible();

        // 檢查是否有新增角色按鈕
        await expect(page.locator('button:has-text("新增角色")')).toBeVisible();

        // 檢查是否有角色列表標題
        await expect(page.locator('h2:has-text("角色列表")')).toBeVisible();

        // 檢查是否有權限設定標題
        await expect(page.locator('h2:has-text("權限設定")')).toBeVisible();

        // 檢查至少有一個角色
        const roleItems = page.locator(".role-item");
        await expect(roleItems).not.toHaveCount(0);
    });

    test("應該成功新增角色", async ({ page }) => {
        const timestamp = Date.now();
        const roleName = `test_role_${timestamp}`;
        const displayName = `測試角色 ${timestamp}`;
        const description = "這是一個測試用的角色";

        // 點擊新增角色按鈕
        await page.click('button:has-text("新增角色")');

        // 等待 Modal 出現
        await expect(page.locator('h3:has-text("新增角色")')).toBeVisible();

        // 填寫表單
        await page.fill('input[name="name"]', roleName);
        await page.fill('input[name="display_name"]', displayName);
        await page.fill('textarea[name="description"]', description);

        // 提交表單,等待建立 API 響應
        const createResponsePromise = page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/roles") &&
                resp.request().method() === "POST"
        );

        await page.click('button[type="submit"]:has-text("新增角色")');

        const createResponse = await createResponsePromise;
        expect(createResponse.status()).toBe(201);

        // 等待 Modal 關閉
        await expect(page.locator('h3:has-text("新增角色")')).not.toBeVisible();

        // 等待角色列表重新載入完成
        await page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/roles") &&
                resp.request().method() === "GET"
        );
        await page.waitForTimeout(1000);

        // 檢查新角色是否出現在列表中
        await expect(page.locator(`h3:has-text("${displayName}")`)).toBeVisible(
            {
                timeout: 5000,
            }
        );

        // 清理：刪除測試角色
        await page
            .locator(
                `.role-item:has-text("${displayName}") button:has-text("刪除")`
            )
            .click();
        await page.waitForTimeout(500); // 等待 confirm dialog
    });

    test("應該能選擇角色並顯示權限設定", async ({ page }) => {
        // 選擇一個角色（選擇第一個非超級管理員的角色）
        const roleItem = page.locator(".role-item").nth(1);
        await roleItem.click();

        // 等待權限設定載入
        await page.waitForTimeout(1000);

        // 檢查權限設定區域是否顯示角色資訊
        const permissionsContainer = page.locator("#permissionsContainer");
        await expect(permissionsContainer.locator("h3").first()).toBeVisible();

        // 檢查是否有權限 checkbox
        const permissionCheckboxes = permissionsContainer.locator(
            ".permission-checkbox"
        );
        await expect(permissionCheckboxes.first()).toBeVisible();

        // 檢查是否有儲存按鈕
        await expect(
            permissionsContainer.locator('button:has-text("儲存權限")')
        ).toBeVisible();

        // 檢查是否有取消按鈕
        await expect(
            permissionsContainer.locator('button:has-text("取消")')
        ).toBeVisible();
    });

    test("應該能更新角色權限", async ({ page }) => {
        // 選擇管理員角色 (使用更精確的選擇器,選擇第二個角色)
        await page.locator(".role-item").nth(1).click();

        // 等待角色權限載入
        await page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/roles/") &&
                resp.request().method() === "GET" &&
                !resp.url().includes("/permissions")
        );
        await page.waitForTimeout(500);

        // 找到一個 checkbox 並記錄其狀態
        const firstCheckbox = page.locator(".permission-checkbox").first();
        await expect(firstCheckbox).toBeVisible();
        const wasChecked = await firstCheckbox.isChecked();

        // 切換 checkbox 狀態
        await firstCheckbox.click();
        await page.waitForTimeout(300);

        // 確認狀態已在 UI 中改變
        const immediateState = await firstCheckbox.isChecked();
        expect(immediateState).toBe(!wasChecked);

        // 點擊儲存,等待 API 響應
        const [updateResponse, getRoleResponse] = await Promise.all([
            page.waitForResponse(
                (resp) =>
                    resp.url().includes("/permissions") &&
                    resp.request().method() === "PUT"
            ),
            page.waitForResponse(
                (resp) =>
                    resp.url().includes("/api/roles/") &&
                    resp.request().method() === "GET" &&
                    !resp.url().includes("/permissions")
            ),
            page.click('button:has-text("儲存權限")'),
        ]);

        // 確認更新成功
        expect(updateResponse.status()).toBe(200);

        // 等待重新載入完成
        await page.waitForTimeout(500);

        // 驗證 checkbox 狀態已持久化
        const newCheckboxState = await firstCheckbox.isChecked();
        expect(newCheckboxState).toBe(!wasChecked);

        // 還原變更
        await firstCheckbox.click();
        await page.waitForTimeout(300);

        await Promise.all([
            page.waitForResponse(
                (resp) =>
                    resp.url().includes("/permissions") &&
                    resp.request().method() === "PUT"
            ),
            page.click('button:has-text("儲存權限")'),
        ]);
        await page.waitForTimeout(500);
    });

    test("應該能取消權限編輯", async ({ page }) => {
        // 選擇一個角色
        await page.locator(".role-item").nth(1).click();

        // 等待權限設定載入
        await page.waitForTimeout(1000);

        // 點擊取消按鈕
        await page.click('button:has-text("取消")');

        // 檢查是否回到初始狀態
        await expect(
            page.locator("text=請選擇一個角色來管理權限")
        ).toBeVisible();
    });

    test("應該能刪除角色", async ({ page }) => {
        const timestamp = Date.now();
        const roleName = `temp_role_${timestamp}`;
        const displayName = `臨時角色 ${timestamp}`;

        // 先建立一個測試角色,等待 API 響應
        await page.click('button:has-text("新增角色")');
        await page.fill('input[name="name"]', roleName);
        await page.fill('input[name="display_name"]', displayName);

        const createResponsePromise = page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/roles") &&
                resp.request().method() === "POST"
        );

        await page.click('button[type="submit"]:has-text("新增角色")');

        const createResponse = await createResponsePromise;
        expect(createResponse.status()).toBe(201);

        // 等待 Modal 關閉
        await expect(page.locator('h3:has-text("新增角色")')).not.toBeVisible();

        // 等待角色列表重新載入
        await page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/roles") &&
                resp.request().method() === "GET"
        );
        await page.waitForTimeout(1000);

        // 確認角色已建立
        await expect(page.locator(`h3:has-text("${displayName}")`)).toBeVisible(
            {
                timeout: 5000,
            }
        );

        // 設置對話框處理器
        page.on("dialog", (dialog) => dialog.accept());

        // 刪除角色,等待 API 響應
        const deleteResponsePromise = page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/roles") &&
                resp.request().method() === "DELETE"
        );

        await page
            .locator(
                `.role-item:has-text("${displayName}") button:has-text("刪除")`
            )
            .click();

        const deleteResponse = await deleteResponsePromise;
        expect(deleteResponse.status()).toBe(200);

        // 等待列表重新載入
        await page.waitForResponse(
            (resp) =>
                resp.url().includes("/api/roles") &&
                resp.request().method() === "GET"
        );
        await page.waitForTimeout(1000);

        // 檢查角色是否從列表中移除
        await expect(
            page.locator(`h3:has-text("${displayName}")`)
        ).not.toBeVisible();
    });

    test("應該顯示權限按資源分組", async ({ page }) => {
        // 選擇一個角色
        await page.locator(".role-item").nth(1).click();

        // 等待權限設定載入
        await page.waitForTimeout(1000);

        // 檢查是否有資源分組標題（例如 posts, users, roles 等）
        const permissionsContainer = page.locator("#permissionsContainer");

        // 應該至少有一個資源分組
        const resourceHeadings = permissionsContainer.locator("h3");
        const count = await resourceHeadings.count();
        expect(count).toBeGreaterThan(1); // 至少有角色名稱 + 一個資源分組
    });

    test("不應該能刪除超級管理員角色", async ({ page }) => {
        // 檢查超級管理員角色項目
        const superAdminRole = page.locator(
            '.role-item:has-text("超級管理員")'
        );

        // 超級管理員角色不應該有刪除按鈕
        await expect(
            superAdminRole.locator('button:has-text("刪除")')
        ).not.toBeVisible();
    });
});

test.describe("角色管理權限驗證", () => {
    test("新增角色時角色名稱和顯示名稱為必填", async ({ page }) => {
        // 點擊新增角色按鈕
        await page.click('button:has-text("新增角色")');

        // 不填寫任何內容，直接提交
        await page.click('button[type="submit"]:has-text("新增角色")');

        // 檢查是否有驗證提示（瀏覽器原生驗證）
        const nameInput = page.locator('input[name="name"]');
        const isInvalid = await nameInput.evaluate((el) => !el.validity.valid);
        expect(isInvalid).toBeTruthy();
    });
});
