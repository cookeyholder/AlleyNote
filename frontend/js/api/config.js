/**
 * API 配置
 * 
 * 注意：當前使用 API 無版本號端點
 * - 開發環境: http://localhost:8080/api
 * - 生產環境: /api
 */

// 從環境變數或使用預設值
const API_BASE_URL = window.location.hostname === 'localhost' 
  ? 'http://localhost:8080/api'
  : '/api';

export const API_CONFIG = {
  baseURL: API_BASE_URL,
  version: '',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
};

console.log('API Config:', API_CONFIG);
