/**
 * 安全工具函式
 */

/**
 * HTML 跳脫函式，防止 XSS 攻擊
 * @param {string} text - 需要跳脫的文字
 * @returns {string} 跳脫後的文字
 */
export function escapeHtml(text) {
  if (text === null || text === undefined) return "";
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return String(text).replace(/[&<>"']/g, (m) => map[m]);
}

/**
 * 安全標籤模板字串，自動跳脫所有變數
 * 使用方式：safeHTML`<div>${userInput}</div>`
 */
export function safeHTML(strings, ...values) {
  return strings.reduce((result, str, i) => {
    const value = i < values.length ? escapeHtml(String(values[i] ?? "")) : "";
    return result + str + value;
  }, "");
}
