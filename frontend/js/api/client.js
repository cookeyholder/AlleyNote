/**
 * API 客戶端封裝
 * 提供統一的 HTTP 請求介面，支援認證、錯誤處理等功能
 */

import { API_CONFIG } from "./config.js";
import { storage } from "../utils/storage.js";
import { notification } from "../utils/notification.js";

const DEFAULT_TIMEOUT = 30000;
const DEFAULT_PAGE_SIZE = 20;
const MAX_RETRIES = 3;

export class ApiError extends Error {
  constructor(message, status, data = null) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.data = data;
  }
}

class ApiClient {
  constructor(config = {}) {
    this.baseURL = config.baseURL || API_CONFIG.baseURL;
    this.timeout = config.timeout || DEFAULT_TIMEOUT;
    this.headers = { ...API_CONFIG.headers, ...config.headers };
    this.withCredentials = config.withCredentials ?? API_CONFIG.withCredentials;
    this._refreshing = false;
    this._refreshPromise = null;
  }

  getAuthToken() {
    const cookieToken = this.getCookie("access_token");
    if (cookieToken) return cookieToken;
    const token = storage.get("access_token");
    return typeof token === "string" ? token : null;
  }

  setAuthToken(token) {
    const cookieToken = this.getCookie("access_token");
    if (cookieToken !== null) {
      return;
    }
    storage.set("access_token", token);
  }

  removeAuthToken() {
    storage.remove("access_token");
  }

  buildHeaders(customHeaders = {}, method = "GET") {
    const headers = { ...this.headers };
    const token = this.getAuthToken();

    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }

    const normalizedMethod = String(method).toUpperCase();
    const needsCsrf = ["POST", "PUT", "PATCH", "DELETE"].includes(
      normalizedMethod,
    );
    const csrfToken = needsCsrf ? this.getCsrfToken() : null;
    if (csrfToken) {
      headers["X-CSRF-TOKEN"] = csrfToken;
    }

    Object.assign(headers, customHeaders);

    return headers;
  }

  getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  getMetaContent(name) {
    const metaTag = document.querySelector(`meta[name="${name}"]`);

    return metaTag?.getAttribute("content") || null;
  }

  getCsrfToken() {
    return this.getCookie("csrf_token") || this.getMetaContent("csrf-token");
  }

  async handleResponse(response) {
    const contentType = response.headers.get("content-type");

    if (contentType && contentType.includes("application/json")) {
      const data = await response.json();

      if (!response.ok) {
        throw new ApiError(
          data.message || data.error || `HTTP ${response.status}`,
          response.status,
          data,
        );
      }

      return data;
    }

    if (!response.ok) {
      const text = await response.text();
      throw new ApiError(text || `HTTP ${response.status}`, response.status);
    }

    return response;
  }

  async handleError(error, originalRequest = null) {
    const isSilent = !!originalRequest?.options?.silent;

    if (error instanceof ApiError) {
      if (
        error.status === 401 &&
        originalRequest &&
        !originalRequest.options?._retry
      ) {
        try {
          originalRequest.options = originalRequest.options || {};
          originalRequest.options._retry = true;

          if (this._refreshing && this._refreshPromise) {
            await this._refreshPromise;
            return this.request(originalRequest.url, originalRequest.options);
          }

          this._refreshing = true;

          const hasRefreshCookie = this.getCookie("auth_mode") === "cookie";
          const refreshToken = storage.get("refresh_token");
          const refreshPayload =
            typeof refreshToken === "string" && refreshToken
              ? { refresh_token: refreshToken }
              : {};

          if (hasRefreshCookie || refreshToken || this.withCredentials) {
            this._refreshPromise = fetch(`${this.baseURL}/auth/refresh`, {
              method: "POST",
              headers: this.buildHeaders({}, "POST"),
              credentials: this.withCredentials ? "include" : "same-origin",
              body: JSON.stringify(refreshPayload),
            });

            const response = await this._refreshPromise;

            if (response.ok) {
              const data = await response.json();
              if (data.success && data.access_token) {
                this.setAuthToken(data.access_token);
                if (data.refresh_token) {
                  storage.set("refresh_token", data.refresh_token);
                }

                this._refreshing = false;
                this._refreshPromise = null;

                return this.request(
                  originalRequest.url,
                  originalRequest.options,
                );
              }
            }
          }

          this._refreshing = false;
          this._refreshPromise = null;
        } catch (refreshError) {
          this._refreshing = false;
          this._refreshPromise = null;
        }

        this.removeAuthToken();
        storage.remove("refresh_token");
        window.dispatchEvent(new CustomEvent("auth:logout"));
      } else if (error.status === 401) {
        this.removeAuthToken();
        storage.remove("refresh_token");
        window.dispatchEvent(new CustomEvent("auth:logout"));
      }

      if (!error.silent && error.status === 401) {
      } else if (!error.silent) {
        notification.error(error.message);
      }

      throw error;
    }

    if (error.name === "TypeError" && error.message === "Failed to fetch") {
      const networkError = new ApiError("網路連線失敗，請檢查您的網路設定", 0);
      if (!isSilent) {
        notification.error(networkError.message);
      }
      throw networkError;
    }

    if (error.name === "AbortError") {
      const timeoutError = new ApiError("請求逾時，請稍後再試", 0);
      if (!isSilent) {
        notification.error(timeoutError.message);
      }
      throw timeoutError;
    }

    const unknownError = new ApiError(error.message || "發生未知錯誤", 0);
    if (!isSilent) {
      notification.error(unknownError.message);
    }
    throw unknownError;
  }

  async request(url, options = {}) {
    const {
      method = "GET",
      headers = {},
      body,
      params,
      timeout = this.timeout,
      silent = false,
    } = options;

    try {
      let fullUrl = `${this.baseURL}${url}`;

      if (params) {
        const searchParams = new URLSearchParams();
        Object.keys(params).forEach((key) => {
          if (params[key] !== null && params[key] !== undefined) {
            searchParams.append(key, params[key]);
          }
        });
        const queryString = searchParams.toString();
        if (queryString) {
          fullUrl += `?${queryString}`;
        }
      }

      const fetchOptions = {
        method,
        headers: this.buildHeaders(headers, method),
        credentials: this.withCredentials ? "include" : "same-origin",
      };

      if (body) {
        if (body instanceof FormData) {
          fetchOptions.body = body;
          delete fetchOptions.headers["Content-Type"];
        } else if (typeof body === "object") {
          fetchOptions.body = JSON.stringify(body);
        } else {
          fetchOptions.body = body;
        }
      }

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);
      fetchOptions.signal = controller.signal;

      const response = await fetch(fullUrl, fetchOptions);
      clearTimeout(timeoutId);

      return await this.handleResponse(response);
    } catch (error) {
      error.silent = silent;
      return this.handleError(error, { url, options });
    }
  }

  get(url, options = {}) {
    return this.request(url, { ...options, method: "GET" });
  }

  post(url, body, options = {}) {
    return this.request(url, { ...options, method: "POST", body });
  }

  put(url, body, options = {}) {
    return this.request(url, { ...options, method: "PUT", body });
  }

  patch(url, body, options = {}) {
    return this.request(url, { ...options, method: "PATCH", body });
  }

  delete(url, options = {}) {
    return this.request(url, { ...options, method: "DELETE" });
  }
}

export const apiClient = new ApiClient();

export default ApiClient;
