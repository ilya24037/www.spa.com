/**
 * Основной store для управления мастерами
 * Pinia store для entities/master (FSD архитектура)
 */

import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'
import { masterApi } from '../api/masterApi'

export const useMasterStore = defineStore('master', () => {
  // === СОСТОЯНИЕ ===
  
  // Список мастеров
  const masters = ref([])
  
  // Текущий мастер
  const currentMaster = ref(null)
  
  // Избранные мастера
  const favoriteMasters = ref([])
  
  // Похожие мастера
  const similarMasters = ref([])
  
  // Фильтры
  const filters = reactive({
    city: null,
    district: null,
    category: null,
    priceFrom: null,
    priceTo: null,
    rating: null,
    experience: null,
    age: null,
    availability: null,
    verified: false,
    premium: false,
    online: false,
    sortBy: 'rating',
    sortOrder: 'desc'
  })
  
  // Поисковый запрос
  const searchQuery = ref('')
  
  // Состояние загрузки
  const loading = ref(false)
  const loadingReviews = ref(false)
  
  // Пагинация
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 12,
    total: 0
  })
  
  // Отзывы текущего мастера
  const currentMasterReviews = ref([])
  
  // === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===
  
  /**
   * Отфильтрованные мастера
   */
  const filteredMasters = computed(() => {
    let result = masters.value
    
    // Фильтр по городу
    if (filters.city) {
      result = result.filter(master => master.city === filters.city)
    }
    
    // Фильтр по району
    if (filters.district) {
      result = result.filter(master => master.district === filters.district)
    }
    
    // Фильтр по категории
    if (filters.category) {
      result = result.filter(master => master.category === filters.category)
    }
    
    // Фильтр по цене
    if (filters.priceFrom) {
      result = result.filter(master => (master.price_from || 0) >= filters.priceFrom)
    }
    
    if (filters.priceTo) {
      result = result.filter(master => (master.price_from || 0) <= filters.priceTo)
    }
    
    // Фильтр по рейтингу
    if (filters.rating) {
      result = result.filter(master => (master.rating || 0) >= filters.rating)
    }
    
    // Фильтр по верификации
    if (filters.verified) {
      result = result.filter(master => master.is_verified)
    }
    
    // Фильтр по премиум
    if (filters.premium) {
      result = result.filter(master => master.is_premium)
    }
    
    // Фильтр по онлайн статусу
    if (filters.online) {
      result = result.filter(master => master.is_online || master.is_available_now)
    }
    
    // Поиск по тексту
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      result = result.filter(master => 
        (master.name && master.name.toLowerCase().includes(query)) ||
        (master.display_name && master.display_name.toLowerCase().includes(query)) ||
        (master.specialty && master.specialty.toLowerCase().includes(query)) ||
        (master.bio && master.bio.toLowerCase().includes(query))
      )
    }
    
    return result
  })
  
  /**
   * Популярные мастера
   */
  const popularMasters = computed(() => 
    masters.value
      .filter(master => master.rating >= 4.5)
      .sort((a, b) => (b.reviews_count || 0) - (a.reviews_count || 0))
  )
  
  /**
   * Премиум мастера
   */
  const premiumMasters = computed(() => 
    masters.value.filter(master => master.is_premium)
  )
  
  /**
   * Проверенные мастера
   */
  const verifiedMasters = computed(() => 
    masters.value.filter(master => master.is_verified)
  )
  
  /**
   * Онлайн мастера
   */
  const onlineMasters = computed(() => 
    masters.value.filter(master => master.is_online || master.is_available_now)
  )
  
  /**
   * Есть ли еще страницы для загрузки
   */
  const hasMorePages = computed(() => 
    pagination.current_page < pagination.last_page
  )
  
  // === ДЕЙСТВИЯ ===
  
  /**
   * Загрузить мастеров
   */
  const fetchMasters = async (params = {}) => {
    loading.value = true
    
    try {
      const queryParams = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        ...filters,
        ...params
      }
      
      if (searchQuery.value) {
        queryParams.search = searchQuery.value
      }
      
      const response = await masterApi.getMasters(queryParams)
      
      // Обновляем список мастеров
      if (params.append) {
        masters.value.push(...response.data)
      } else {
        masters.value = response.data
      }
      
      // Обновляем пагинацию
      Object.assign(pagination, {
        current_page: response.current_page,
        last_page: response.last_page,
        per_page: response.per_page,
        total: response.total
      })
      
      return response
    } catch (error) {
      console.error('Ошибка загрузки мастеров:', error)
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Загрузить мастера по ID
   */
  const fetchMaster = async (id) => {
    loading.value = true
    
    try {
      const response = await masterApi.getMaster(id)
      
      currentMaster.value = response
      
      // Добавляем в список если его там нет
      const existingIndex = masters.value.findIndex(master => master.id === id)
      if (existingIndex >= 0) {
        masters.value[existingIndex] = response
      } else {
        masters.value.unshift(response)
      }
      
      return response
    } catch (error) {
      console.error('Ошибка загрузки мастера:', error)
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Поиск мастеров
   */
  const searchMasters = async (query, additionalFilters = {}) => {
    loading.value = true
    
    try {
      const response = await masterApi.searchMasters(query, {
        ...filters,
        ...additionalFilters
      })
      
      masters.value = response.data
      
      // Обновляем пагинацию
      Object.assign(pagination, {
        current_page: response.current_page || 1,
        last_page: response.last_page || 1,
        per_page: response.per_page || 12,
        total: response.total || response.data.length
      })
      
      return response
    } catch (error) {
      console.error('Ошибка поиска мастеров:', error)
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Загрузить отзывы мастера
   */
  const fetchMasterReviews = async (masterId, params = {}) => {
    loadingReviews.value = true
    
    try {
      const response = await masterApi.getMasterReviews(masterId, params)
      
      if (params.append) {
        currentMasterReviews.value.push(...response.data)
      } else {
        currentMasterReviews.value = response.data
      }
      
      return response
    } catch (error) {
      console.error('Ошибка загрузки отзывов:', error)
      throw error
    } finally {
      loadingReviews.value = false
    }
  }
  
  /**
   * Загрузить похожих мастеров
   */
  const fetchSimilarMasters = async (masterId, params = {}) => {
    try {
      const response = await masterApi.getSimilarMasters(masterId, params)
      similarMasters.value = response.data
      return response
    } catch (error) {
      console.error('Ошибка загрузки похожих мастеров:', error)
      throw error
    }
  }
  
  /**
   * Добавить/удалить из избранного
   */
  const toggleFavorite = async (masterId) => {
    try {
      const master = masters.value.find(m => m.id === masterId) || currentMaster.value
      if (!master) return
      
      if (master.is_favorite) {
        await masterApi.removeFromFavorites(masterId)
        master.is_favorite = false
        
        // Удаляем из избранных
        const favIndex = favoriteMasters.value.findIndex(fav => fav.id === masterId)
        if (favIndex >= 0) {
          favoriteMasters.value.splice(favIndex, 1)
        }
      } else {
        await masterApi.addToFavorites(masterId)
        master.is_favorite = true
        
        // Добавляем в избранные
        favoriteMasters.value.unshift(master)
      }
      
      return master.is_favorite
    } catch (error) {
      console.error('Ошибка изменения избранного:', error)
      throw error
    }
  }
  
  /**
   * Загрузить избранных мастеров
   */
  const fetchFavorites = async () => {
    loading.value = true
    
    try {
      const response = await masterApi.getFavorites()
      favoriteMasters.value = response.data
      return response
    } catch (error) {
      console.error('Ошибка загрузки избранных:', error)
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Увеличить просмотры
   */
  const incrementViews = async (masterId) => {
    try {
      await masterApi.incrementViews(masterId)
      
      // Обновляем локально
      const master = masters.value.find(m => m.id === masterId) || currentMaster.value
      if (master && master.views_count) {
        master.views_count++
      }
    } catch (error) {
      // Не показываем ошибку пользователю для просмотров
      console.warn('Ошибка обновления просмотров:', error)
    }
  }
  
  /**
   * Установить фильтры
   */
  const setFilters = (newFilters) => {
    Object.assign(filters, newFilters)
    pagination.current_page = 1 // Сбрасываем на первую страницу
  }
  
  /**
   * Очистить фильтры
   */
  const clearFilters = () => {
    Object.keys(filters).forEach(key => {
      if (typeof filters[key] === 'boolean') {
        filters[key] = false
      } else {
        filters[key] = null
      }
    })
    filters.sortBy = 'rating'
    filters.sortOrder = 'desc'
    pagination.current_page = 1
  }
  
  /**
   * Установить поисковый запрос
   */
  const setSearchQuery = (query) => {
    searchQuery.value = query
    pagination.current_page = 1 // Сбрасываем на первую страницу
  }
  
  /**
   * Загрузить следующую страницу
   */
  const loadMoreMasters = async () => {
    if (!hasMorePages.value || loading.value) return
    
    pagination.current_page++
    return await fetchMasters({ append: true })
  }
  
  /**
   * Сброс состояния
   */
  const reset = () => {
    masters.value = []
    currentMaster.value = null
    favoriteMasters.value = []
    similarMasters.value = []
    currentMasterReviews.value = []
    searchQuery.value = ''
    loading.value = false
    loadingReviews.value = false
    
    clearFilters()
    
    Object.assign(pagination, {
      current_page: 1,
      last_page: 1,
      per_page: 12,
      total: 0
    })
  }
  
  // Возвращаем публичный API
  return {
    // Состояние
    masters,
    currentMaster,
    favoriteMasters,
    similarMasters,
    currentMasterReviews,
    filters,
    searchQuery,
    loading,
    loadingReviews,
    pagination,
    
    // Вычисляемые свойства
    filteredMasters,
    popularMasters,
    premiumMasters,
    verifiedMasters,
    onlineMasters,
    hasMorePages,
    
    // Действия
    fetchMasters,
    fetchMaster,
    searchMasters,
    fetchMasterReviews,
    fetchSimilarMasters,
    toggleFavorite,
    fetchFavorites,
    incrementViews,
    setFilters,
    clearFilters,
    setSearchQuery,
    loadMoreMasters,
    reset
  }
})