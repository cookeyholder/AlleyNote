/**
 * API 客戶端類別
 * 負責與後端 API 進行通訊
 */
export class ApiClient {
    /**
     * @param {string} baseURL - API 基礎 URL
     */
    constructor(baseURL = '/api') {
        this.baseURL = baseURL
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    }

    /**
     * 發送 HTTP 請求
     * @param {string} endpoint - API 端點
     * @param {Object} options - 請求選項
     * @returns {Promise<Object>} API 回應
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`
        
        const config = {
            headers: {
                ...this.defaultHeaders,
                ...options.headers
            },
            ...options
        }

        try {
            const response = await fetch(url, config)
            
            if (!response.ok) {
                throw new Error(`API 錯誤: ${response.status} ${response.statusText}`)
            }
            
            return await response.json()
        } catch (error) {
            console.error('API 請求失敗:', error)
            throw error
        }
    }

    /**
     * GET 請求
     */
    async get(endpoint, params = {}) {
        const url = new URL(endpoint, this.baseURL)
        Object.keys(params).forEach(key => 
            url.searchParams.append(key, params[key])
        )
        
        return this.request(url.pathname + url.search, {
            method: 'GET'
        })
    }

    /**
     * POST 請求
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        })
    }

    /**
     * PUT 請求
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        })
    }

    /**
     * DELETE 請求
     */
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        })
    }

    /**
     * 設定認證 Token
     */
    setAuthToken(token) {
        this.defaultHeaders['Authorization'] = `Bearer ${token}`
    }

    /**
     * 移除認證 Token
     */
    removeAuthToken() {
        delete this.defaultHeaders['Authorization']
    }
}
