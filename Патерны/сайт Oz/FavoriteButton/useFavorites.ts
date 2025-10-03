/**
 * Composable для управления избранным
 * Синхронизация, localStorage, API интеграция
 */

import { ref, computed, watch } from 'vue'
import type { 
  FavoriteApiConfig,
  FavoriteApiResponse,
  FavoriteBatchOperation,
  FavoriteStats,
  FavoriteSyncConfig,
  FavoriteStorageData
} from './FavoriteButton.types'

export function useFavorites(config?: Partial<FavoriteApiConfig & FavoriteSyncConfig>) {
  // Настройки по умолчанию
  const defaultConfig = {
    addUrl: '/api/favorites/add',
    removeUrl: '/api/favorites/remove',
    countUrl: '/api/favorites/count',
    method: 'POST' as const,
    withCredentials: true,
    enabled: true,
    interval: 5000,
    storageKey: 'ozon_favorites',
    broadcastChannel: 'favorites_sync'
  }
  
  const settings = { ...defaultConfig, ...config }
  
  // Состояние
  const favorites = ref<Set<string>>(new Set())
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const lastSync = ref<number>(Date.now())
  
  // Статистика
  const stats = ref<FavoriteStats>({
    totalCount: 0,
    categories: {},
    recentlyAdded: [],
    mostFavorited: [],
    lastUpdated: new Date()
  })
  
  // Загрузка из localStorage
  const loadFromStorage = (): Set<string> => {
    try {
      const stored = localStorage.getItem(settings.storageKey)
      if (stored) {
        const data: FavoriteStorageData = JSON.parse(stored)
        lastSync.value = data.lastSync
        return new Set(data.items)
      }
    } catch (err) {
      console.error('Failed to load favorites from storage:', err)
    }
    return new Set()
  }
  
  // Сохранение в localStorage
  const saveToStorage = (items: Set<string>) => {
    try {
      const data: FavoriteStorageData = {
        items: items,
        lastSync: Date.now(),
        version: '1.0.0'
      }
      localStorage.setItem(settings.storageKey, JSON.stringify({
        ...data,
        items: Array.from(items)
      }))
    } catch (err) {
      console.error('Failed to save favorites to storage:', err)
    }
  }
  
  // API вызовы
  const apiCall = async (
    url: string,
    data: any
  ): Promise<FavoriteApiResponse> => {
    try {
      const response = await fetch(url, {
        method: settings.method,
        headers: {
          'Content-Type': 'application/json',
          ...settings.headers
        },
        credentials: settings.withCredentials ? 'include' : 'same-origin',
        body: JSON.stringify(data)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      return await response.json()
    } catch (err) {
      console.error('API call failed:', err)
      throw err
    }
  }
  
  // Добавление в избранное
  const addToFavorites = async (itemId: string, metadata?: Record<string, any>) => {
    if (favorites.value.has(itemId)) return true
    
    isLoading.value = true
    error.value = null
    
    try {
      // Оптимистичное обновление
      favorites.value.add(itemId)
      saveToStorage(favorites.value)
      
      // API вызов
      const response = await apiCall(settings.addUrl, {
        itemId,
        metadata
      })
      
      if (!response.success) {
        // Откат при ошибке
        favorites.value.delete(itemId)
        saveToStorage(favorites.value)
        throw new Error(response.error || 'Failed to add to favorites')
      }
      
      // Обновление статистики
      updateStats('add', itemId)
      
      // Синхронизация между вкладками
      broadcastChange('add', itemId)
      
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  // Удаление из избранного
  const removeFromFavorites = async (itemId: string) => {
    if (!favorites.value.has(itemId)) return true
    
    isLoading.value = true
    error.value = null
    
    try {
      // Оптимистичное обновление
      favorites.value.delete(itemId)
      saveToStorage(favorites.value)
      
      // API вызов
      const response = await apiCall(settings.removeUrl, {
        itemId
      })
      
      if (!response.success) {
        // Откат при ошибке
        favorites.value.add(itemId)
        saveToStorage(favorites.value)
        throw new Error(response.error || 'Failed to remove from favorites')
      }
      
      // Обновление статистики
      updateStats('remove', itemId)
      
      // Синхронизация между вкладками
      broadcastChange('remove', itemId)
      
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  // Переключение избранного
  const toggleFavorite = async (itemId: string, metadata?: Record<string, any>) => {
    if (favorites.value.has(itemId)) {
      return await removeFromFavorites(itemId)
    } else {
      return await addToFavorites(itemId, metadata)
    }
  }
  
  // Batch операции
  const batchOperation = async (operation: FavoriteBatchOperation) => {
    isLoading.value = true
    error.value = null
    
    try {
      const url = operation.action === 'add' ? settings.addUrl : settings.removeUrl
      
      // Оптимистичное обновление
      operation.items.forEach(itemId => {
        if (operation.action === 'add') {
          favorites.value.add(itemId)
        } else {
          favorites.value.delete(itemId)
        }
      })
      saveToStorage(favorites.value)
      
      // API вызов
      const response = await apiCall(url, {
        items: operation.items,
        category: operation.category,
        metadata: operation.metadata
      })
      
      if (!response.success) {
        // Откат при ошибке
        operation.items.forEach(itemId => {
          if (operation.action === 'add') {
            favorites.value.delete(itemId)
          } else {
            favorites.value.add(itemId)
          }
        })
        saveToStorage(favorites.value)
        throw new Error(response.error || 'Batch operation failed')
      }
      
      // Обновление статистики
      operation.items.forEach(itemId => {
        updateStats(operation.action, itemId)
      })
      
      // Синхронизация
      broadcastBatchChange(operation)
      
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Unknown error'
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  // Проверка, в избранном ли элемент
  const isFavorite = (itemId: string): boolean => {
    return favorites.value.has(itemId)
  }
  
  // Обновление статистики
  const updateStats = (action: 'add' | 'remove', itemId: string) => {
    stats.value.totalCount = favorites.value.size
    stats.value.lastUpdated = new Date()
    
    if (action === 'add') {
      stats.value.recentlyAdded.unshift(itemId)
      stats.value.recentlyAdded = stats.value.recentlyAdded.slice(0, 10)
    } else {
      stats.value.recentlyAdded = stats.value.recentlyAdded.filter(id => id !== itemId)
    }
  }
  
  // Синхронизация между вкладками
  const broadcastChange = (action: 'add' | 'remove', itemId: string) => {
    if (!settings.broadcastChannel || typeof window === 'undefined') return
    
    try {
      const channel = new BroadcastChannel(settings.broadcastChannel)
      channel.postMessage({
        action,
        itemId,
        timestamp: Date.now()
      })
    } catch (err) {
      console.error('Failed to broadcast change:', err)
    }
  }
  
  // Batch синхронизация
  const broadcastBatchChange = (operation: FavoriteBatchOperation) => {
    if (!settings.broadcastChannel || typeof window === 'undefined') return
    
    try {
      const channel = new BroadcastChannel(settings.broadcastChannel)
      channel.postMessage({
        action: 'batch',
        operation,
        timestamp: Date.now()
      })
    } catch (err) {
      console.error('Failed to broadcast batch change:', err)
    }
  }
  
  // Слушатель изменений из других вкладок
  if (settings.broadcastChannel && typeof window !== 'undefined') {
    const channel = new BroadcastChannel(settings.broadcastChannel)
    channel.onmessage = (event) => {
      if (event.data.action === 'add') {
        favorites.value.add(event.data.itemId)
      } else if (event.data.action === 'remove') {
        favorites.value.delete(event.data.itemId)
      } else if (event.data.action === 'batch') {
        const op = event.data.operation as FavoriteBatchOperation
        op.items.forEach(itemId => {
          if (op.action === 'add') {
            favorites.value.add(itemId)
          } else {
            favorites.value.delete(itemId)
          }
        })
      }
      saveToStorage(favorites.value)
    }
  }
  
  // Синхронизация с сервером
  const syncWithServer = async () => {
    if (!settings.countUrl) return
    
    try {
      const response = await apiCall(settings.countUrl, {
        items: Array.from(favorites.value)
      })
      
      if (response.success && response.count !== undefined) {
        stats.value.totalCount = response.count
      }
    } catch (err) {
      console.error('Failed to sync with server:', err)
    }
  }
  
  // Автоматическая синхронизация
  if (settings.enabled && settings.interval > 0) {
    setInterval(syncWithServer, settings.interval)
  }
  
  // Computed свойства
  const favoritesCount = computed(() => favorites.value.size)
  const favoritesArray = computed(() => Array.from(favorites.value))
  const hasFavorites = computed(() => favorites.value.size > 0)
  
  // Инициализация
  favorites.value = loadFromStorage()
  
  // Сохранение при изменениях
  watch(favorites, (newFavorites) => {
    saveToStorage(newFavorites)
  }, { deep: true })
  
  return {
    // Состояние
    favorites: computed(() => favorites.value),
    favoritesCount,
    favoritesArray,
    hasFavorites,
    isLoading: computed(() => isLoading.value),
    error: computed(() => error.value),
    stats: computed(() => stats.value),
    
    // Методы
    addToFavorites,
    removeFromFavorites,
    toggleFavorite,
    isFavorite,
    batchOperation,
    syncWithServer,
    
    // Утилиты
    clearAll: () => {
      favorites.value.clear()
      saveToStorage(favorites.value)
      broadcastBatchChange({
        items: Array.from(favorites.value),
        action: 'remove'
      })
    },
    
    exportFavorites: () => {
      return JSON.stringify({
        items: Array.from(favorites.value),
        exportDate: new Date().toISOString(),
        count: favorites.value.size
      })
    },
    
    importFavorites: (data: string) => {
      try {
        const parsed = JSON.parse(data)
        favorites.value = new Set(parsed.items)
        saveToStorage(favorites.value)
        return true
      } catch (err) {
        error.value = 'Failed to import favorites'
        return false
      }
    }
  }
}

// Глобальный экземпляр
let globalFavorites: ReturnType<typeof useFavorites> | null = null

export function useGlobalFavorites(config?: Partial<FavoriteApiConfig & FavoriteSyncConfig>) {
  if (!globalFavorites) {
    globalFavorites = useFavorites(config)
  }
  return globalFavorites
}