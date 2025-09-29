import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { logger } from '@/src/shared/utils/logger'

// TypeScript интерфейсы
export interface FavoriteItem {
  id: number
  type: 'master' | 'service' | 'ad'
  itemId: number
  addedAt: string
  // Данные самого элемента
  item: {
    id: number
    name: string
    image?: string
    rating?: number
    price?: number
    location?: string
    description?: string
  }
}

export interface FavoritesStats {
  totalCount: number
  masterCount: number
  serviceCount: number
  adCount: number
  lastAdded?: string
}

export const useFavoritesStore = defineStore('favorites', () => {
  // State
  const favorites = ref<FavoriteItem[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const lastSyncTime = ref<Date | null>(null)
  
  // Getters
  const totalCount = computed(() => favorites.value.length)
  
  const stats = computed<FavoritesStats>(() => ({
    totalCount: favorites.value.length,
    masterCount: favorites.value.filter(f => f.type === 'master').length,
    serviceCount: favorites.value.filter(f => f.type === 'service').length,
    adCount: favorites.value.filter(f => f.type === 'ad').length,
    lastAdded: favorites.value[0]?.addedAt
  }))
  
  const favoritesByType = computed(() => {
    const groups = {
      master: [] as FavoriteItem[],
      service: [] as FavoriteItem[],
      ad: [] as FavoriteItem[]
    }
    
    favorites.value.forEach(favorite => {
      groups[favorite.type].push(favorite)
    })
    
    return groups
  })
  
  const recentFavorites = computed(() => 
    favorites.value
      .slice()
      .sort((a, b) => new Date(b.addedAt).getTime() - new Date(a.addedAt).getTime())
      .slice(0, 10)
  )
  
  const hasFavorites = computed(() => favorites.value.length > 0)
  
  // Для обратной совместимости - список ID мастеров в избранном
  const favoriteIds = computed(() => 
    favorites.value
      .filter(f => f.type === 'master')
      .map(f => f.itemId)
  )
  
  // Actions
  const loadFavorites = async (): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/user/favorites')
      
      if (response.data?.favorites) {
        favorites.value = response.data.favorites
        lastSyncTime.value = new Date()
      }
    } catch (err: any) {
      // Молча обрабатываем 404 и 401 ошибки
      if (err.response?.status === 404 || err.response?.status === 401) {
        favorites.value = []
        // Removed console.log in production
      } else {
        error.value = err.response?.data?.message || 'Ошибка загрузки избранного'
        logger.warn('Failed to load favorites:', err.message || err)
      }
    } finally {
      isLoading.value = false
    }
  }
  
  const addToFavorites = async (type: 'master' | 'service' | 'ad', itemId: number, itemData?: any): Promise<boolean> => {
    // Проверить что элемент еще не в избранном
    if (isFavorite(type, itemId)) {
      return false
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/user/favorites', {
        type,
        item_id: itemId,
        item_data: itemData
      })
      
      if (response.data?.favorite) {
        // Добавить в начало списка
        favorites.value.unshift(response.data.favorite)
        return true
      }
      
      return false
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка добавления в избранное'
      logger.error('Failed to add to favorites:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  const removeFromFavorites = async (type: 'master' | 'service' | 'ad', itemId: number): Promise<boolean> => {
    const favoriteIndex = favorites.value.findIndex(f => f.type === type && f.itemId === itemId)
    if (favoriteIndex === -1) return false
    
    // Оптимистичное удаление
    const removedFavorite = favorites.value.splice(favoriteIndex, 1)[0]
    
    try {
      if (removedFavorite) {
        await axios.delete(`/api/user/favorites/${removedFavorite.id}`)
      }
      return true
    } catch (err: any) {
      // Откатить изменения
      if (removedFavorite) {
        favorites.value.splice(favoriteIndex, 0, removedFavorite)
      }
      error.value = err.response?.data?.message || 'Ошибка удаления из избранного'
      logger.error('Failed to remove from favorites:', err)
      return false
    }
  }
  
  const toggleFavorite = async (type: 'master' | 'service' | 'ad', itemId: number, itemData?: any): Promise<boolean> => {
    if (isFavorite(type, itemId)) {
      return removeFromFavorites(type, itemId)
    } else {
      return addToFavorites(type, itemId, itemData)
    }
  }
  
  const isFavorite = (type: 'master' | 'service' | 'ad', itemId: number): boolean => {
    return favorites.value.some(f => f.type === type && f.itemId === itemId)
  }
  
  // Удобные методы для мастеров (обратная совместимость)
  const isMasterFavorite = (masterId: number): boolean => {
    return isFavorite('master', masterId)
  }
  
  const addMasterToFavorites = async (masterId: number, masterData?: any): Promise<boolean> => {
    return addToFavorites('master', masterId, masterData)
  }
  
  const removeMasterFromFavorites = async (masterId: number): Promise<boolean> => {
    return removeFromFavorites('master', masterId)
  }
  
  const toggleMasterFavorite = async (masterId: number, masterData?: any): Promise<boolean> => {
    return toggleFavorite('master', masterId, masterData)
  }
  
  const getFavorite = (type: 'master' | 'service' | 'ad', itemId: number): FavoriteItem | null => {
    return favorites.value.find(f => f.type === type && f.itemId === itemId) || null
  }
  
  const clearAllFavorites = async (): Promise<boolean> => {
    if (!hasFavorites.value) return true
    
    const backupFavorites = [...favorites.value]
    favorites.value = []
    
    try {
      await axios.delete('/api/user/favorites/all')
      return true
    } catch (err: any) {
      // Откатить изменения
      favorites.value = backupFavorites
      error.value = err.response?.data?.message || 'Ошибка очистки избранного'
      logger.error('Failed to clear favorites:', err)
      return false
    }
  }
  
  const clearFavoritesByType = async (type: 'master' | 'service' | 'ad'): Promise<boolean> => {
    const typeItems = favorites.value.filter(f => f.type === type)
    if (typeItems.length === 0) return true
    
    // Оптимистичное удаление
    favorites.value = favorites.value.filter(f => f.type !== type)
    
    try {
      await axios.delete(`/api/user/favorites/type/${type}`)
      return true
    } catch (err: any) {
      // Откатить изменения
      favorites.value = [...favorites.value, ...typeItems].sort((a, b) => 
        new Date(b.addedAt).getTime() - new Date(a.addedAt).getTime()
      )
      error.value = err.response?.data?.message || `Ошибка очистки избранного типа ${type}`
      logger.error(`Failed to clear ${type} favorites:`, err)
      return false
    }
  }
  
  const syncFavorites = async (): Promise<void> => {
    if (isLoading.value) return
    
    try {
      await loadFavorites()
    } catch (err) {
      logger.warn('Favorites sync failed:', err)
    }
  }
  
  const searchFavorites = (query: string): FavoriteItem[] => {
    if (!query.trim()) return favorites.value
    
    const searchTerm = query.toLowerCase()
    return favorites.value.filter(favorite => 
      favorite.item.name.toLowerCase().includes(searchTerm) ||
      favorite.item.description?.toLowerCase().includes(searchTerm) ||
      favorite.item.location?.toLowerCase().includes(searchTerm)
    )
  }
  
  const exportFavorites = async (): Promise<Blob | null> => {
    try {
      const response = await axios.get('/api/user/favorites/export', {
        responseType: 'blob'
      })
      
      return new Blob([response.data], { type: 'application/json' })
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка экспорта избранного'
      logger.error('Failed to export favorites:', err)
      return null
    }
  }
  
  const clearError = (): void => {
    error.value = null
  }
  
  const reset = (): void => {
    favorites.value = []
    error.value = null
    isLoading.value = false
    lastSyncTime.value = null
  }
  
  // Не загружаем автоматически - API может быть недоступен
  // loadFavorites() - вызывается вручную когда нужно
  
  return {
    // State
    favorites,
    isLoading,
    error,
    lastSyncTime,
    
    // Getters
    totalCount,
    stats,
    favoritesByType,
    recentFavorites,
    hasFavorites,
    favoriteIds,
    
    // Actions
    loadFavorites,
    addToFavorites,
    removeFromFavorites,
    toggleFavorite,
    isFavorite,
    getFavorite,
    // Для мастеров
    isMasterFavorite,
    addMasterToFavorites,
    removeMasterFromFavorites,
    toggleMasterFavorite,
    clearAllFavorites,
    clearFavoritesByType,
    syncFavorites,
    searchFavorites,
    exportFavorites,
    clearError,
    reset
  }
})