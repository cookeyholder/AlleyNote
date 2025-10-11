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
   * 將時間轉換為 datetime-local 輸入格式
   * 
   * @param {string} utcTimeString - UTC 時間字串
   * @returns {string} YYYY-MM-DDTHH:MM 格式
   */
  async toDateTimeLocalFormat(utcTimeString) {
    if (!utcTimeString) {
      // 返回當前時間
      const now = new Date();
      return this.dateToLocalFormat(now);
    }

    try {
      const utcDate = new Date(utcTimeString);
      const timezone = await this.getSiteTimezone();
      const offsetHours = this.getTimezoneOffsetHours(timezone);
      const siteDate = new Date(utcDate.getTime() + offsetHours * 3600000);

      return this.dateToLocalFormat(siteDate);
    } catch (error) {
      console.error('格式轉換錯誤:', error);
      return '';
    }
  }

  /**
   * Date 對象轉 datetime-local 格式
   */
  dateToLocalFormat(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }

  /**
   * 從 datetime-local 格式轉換為 UTC
   */
  async fromDateTimeLocalFormat(localTimeString) {
    if (!localTimeString) return '';

    try {
      // datetime-local 格式: YYYY-MM-DDTHH:MM
      const siteDate = new Date(localTimeString);
      return await this.siteTimezoneToUtc(siteDate);
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
  }
}

// 匯出單例
export const timezoneUtils = new TimezoneUtils();
export default timezoneUtils;
