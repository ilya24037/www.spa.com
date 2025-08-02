/**
 * API слой для работы с объявлениями
 * Централизованные методы для CRUD операций с ads
 */

import axios from 'axios'

class AdApi {
  /**
   * Базовый URL для API объявлений
   */
  baseUrl = '/api/ads'

  /**
   * Получить список объявлений
   */
  async getAds(params = {}) {
    const response = await axios.get(this.baseUrl, { params })
    return response.data
  }

  /**
   * Получить объявление по ID
   */
  async getAd(id) {
    const response = await axios.get(`${this.baseUrl}/${id}`)
    return response.data
  }

  /**
   * Создать новое объявление
   */
  async createAd(data) {
    const preparedData = this.prepareFormData(data)
    const response = await axios.post(this.baseUrl, preparedData)
    return response.data
  }

  /**
   * Обновить объявление
   */
  async updateAd(id, data) {
    const preparedData = this.prepareFormData(data)
    const response = await axios.put(`${this.baseUrl}/${id}`, preparedData)
    return response.data
  }

  /**
   * Удалить объявление
   */
  async deleteAd(id) {
    const response = await axios.delete(`${this.baseUrl}/${id}`)
    return response.data
  }

  /**
   * Сохранить черновик
   */
  async saveDraft(data) {
    const preparedData = this.prepareFormData(data)
    const response = await axios.post(`${this.baseUrl}/draft`, preparedData)
    return response.data
  }

  /**
   * Обновить черновик
   */
  async updateDraft(id, data) {
    const preparedData = this.prepareFormData(data)
    const response = await axios.put(`${this.baseUrl}/${id}/draft`, preparedData)
    return response.data
  }

  /**
   * Опубликовать объявление
   */
  async publishAd(id, data = null) {
    const payload = data ? this.prepareFormData(data) : {}
    const response = await axios.post(`${this.baseUrl}/${id}/publish`, payload)
    return response.data
  }

  /**
   * Архивировать объявление
   */
  async archiveAd(id) {
    const response = await axios.post(`${this.baseUrl}/${id}/archive`)
    return response.data
  }

  /**
   * Восстановить из архива
   */
  async unarchiveAd(id) {
    const response = await axios.post(`${this.baseUrl}/${id}/unarchive`)
    return response.data
  }

  /**
   * Изменить статус объявления
   */
  async changeStatus(id, status) {
    const response = await axios.patch(`${this.baseUrl}/${id}/status`, { status })
    return response.data
  }

  /**
   * Загрузить фотографии
   */
  async uploadPhotos(files, adId = null) {
    const formData = new FormData()
    
    files.forEach((file, index) => {
      formData.append(`photos[${index}]`, file)
    })
    
    if (adId) {
      formData.append('ad_id', adId)
    }

    const response = await axios.post(`${this.baseUrl}/upload-photos`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    return response.data
  }

  /**
   * Загрузить видео
   */
  async uploadVideo(file, adId = null) {
    const formData = new FormData()
    formData.append('video', file)
    
    if (adId) {
      formData.append('ad_id', adId)
    }

    const response = await axios.post(`${this.baseUrl}/upload-video`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    return response.data
  }

  /**
   * Удалить фотографию
   */
  async deletePhoto(photoId) {
    const response = await axios.delete(`${this.baseUrl}/photos/${photoId}`)
    return response.data
  }

  /**
   * Поиск объявлений
   */
  async searchAds(query, filters = {}) {
    const params = {
      q: query,
      ...filters
    }
    
    const response = await axios.get(`${this.baseUrl}/search`, { params })
    return response.data
  }

  /**
   * Получить статистику объявлений пользователя
   */
  async getUserAdStats() {
    const response = await axios.get(`${this.baseUrl}/stats`)
    return response.data
  }

  /**
   * Добавить в избранное
   */
  async addToFavorites(adId) {
    const response = await axios.post(`${this.baseUrl}/${adId}/favorite`)
    return response.data
  }

  /**
   * Удалить из избранного
   */
  async removeFromFavorites(adId) {
    const response = await axios.delete(`${this.baseUrl}/${adId}/favorite`)
    return response.data
  }

  /**
   * Получить избранные объявления
   */
  async getFavorites(params = {}) {
    const response = await axios.get('/api/favorites/ads', { params })
    return response.data
  }

  /**
   * Увеличить счетчик просмотров
   */
  async incrementViews(adId) {
    const response = await axios.post(`${this.baseUrl}/${adId}/view`)
    return response.data
  }

  /**
   * Подготовка данных формы для отправки на сервер
   */
  prepareFormData(form) {
    const data = { ...form }

    // Обрабатываем JSON поля
    const jsonFields = ['schedule', 'features', 'services', 'geo', 'pricing_data']
    jsonFields.forEach(field => {
      if (data[field] && typeof data[field] === 'object') {
        data[field] = JSON.stringify(data[field])
      }
    })

    // Обрабатываем массивы
    const arrayFields = ['service_location', 'outcall_locations', 'clients', 'service_provider', 'payment_methods']
    arrayFields.forEach(field => {
      if (data[field] && Array.isArray(data[field])) {
        data[field] = data[field].join(',')
      }
    })

    // Очищаем пустые поля
    Object.keys(data).forEach(key => {
      if (data[key] === '' || data[key] === null || data[key] === undefined) {
        delete data[key]
      }
    })

    // Добавляем метаданные
    data.category = data.category || 'massage'
    
    return data
  }

  /**
   * Обработка данных с сервера для формы
   */
  processServerData(serverData) {
    const data = { ...serverData }

    // Парсим JSON поля
    const jsonFields = ['schedule', 'features', 'services', 'geo', 'pricing_data']
    jsonFields.forEach(field => {
      if (data[field] && typeof data[field] === 'string') {
        try {
          data[field] = JSON.parse(data[field])
        } catch (e) {
          data[field] = {}
        }
      }
    })

    // Парсим массивы
    const arrayFields = ['service_location', 'outcall_locations', 'clients', 'service_provider', 'payment_methods']
    arrayFields.forEach(field => {
      if (data[field] && typeof data[field] === 'string') {
        data[field] = data[field].split(',').filter(Boolean)
      }
    })

    // Обрабатываем фотографии
    if (data.photos && typeof data.photos === 'string') {
      try {
        data.photos = JSON.parse(data.photos)
      } catch (e) {
        data.photos = []
      }
    }

    return data
  }
}

// Экспортируем синглтон
export const adApi = new AdApi()
export default adApi