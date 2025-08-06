/**
 * Основной store для управления объявлениями
 * Pinia store для entities/ad (FSD архитектура)
 */

import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'
import { adApi } from '../api/adApi'

// TypeScript интерфейсы
interface Ad {
  id: number
  title?: string
  description?: string
  status: 'draft' | 'active' | 'archived' | 'waiting_payment'
  category?: string
  location?: string
  price?: number
  is_favorite?: boolean
  [key: string]: any
}

interface AdFilters {
  status: string | null
  category: string | null
  location: string | null
  priceFrom: number | null
  priceTo: number | null
  sortBy: string
  sortOrder: 'asc' | 'desc'
}

interface AdPagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface UserStats {
  total: number
  active: number
  draft: number
  waiting_payment: number
  archived: number
  views_total: number
  views_today: number
}

interface FetchAdsParams {
  append?: boolean
  page?: number
  per_page?: number
  search?: string
  [key: string]: any
}

export const useAdStore = defineStore('ad', () => {
  // === СОСТОЯНИЕ ===
  
  // Список объявлений
  const ads = ref<Ad[]>([])
  
  // Текущее объявление
  const currentAd = ref<Ad | null>(null)
  
  // Избранные объявления
  const favoriteAds = ref<Ad[]>([])
  
  // Фильтры и поиск
  const filters = reactive<AdFilters>({
    status: null,
    category: null,
    location: null,
    priceFrom: null,
    priceTo: null,
    sortBy: 'created_at',
    sortOrder: 'desc'
  })
  
  // Поисковый запрос
  const searchQuery = ref<string>('')
  
  // Состояние загрузки
  const loading = ref<boolean>(false)
  const saving = ref<boolean>(false)
  
  // Пагинация
  const pagination = reactive<AdPagination>({
    current_page: 1,
    last_page: 1,
    per_page: 12,
    total: 0
  })
  
  // Статистика пользователя
  const userStats = reactive<UserStats>({
    total: 0,
    active: 0,
    draft: 0,
    waiting_payment: 0,
    archived: 0,
    views_total: 0,
    views_today: 0
  })
  
  // === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===
  
  /**
   * Отфильтрованные объявления
   */
  const filteredAds = computed<Ad[]>(() => {
    let result = ads?.value
    
    // Фильтр по статусу
    if (filters?.status) {
      result = result?.filter(ad => ad?.status === filters?.status)
    }
    
    // Фильтр по категории
    if (filters?.category) {
      result = result?.filter(ad => ad?.category === filters?.category)
    }
    
    // Фильтр по цене
    if ((filters?.priceFrom ?? 0)) {
      result = result?.filter(ad => (ad?.price || 0) >= (filters?.priceFrom ?? 0) || 0)
    }
    
    if ((filters?.priceTo ?? Infinity)) {
      result = result?.filter(ad => (ad?.price || 0) <= (filters?.priceTo ?? Infinity) || Infinity)
    }
    
    // Поиск по тексту
    if (searchQuery?.value) {
      const query = searchQuery?.value.toLowerCase()
      result = result?.filter(ad => 
        (ad?.title && ad?.title.toLowerCase().includes(query)) ||
        (ad?.description && ad?.description.toLowerCase().includes(query))
      )
    }
    
    return result
  })
  
  /**
   * Активные объявления
   */
  const activeAds = computed(() => 
    ads?.value.filter(ad => ad?.status === 'active')
  )
  
  /**
   * Черновики
   */
  const draftAds = computed(() => 
    ads?.value.filter(ad => ad?.status === 'draft')
  )
  
  /**
   * Архивные объявления
   */
  const archivedAds = computed(() => 
    ads?.value.filter(ad => ad?.status === 'archived')
  )
  
  /**
   * Есть ли еще страницы для загрузки
   */
  const hasMorePages = computed(() => 
    pagination?.current_page < pagination?.last_page
  )
  
  // === ДЕЙСТВИЯ ===
  
  /**
   * Загрузить объявления
   */
  const fetchAds = async (params: FetchAdsParams = {}): Promise<any> => {
    loading.value = true
    
    try {
      const queryParams = {
        page: pagination?.current_page,
        per_page: pagination?.per_page,
        ...filters,
        ...params
      }
      
      if (searchQuery.value) {
        queryParams.search = searchQuery.value
      }
      
      const response = await adApi?.getAds(queryParams)
      
      // Обновляем список объявлений
      if (params?.append) {
        ads.value.push(...response?.data)
      } else {
        ads.value = response?.data
      }
      
      // Обновляем пагинацию
      Object.assign(pagination, {
        current_page: response?.current_page,
        last_page: response?.last_page,
        per_page: response?.per_page,
        total: response?.total
      })
      
      return response
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Загрузить объявление по ID
   */
  const fetchAd = async (id: any) => {
    loading.value = true
    
    try {
      const response = await adApi?.getAd(id)
      const processedData = adApi?.processServerData(response)
      
      currentAd.value = processedData
      
      // Добавляем в список если его там нет
      const existingIndex = ads?.value.findIndex(ad => ad?.id === id)
      if (existingIndex >= 0) {
        ads.value[existingIndex] = processedData
      } else {
        ads.value.unshift(processedData)
      }
      
      return processedData
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Создать объявление
   */
  const createAd = async (data: any) => {
    saving.value = true
    
    try {
      const response = await adApi?.createAd(data)
      const newAd = adApi?.processServerData(response)
      
      // Добавляем в начало списка
      ads.value.unshift(newAd)
      
      // Обновляем статистику
      userStats.total++
      if (newAd?.status === 'draft') userStats.draft++
      if (newAd?.status === 'active') userStats.active++
      
      return newAd
    } catch (error) {
      throw error
    } finally {
      saving.value = false
    }
  }
  
  /**
   * Обновить объявление
   */
  const updateAd = async (id: any, data: any) => {
    saving.value = true
    
    try {
      const response = await adApi?.updateAd(id, data)
      const updatedAd = adApi?.processServerData(response)
      
      // Обновляем в списке
      const index = ads?.value.findIndex(ad => ad?.id === id)
      if (index >= 0) {
        ads.value[index] = updatedAd
      }
      
      // Обновляем текущее объявление
      if (currentAd?.value && currentAd?.value.id === id) {
        currentAd.value = updatedAd
      }
      
      return updatedAd
    } catch (error) {
      throw error
    } finally {
      saving.value = false
    }
  }
  
  /**
   * Удалить объявление
   */
  const deleteAd = async (id: any) => {
    saving.value = true
    
    try {
      await adApi?.deleteAd(id)
      
      // Удаляем из списка
      const index = ads?.value.findIndex(ad => ad?.id === id)
      if (index >= 0) {
        const deletedAd = ads?.value[index]
        ads?.value.splice(index, 1)
        
        // Обновляем статистику
        userStats.total--
        if (deletedAd?.status === 'draft') userStats.draft--
        if (deletedAd?.status === 'active') userStats.active--
        if (deletedAd?.status === 'archived') userStats.archived--
      }
      
      // Очищаем текущее объявление если оно удалено
      if (currentAd?.value && currentAd?.value.id === id) {
        currentAd.value = null
      }
      
    } catch (error) {
      throw error
    } finally {
      saving.value = false
    }
  }
  
  /**
   * Изменить статус объявления
   */
  const changeAdStatus = async (id: any, status: any) => {
    saving.value = true
    
    try {
      await adApi?.changeStatus(id, status)
      
      // Обновляем в списке
      const index = ads?.value.findIndex(ad => ad?.id === id)
      if (index >= 0) {
        const oldStatus = ads?.value[index].status
        ads?.value[index].status = status
        
        // Обновляем статистику
        if (oldStatus === 'draft') userStats.draft--
        if (oldStatus === 'active') userStats.active--
        if (oldStatus === 'archived') userStats.archived--
        
        if (status === 'draft') userStats.draft++
        if (status === 'active') userStats.active++
        if (status === 'archived') userStats.archived++
      }
      
      return ads?.value[index]
    } catch (error) {
      throw error
    } finally {
      saving.value = false
    }
  }
  
  /**
   * Добавить/удалить из избранного
   */
  const toggleFavorite = async (adId: any) => {
    try {
      const ad = ads?.value.find(a => a?.id === adId)
      if (!ad) return
      
      if (ad?.is_favorite) {
        await adApi?.removeFromFavorites(adId)
        ad?.is_favorite = false
        
        // Удаляем из избранных
        const favIndex = favoriteAds?.value.findIndex(fav => fav?.id === adId)
        if (favIndex >= 0) {
          favoriteAds?.value.splice(favIndex, 1)
        }
      } else {
        await adApi?.addToFavorites(adId)
        ad?.is_favorite = true
        
        // Добавляем в избранные
        favoriteAds.value.unshift(ad)
      }
      
      return ad?.is_favorite
    } catch (error) {
      throw error
    }
  }
  
  /**
   * Загрузить избранные объявления
   */
  const fetchFavorites = async () => {
    loading.value = true
    
    try {
      const response = await adApi?.getFavorites()
      favoriteAds.value = response?.data
      return response
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }
  
  /**
   * Загрузить статистику пользователя
   */
  const fetchUserStats = async () => {
    try {
      const response = await adApi?.getUserAdStats()
      Object.assign(userStats, response)
      return response
    } catch (error) {
      throw error
    }
  }
  
  /**
   * Установить фильтры
   */
  const setFilters = (newFilters: any) => {
    Object.assign(filters, newFilters)
    pagination?.current_page = 1 // Сбрасываем на первую страницу
  }
  
  /**
   * Очистить фильтры
   */
  const clearFilters = () => {
    Object?.keys(filters).forEach(key => {
      (filters as any)[key] = null
    })
    filters?.sortBy = 'created_at'
    filters?.sortOrder = 'desc'
    pagination?.current_page = 1
  }
  
  /**
   * Установить поисковый запрос
   */
  const setSearchQuery = (query: any) => {
    searchQuery.value = query
    pagination?.current_page = 1 // Сбрасываем на первую страницу
  }
  
  /**
   * Загрузить следующую страницу
   */
  const loadMoreAds = async () => {
    if (!hasMorePages?.value || loading?.value) return
    
    pagination.current_page++
    return await fetchAds({ append: true })
  }
  
  /**
   * Сброс состояния
   */
  const reset = () => {
    ads.value = []
    currentAd.value = null
    favoriteAds.value = []
    searchQuery.value = ''
    loading.value = false
    saving.value = false
    
    clearFilters()
    
    Object.assign(pagination, {
      current_page: 1,
      last_page: 1,
      per_page: 12,
      total: 0
    })
    
    Object.assign(userStats, {
      total: 0,
      active: 0,
      draft: 0,
      waiting_payment: 0,
      archived: 0,
      views_total: 0,
      views_today: 0
    })
  }
  
  // Возвращаем публичный API
  return {
    // Состояние
    ads,
    currentAd,
    favoriteAds,
    filters,
    searchQuery,
    loading,
    saving,
    pagination,
    userStats,
    
    // Вычисляемые свойства
    filteredAds,
    activeAds,
    draftAds,
    archivedAds,
    hasMorePages,
    
    // Действия
    fetchAds,
    fetchAd,
    createAd,
    updateAd,
    deleteAd,
    changeAdStatus,
    toggleFavorite,
    fetchFavorites,
    fetchUserStats,
    setFilters,
    clearFilters,
    setSearchQuery,
    loadMoreAds,
    reset
  }
})