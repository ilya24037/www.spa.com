import axios from 'axios'
import type { 
  VerificationData, 
  VerificationInstructions, 
  VerificationUploadResponse 
} from '../model/types'

/**
 * API для работы с верификацией объявлений
 */
export const verificationApi = {
  /**
   * Загрузить проверочное фото
   */
  async uploadPhoto(adId: number, file: File): Promise<VerificationUploadResponse> {
    const formData = new FormData()
    formData.append('photo', file)
    
    const response = await axios.post(
      `/api/ads/${adId}/verification/photo`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )
    
    return response.data
  },
  
  /**
   * Загрузить проверочное видео
   */
  async uploadVideo(adId: number, file: File): Promise<VerificationUploadResponse> {
    const formData = new FormData()
    formData.append('video', file)
    
    const response = await axios.post(
      `/api/ads/${adId}/verification/video`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )
    
    return response.data
  },
  
  /**
   * Получить статус верификации
   */
  async getStatus(adId: number): Promise<VerificationData> {
    const response = await axios.get(`/api/ads/${adId}/verification/status`)
    return response.data
  },
  
  /**
   * Удалить файлы верификации
   */
  async deleteFiles(adId: number): Promise<void> {
    await axios.delete(`/api/ads/${adId}/verification/photo`)
  },
  
  /**
   * Получить инструкции для верификации
   */
  async getInstructions(): Promise<VerificationInstructions> {
    const response = await axios.get('/api/verification/instructions')
    return response.data.instructions
  }
}