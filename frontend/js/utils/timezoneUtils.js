/**
 * 時區處理工具模組
 * 
 * 負責前端的時區轉換和格式化
 */

import { apiClient } from '../api/client.js';

class TimezoneUtils {
  constructor() {
    this.siteTimezone = null;
    this.timezoneOffset = null;
    this.timezoneInfo = null;
  }

  /**
   * 獲取網站時區設定
   */
  async getSiteTimezone() {
    if (this.siteTimezone) {
      return this.siteTimezone;
    }

    try {
      const response = await apiClient.get('/settings/site_timezone');
      if (response.success && response.data) {
        this.siteTimezone = response.data.value || 'Asia/Taipei';
        return this.siteTimezone;
      }
    } catch (error) {
      console.warn('無法獲取網站時區設定，使用預設值', error);
    }

    // 預設使用 Asia/Taipei
    this.siteTimezone = 'Asia/Taipei';
    return this.siteTimezone;
  }

  /**
   * 獲取時區資訊（包含所有時區列表）
   */
  async getTimezoneInfo() {
    if (this.timezoneInfo) {
      return this.timezoneInfo;
    }

    try {
      const response = await apiClient.get('/settings/timezone/info');
      if (response.success && response.data) {
        this.timezoneInfo = response.data;
        return this.timezoneInfo;
      }
    } catch (error) {
      console.warn('無法獲取時區資訊', error);
    }

    return null;
  }

  /**
   * 獲取時區偏移量（小時）
   */
  getTimezoneOffsetHours(timezone = 'Asia/Taipei') {
    const offsetMap = {
      'UTC': 0,
      'Asia/Taipei': 8,
      'Asia/Tokyo': 9,
      'Asia/Shanghai': 8,
      'Asia/Hong_Kong': 8,
      'Asia/Singapore': 8,
      'America/New_York': -5, // 標準時間
      'America/Los_Angeles': -8, // 標準時間
      'America/Chicago': -6, // 標準時間
      'Europe/London': 0, // 標準時間
      'Europe/Paris': 1, // 標準時間
      'Europe/Berlin': 1, // 標準時間
      'Australia/Sydney': 10, // 標準時間
    };

    return offsetMap[timezone] || 8;
  }

  /**
   * 將 UTC 時間轉換為網站時區顯示
   * 
   * @param {string} utcTimeString - UTC 時間字串（ISO 8601 或 RFC3339 格式）
   * @param {string} format - 格式化選項 ('datetime', 'date', 'time', 'full')
   * @returns {string} 格式化後的時間字串
   */
  async utcToSiteTimezone(utcTimeString, format = 'datetime') {
    if (!utcTimeString) return '';

    try {
      // 解析 UTC 時間
      const utcDate = new Date(utcTimeString);
      if (isNaN(utcDate.getTime())) {
        console.warn('無效的時間格式:', utcTimeString);
        return utcTimeString;
      }

      // 獲取網站時區
      const timezone = await this.getSiteTimezone();
      const offsetHours = this.getTimezoneOffsetHours(timezone);

      // 調整時區（簡化版本，不處理夏令時）
      const siteDate = new Date(utcDate.getTime() + offsetHours * 3600000);

      // 根據格式返回
      return this.formatDateTime(siteDate, format);
    } catch (error) {
      console.error('時區轉換錯誤:', error);
      return utcTimeString;
    }
  }

  /**
   * 將網站時區時間轉換為 UTC
   * 
   * @param {string|Date} siteTimeString - 網站時區時間
   * @returns {string} RFC3339 格式的 UTC 時間
   */
  async siteTimezoneToUtc(siteTimeString) {
    if (!siteTimeString) return '';

    try {
      // 解析時間（假設輸入是網站時區）
      const siteDate = typeof siteTimeString === 'string' 
        ? new Date(siteTimeString) 
        : siteTimeString;

      if (isNaN(siteDate.getTime())) {
        console.warn('無效的時間格式:', siteTimeString);
        return '';
      }

      // 獲取網站時區偏移
      const timezone = await this.getSiteTimezone();
      const offsetHours = this.getTimezoneOffsetHours(timezone);

      // 轉換為 UTC
      const utcDate = new Date(siteDate.getTime() - offsetHours * 3600000);

      // 返回 RFC3339 格式
      return utcDate.toISOString();
    } catch (error) {
      console.error('時區轉換錯誤:', error);
      return '';
    }
  }

  /**
   * 格式化時間顯示
   * 
   * @param {Date} date - Date 對象
   * @param {string} format - 格式 ('datetime', 'date', 'time', 'full')
   * @returns {string} 格式化後的時間字串
   */
  formatDateTime(date, format = 'datetime') {
    const options = {
      date: {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      },
      time: {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      },
      datetime: {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      },
      full: {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
      },
    };

    const formatOptions = options[format] || options.datetime;

    if (format === 'date') {
      return date.toLocaleDateString('zh-TW', formatOptions);
    } else if (format === 'time') {
      return date.toLocaleTimeString('zh-TW', formatOptions);
    } else {
      const dateStr = date.toLocaleDateString('zh-TW', options.date);
      const timeStr = date.toLocaleTimeString('zh-TW', options.time);
      return `${dateStr} ${timeStr}`;
    }
  }

