/**
 * API 配置
 */

// 從環境變數或使用預設值
const API_BASE_URL = window.location.hostname === 'localhost' 
  ? 'http://localhost:8080/api'
  : '/api';

export const API_CONFIG = {
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
};

console.log('API Config:', API_CONFIG);
