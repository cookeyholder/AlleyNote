// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testDir: './tests',
  
  // 測試執行設定
  fullyParallel: false, // 循序執行，避免測試間互相影響
  forbidOnly: !!process.env.CI, // CI 環境禁止 .only
  retries: process.env.CI ? 1 : 0, // CI 環境重試 1 次（從 2 降為 1）
  workers: process.env.CI ? 1 : 1, // 使用 1 個 worker
  
  // 超時設定
  timeout: 60000, // 每個測試最長 60 秒（新增）
  
  // 報告設定
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['list'],
    ['json', { outputFile: 'test-results.json' }]
  ],
  
  // 全域設定
  use: {
    // 基礎 URL
    baseURL: 'http://localhost:3000',
    
    // 截圖設定
    screenshot: 'only-on-failure',
    
    // 錄影設定
    video: 'retain-on-failure',
    
    // 追蹤設定（用於除錯）
    trace: 'retain-on-failure',
    
    // 逾時設定（增加以減少不必要的 retry）
    actionTimeout: 15000, // 從 10秒 增加到 15秒
    navigationTimeout: 40000, // 從 30秒 增加到 40秒
  },

  // 測試專案配置
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  // 開發伺服器設定（可選）
  // webServer: {
  //   command: 'docker compose up',
  //   url: 'http://localhost:3000',
  //   reuseExistingServer: !process.env.CI,
  //   timeout: 120000,
  // },
});
