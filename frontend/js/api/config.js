/**
 * API 配置
 *
 * 統一使用同源 `/api`：
 * - Docker Nginx（:3000）由 Nginx 反向代理到 API
 * - DevContainer live-server（:3000）由 --proxy 轉發到後端
 */

const API_BASE_URL = "/api";

export const API_CONFIG = {
  baseURL: API_BASE_URL,
  version: "",
  timeout: 30000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  withCredentials: true,
};
