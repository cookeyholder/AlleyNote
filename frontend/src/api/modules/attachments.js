import apiClient from '../client.js';
import { API_ENDPOINTS } from '../config.js';

/**
 * 附件 API
 */
export const attachmentsAPI = {
  /**
   * 上傳檔案
   */
  async upload(file, onProgress = null) {
    const formData = new FormData();
    formData.append('file', file);

    const config = {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    };

    if (onProgress) {
      config.onUploadProgress = (progressEvent) => {
        const percentCompleted = Math.round(
          (progressEvent.loaded * 100) / progressEvent.total
        );
        onProgress(percentCompleted);
      };
    }

    const response = await apiClient.post(
      API_ENDPOINTS.ATTACHMENTS.UPLOAD,
      formData,
      config
    );
    
    return response.data;
  },

  /**
   * 刪除檔案
   */
  async delete(id) {
    await apiClient.delete(API_ENDPOINTS.ATTACHMENTS.DELETE(id));
  },
};
