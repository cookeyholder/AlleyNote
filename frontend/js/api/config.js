/**
 * API 配置
 *
 * 注意：當前使用 API 無版本號端點
 * - 開發環境: http://localhost:8080/api
 * - 生產環境: /api
 */

// 從環境變數或使用預設值
// 本地開發若前端由 Vite(:3000) 提供，API 走 :8080
// 若前端由 Nginx(:8081) 或其他同源網域提供，API 走同源 /api
const isLocalhost = window.location.hostname === "localhost";
const isViteDevServer = window.location.port === "3000";

const API_BASE_URL =
  isLocalhost && isViteDevServer ? "http://localhost:8080/api" : "/api";

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