  /**
   * 將 UTC 時間轉換為 datetime-local 輸入格式（網站時區）
   * 
   * @param {string} utcTimeString - UTC 時間字串 (ISO 8601 / RFC3339 或資料庫格式)
   * @returns {string} YYYY-MM-DDTHH:MM 格式（網站時區）
   */
  async toDateTimeLocalFormat(utcTimeString) {
    if (!utcTimeString) {
      // 如果沒有提供時間，返回當前時間（網站時區）
      const timezone = await this.getSiteTimezone();
      const offsetHours = this.getTimezoneOffsetHours(timezone);
      const now = new Date();
      const siteNow = new Date(now.getTime() + offsetHours * 3600000);
      return this.dateToLocalFormat(siteNow);
    }

    try {
      let utcDate;
      
      // 處理不同的時間格式
      if (utcTimeString.includes('T')) {
        // ISO 8601 / RFC3339 format
        utcDate = new Date(utcTimeString);
      } else {
        // 資料庫格式：YYYY-MM-DD HH:MM:SS (假設為 UTC)
        // 明確指定為 UTC 時間
        const utcString = utcTimeString.replace(' ', 'T') + 'Z';
        utcDate = new Date(utcString);
      }
      
      if (isNaN(utcDate.getTime())) {
        console.warn('無效的 UTC 時間:', utcTimeString);
        return '';
      }

      // 獲取網站時區偏移
      const timezone = await this.getSiteTimezone();
      const offsetHours = this.getTimezoneOffsetHours(timezone);
      
      // UTC 時間加上偏移 = 網站時區時間
      // 例如：UTC 07:30 + 8小時 = 網站時區 15:30
      const siteDate = new Date(utcDate.getTime() + offsetHours * 3600000);

      // 格式化為 datetime-local 格式
      return this.dateToLocalFormat(siteDate);
    } catch (error) {
      console.error('格式轉換錯誤:', error);
      return '';
    }
  }

  /**
   * Date 對象轉 datetime-local 格式
   * 
   * 注意：傳入的 Date 物件應該已經是網站時區調整後的時間
   * 我們只需要提取其 UTC 表示的年月日時分（這些值實際代表網站時區）
   * 
   * @param {Date} date - 已調整為網站時區的 Date 物件
   * @returns {string} YYYY-MM-DDTHH:MM 格式
   */
  dateToLocalFormat(date) {
    // 使用 UTC 方法提取值，因為我們已經將時區偏移加入到時間中
    const year = date.getUTCFullYear();
    const month = String(date.getUTCMonth() + 1).padStart(2, '0');
    const day = String(date.getUTCDate()).padStart(2, '0');
    const hours = String(date.getUTCHours()).padStart(2, '0');
    const minutes = String(date.getUTCMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }

  /**
   * 從 datetime-local 格式轉換為 UTC RFC3339 格式
   * 
   * datetime-local 輸入的值代表「網站時區」的時間
   * 需要轉換為 UTC ISO 8601 / RFC3339 格式
   * 
   * @param {string} localTimeString - datetime-local 格式 (YYYY-MM-DDTHH:MM)
   * @returns {string} UTC RFC3339 格式的時間字串
   */
  async fromDateTimeLocalFormat(localTimeString) {
    if (!localTimeString) return '';

    try {
      // datetime-local 格式: YYYY-MM-DDTHH:MM
      // 這個值代表的是「網站時區」的時間
      const [datePart, timePart] = localTimeString.split('T');
      const [year, month, day] = datePart.split('-').map(Number);
      const [hours, minutes] = timePart.split(':').map(Number);

      // 獲取網站時區偏移
      const timezone = await this.getSiteTimezone();
      const offsetHours = this.getTimezoneOffsetHours(timezone);

      // 建立 UTC 時間：網站時區的時間減去偏移 = UTC 時間
      // 例如：網站時區 15:30 (UTC+8) = UTC 07:30
      const utcDate = new Date(Date.UTC(
        year,
        month - 1,  // JavaScript 月份從 0 開始
        day,
        hours - offsetHours,  // 減去時區偏移得到 UTC 小時
        minutes
      ));

      // 返回 RFC3339 / ISO 8601 格式
      return utcDate.toISOString();
    } catch (error) {
      console.error('格式轉換錯誤:', error);
      return '';
    }
  }

  /**
   * 獲取常用時區列表
   */
  getCommonTimezones() {
    return [
      { value: 'UTC', label: 'UTC (協調世界時)', offset: '+00:00' },
      { value: 'Asia/Taipei', label: 'Asia/Taipei (台北時間 UTC+8)', offset: '+08:00' },
      { value: 'Asia/Tokyo', label: 'Asia/Tokyo (東京時間 UTC+9)', offset: '+09:00' },
      { value: 'Asia/Shanghai', label: 'Asia/Shanghai (上海時間 UTC+8)', offset: '+08:00' },
      { value: 'Asia/Hong_Kong', label: 'Asia/Hong_Kong (香港時間 UTC+8)', offset: '+08:00' },
      { value: 'Asia/Singapore', label: 'Asia/Singapore (新加坡時間 UTC+8)', offset: '+08:00' },
      { value: 'America/New_York', label: 'America/New_York (紐約時間)', offset: '-05:00/-04:00' },
      { value: 'America/Los_Angeles', label: 'America/Los_Angeles (洛杉磯時間)', offset: '-08:00/-07:00' },
      { value: 'America/Chicago', label: 'America/Chicago (芝加哥時間)', offset: '-06:00/-05:00' },
      { value: 'Europe/London', label: 'Europe/London (倫敦時間)', offset: '+00:00/+01:00' },
      { value: 'Europe/Paris', label: 'Europe/Paris (巴黎時間)', offset: '+01:00/+02:00' },
      { value: 'Europe/Berlin', label: 'Europe/Berlin (柏林時間)', offset: '+01:00/+02:00' },
      { value: 'Australia/Sydney', label: 'Australia/Sydney (雪梨時間)', offset: '+10:00/+11:00' },
    ];
  }

  /**
   * 清除快取
   */
  clearCache() {
    this.siteTimezone = null;
    this.timezoneOffset = null;
    this.timezoneInfo = null;
  }
}

// 匯出單例
export const timezoneUtils = new TimezoneUtils();
export default timezoneUtils;
