/**
 * API 配置
 *
 * 注意：當前使用 API 無版本號端點
 * - 開發環境: http://localhost:8080/api
 * - 生產環境: /api
 */

// 從環境變數或使用預設值
const runtimeConfiguredBaseUrl =
    window.__APP_API_BASE_URL ||
    window.localStorage?.getItem("app:apiBaseUrl") ||
    null;

const sameOriginBaseUrl = `${window.location.origin}/api`;

const candidateBaseUrls = [
    runtimeConfiguredBaseUrl,
    window.location.hostname === "localhost"
        ? "http://localhost:8080/api"
        : null,
    sameOriginBaseUrl,
    "/api",
].filter((value, index, array) =>
    typeof value === "string" && value.length > 0
        ? array.indexOf(value) === index
        : false
);

const API_BASE_URL = candidateBaseUrls[0] || "/api";
const FALLBACK_API_BASE_URLS = candidateBaseUrls.slice(1);

export const API_CONFIG = {
    baseURL: API_BASE_URL,
    fallbackBaseUrls: FALLBACK_API_BASE_URLS,
    version: "",
    timeout: 30000,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    withCredentials: true,
};

console.log("API Config:", API_CONFIG);
