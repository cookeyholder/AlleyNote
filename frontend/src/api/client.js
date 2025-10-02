import axios from 'axios';
import { API_CONFIG } from './config.js';
import {
  requestInterceptor,
  requestErrorInterceptor,
} from './interceptors/request.js';
import {
  responseInterceptor,
  responseErrorInterceptor,
} from './interceptors/response.js';

/**
 * 建立 Axios 實例
 */
const apiClient = axios.create({
  baseURL: API_CONFIG.baseURL,
  timeout: API_CONFIG.timeout,
  headers: API_CONFIG.headers,
  withCredentials: API_CONFIG.withCredentials,
});

/**
 * 註冊請求攔截器
 */
apiClient.interceptors.request.use(requestInterceptor, requestErrorInterceptor);

/**
 * 註冊回應攔截器
 */
apiClient.interceptors.response.use(responseInterceptor, responseErrorInterceptor);

export default apiClient;
