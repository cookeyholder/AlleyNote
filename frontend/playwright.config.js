import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright 配置
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './src/tests/e2e',
  
  /* 並行執行測試 */
  fullyParallel: true,
  
  /* CI 環境設定 */
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  
  /* Reporter */
  reporter: 'html',
  
  /* 共用設定 */
  use: {
    /* 基礎 URL */
    baseURL: 'http://localhost:5173',
    
    /* 截圖設定 */
    screenshot: 'only-on-failure',
    
    /* 影片設定 */
    video: 'retain-on-failure',
    
    /* 追蹤設定 */
    trace: 'on-first-retry',
  },

  /* 測試專案 - 不同瀏覽器 */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    /* 行動裝置測試 */
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  /* 在執行測試前啟動開發伺服器 */
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
  },
});
