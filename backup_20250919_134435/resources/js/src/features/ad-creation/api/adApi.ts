import axios from 'axios'

// API для работы с объявлениями
export const adApi = {
  // Создание нового объявления
  async create(data: FormData) {
    const response = await axios.post('/api/ads', data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  },

  // Сохранение черновика
  async saveDraft(data: any) {
    const response = await axios.post('/api/ads/draft', data)
    return response.data
  },

  // Обновление существующего объявления
  async update(id: string | number, data: FormData) {
    const response = await axios.post(`/api/ads/${id}`, data, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-HTTP-Method-Override': 'PUT'
      }
    })
    return response.data
  },

  // Получение объявления для редактирования
  async getForEdit(id: string | number) {
    const response = await axios.get(`/api/ads/${id}/edit`)
    return response.data
  },

  // Публикация объявления
  async publish(id: string | number) {
    const response = await axios.post(`/api/ads/${id}/publish`)
    return response.data
  },

  // Загрузка фото
  async uploadPhoto(file: File) {
    const formData = new FormData()
    formData.append('photo', file)
    
    const response = await axios.post('/api/ads/upload-photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  },

  // Удаление фото
  async deletePhoto(photoId: string | number) {
    const response = await axios.delete(`/api/ads/photos/${photoId}`)
    return response.data
  },

  // Валидация полей формы
  async validateField(field: string, value: any) {
    const response = await axios.post('/api/ads/validate-field', {
      field,
      value
    })
    return response.data
  },

  // Получение доступных городов
  async getCities() {
    const response = await axios.get('/api/ads/cities')
    return response.data
  },

  // Получение специализаций по категории
  async getSpecialties(category: string) {
    const response = await axios.get(`/api/ads/specialties/${category}`)
    return response.data
  }
}