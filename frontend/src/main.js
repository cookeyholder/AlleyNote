/**
 * AlleyNote 前端應用程式主入口
 *
 * @version 1.0.0
 * @author AlleyNote Team
 */

import "./style.css";
import { ApiClient } from "./api/ApiClient.js";
import { StatisticsDashboard } from "./views/StatisticsDashboard.js";

/**
 * 應用程式主類別
 */
class App {
    constructor() {
        this.apiClient = new ApiClient();
        this.init();
    }

    /**
     * 初始化應用程式
     */
    init() {
        this.setupEventListeners();
        this.initializeStatisticsDashboard();
    }

    /**
     * 設定事件監聽器
     */
    setupEventListeners() {
        document.addEventListener("DOMContentLoaded", () => {
            console.log("AlleyNote 前端應用程式已啟動");
        });
    }

    /**
     * 載入初始資料
     */
    initializeStatisticsDashboard() {
        try {
            this.statisticsDashboard = new StatisticsDashboard({
                apiClient: this.apiClient,
            });
            this.statisticsDashboard.init();
        } catch (error) {
            console.error("初始化統計儀表板失敗:", error);
        }
    }
}

// 啟動應用程式
new App();
