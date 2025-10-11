/**
 * API 配置
 * 
 * 注意：使用 API v1 版本
 * - 開發環境: http://localhost:8080/api/v1
 * - 生產環境: /api/v1
 * 
 * 舊的無版本號端點 (/api/) 將在 2026-01-01 廢棄
 */

// API 版本
const API_VERSION = 'v1';

// 從環境變數或使用預設值
const API_BASE_URL = window.location.hostname === 'localhost' 
  ? `http://localhost:8080/api/${API_VERSION}`
  : `/api/${API_VERSION}`;

export const API_CONFIG = {
  baseURL: API_BASE_URL,
  version: API_VERSION,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
};

console.log('API Config:', API_CONFIG);
