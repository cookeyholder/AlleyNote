/**
 * AlleyNote 前端應用程式主入口
 * 
 * @version 1.0.0
 * @author AlleyNote Team
 */

import './style.css'
import { ApiClient } from './api/ApiClient.js'

/**
 * 應用程式主類別
 */
class App {
    constructor() {
        this.apiClient = new ApiClient()
        this.init()
    }

    /**
     * 初始化應用程式
     */
    init() {
        this.setupEventListeners()
        this.loadInitialData()
    }

    /**
     * 設定事件監聽器
     */
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('AlleyNote 前端應用程式已啟動')
        })
    }

    /**
     * 載入初始資料
     */
    async loadInitialData() {
        try {
            // 這裡可以載入初始資料
            console.log('載入初始資料...')
        } catch (error) {
            console.error('載入初始資料失敗:', error)
        }
    }
}

// 啟動應用程式
new App()
