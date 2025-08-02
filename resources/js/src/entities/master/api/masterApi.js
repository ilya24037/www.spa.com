/**
 * API слой для работы с мастерами
 * Централизованные методы для работы с master profiles
 */

import axios from 'axios'

class MasterApi {
  /**
   * Базовый URL для API мастеров
   */
  baseUrl = '/api/masters'

  /**
   * Получить список мастеров
   */
  async getMasters(params = {}) {
    const response = await axios.get(this.baseUrl, { params })
    return response.data
  }

  /**
   * Получить мастера по ID
   */
  async getMaster(id) {
    const response = await axios.get(`${this.baseUrl}/${id}`)
    return response.data
  }

  /**
   * Поиск мастеров
   */
  async searchMasters(query, filters = {}) {
    const params = {
      q: query,
      ...filters
    }
    
    const response = await axios.get(`${this.baseUrl}/search`, { params })
    return response.data
  }

  /**
   * Получить отзывы мастера
   */
  async getMasterReviews(masterId, params = {}) {
    const response = await axios.get(`${this.baseUrl}/${masterId}/reviews`, { params })
    return response.data
  }

  /**
   * Получить услуги мастера
   */
  async getMasterServices(masterId) {
    const response = await axios.get(`${this.baseUrl}/${masterId}/services`)
    return response.data
  }

  /**
   * Получить фотографии мастера
   */
  async getMasterPhotos(masterId) {
    const response = await axios.get(`${this.baseUrl}/${masterId}/photos`)
    return response.data
  }

  /**
   * Получить график работы мастера
   */
  async getMasterSchedule(masterId) {
    const response = await axios.get(`${this.baseUrl}/${masterId}/schedule`)
    return response.data
  }

  /**
   * Получить доступные слоты для записи
   */
  async getAvailableSlots(masterId, date) {
    const response = await axios.get(`${this.baseUrl}/${masterId}/time-slots`, {
      params: { date }
    })
    return response.data
  }

  /**
   * Добавить в избранное
   */
  async addToFavorites(masterId) {
    const response = await axios.post(`${this.baseUrl}/${masterId}/favorite`)
    return response.data
  }

  /**
   * Удалить из избранного
   */
  async removeFromFavorites(masterId) {
    const response = await axios.delete(`${this.baseUrl}/${masterId}/favorite`)
    return response.data
  }

  /**
   * Получить избранных мастеров
   */
  async getFavorites(params = {}) {
    const response = await axios.get('/api/favorites/masters', { params })
    return response.data
  }

  /**
   * Увеличить счетчик просмотров
   */
  async incrementViews(masterId) {
    const response = await axios.post(`${this.baseUrl}/${masterId}/view`)
    return response.data
  }

  /**
   * Получить похожих мастеров
   */
  async getSimilarMasters(masterId, params = {}) {
    const response = await axios.get(`${this.baseUrl}/${masterId}/similar`, { params })
    return response.data
  }

  /**
   * Получить статистику мастера
   */
  async getMasterStats(masterId) {
    const response = await axios.get(`${this.baseUrl}/${masterId}/stats`)
    return response.data
  }

  /**
   * Создать отзыв
   */
  async createReview(masterId, reviewData) {
    const response = await axios.post(`${this.baseUrl}/${masterId}/reviews`, reviewData)
    return response.data
  }

  /**
   * Получить мастеров по городу
   */
  async getMastersByCity(city, params = {}) {
    const response = await axios.get(`${this.baseUrl}/city/${city}`, { params })
    return response.data
  }

  /**
   * Получить мастеров по категории
   */
  async getMastersByCategory(category, params = {}) {
    const response = await axios.get(`${this.baseUrl}/category/${category}`, { params })
    return response.data
  }

  /**
   * Фильтрация мастеров
   */
  async filterMasters(filters) {
    const response = await axios.post(`${this.baseUrl}/filter`, filters)
    return response.data
  }

  /**
   * Получить популярных мастеров
   */
  async getPopularMasters(params = {}) {
    const response = await axios.get(`${this.baseUrl}/popular`, { params })
    return response.data
  }

  /**
   * Получить рекомендованных мастеров
   */
  async getRecommendedMasters(params = {}) {
    const response = await axios.get(`${this.baseUrl}/recommended`, { params })
    return response.data
  }

  /**
   * Получить новых мастеров
   */
  async getNewMasters(params = {}) {
    const response = await axios.get(`${this.baseUrl}/new`, { params })
    return response.data
  }

  /**
   * Получить мастеров онлайн
   */
  async getOnlineMasters(params = {}) {
    const response = await axios.get(`${this.baseUrl}/online`, { params })
    return response.data
  }

  /**
   * Получить премиум мастеров
   */
  async getPremiumMasters(params = {}) {
    const response = await axios.get(`${this.baseUrl}/premium`, { params })
    return response.data
  }

  /**
   * Получить проверенных мастеров
   */
  async getVerifiedMasters(params = {}) {
    const response = await axios.get(`${this.baseUrl}/verified`, { params })
    return response.data
  }
}

// Экспортируем синглтон
export const masterApi = new MasterApi()
export default masterApi