import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { logger } from '@/src/shared/utils/logger'

// TypeScript интерфейсы
export interface CompareItem {
  id: number
  type: 'master' | 'service' | 'ad'
  itemId: number
  addedAt: string
  // Данные самого элемента для сравнения
  item: {
    id: number
    name: string
    image?: string
    rating?: number
    price?: number
    location?: string
    description?: string
    // Дополнительные поля для сравнения
    features?: Record<string, any>
    specifications?: Record<string, any>
  }
}

export interface CompareStats {
  totalCount: number
  masterCount: number
  serviceCount: number
  adCount: number
  lastAdded?: string
}

export const useCompareStore = defineStore('compare', () => {
  // State
  const compareItems = ref<CompareItem[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const maxItemsPerType = ref(5) // Ограничение сравнения
  const lastSyncTime = ref<Date | null>(null)
  
  // Getters
  const totalCount = computed(() => compareItems.value.length)
  
  const stats = computed<CompareStats>(() => ({
    totalCount: compareItems.value.length,
    masterCount: compareItems.value.filter(c => c.type === 'master').length,
    serviceCount: compareItems.value.filter(c => c.type === 'service').length,
    adCount: compareItems.value.filter(c => c.type === 'ad').length,
    lastAdded: compareItems.value[0]?.addedAt
  }))
  
  const compareByType = computed(() => {
    const groups = {
      master: [] as CompareItem[],
      service: [] as CompareItem[],
      ad: [] as CompareItem[]
    }
    
    compareItems.value.forEach(item => {
      groups[item.type].push(item)
    })
    
    return groups
  })
  
  const recentCompare = computed(() => 
    compareItems.value
      .slice()
      .sort((a, b) => new Date(b.addedAt).getTime() - new Date(a.addedAt).getTime())
      .slice(0, 10)
  )
  
  const hasCompareItems = computed(() => compareItems.value.length > 0)
  
  const canAddMore = computed(() => (type: 'master' | 'service' | 'ad') => {
    const countByType = compareByType.value[type].length
    return countByType < maxItemsPerType.value
  })
  
  // Actions
  const loadCompareItems = async (): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/user/compare')
      
      if (response.data?.compare_items) {
        compareItems.value = response.data.compare_items
        lastSyncTime.value = new Date()
      }
    } catch (err: any) {
      // Молча обрабатываем 404 и 401 ошибки
      if (err.response?.status === 404 || err.response?.status === 401) {
        compareItems.value = []
        // Removed console.log in production
      } else {
        error.value = err.response?.data?.message || 'Ошибка загрузки списка сравнения'
        logger.warn('Failed to load compare items:', err.message || err)
      }
    } finally {
      isLoading.value = false
    }
  }
  
  const addToCompare = async (type: 'master' | 'service' | 'ad', itemId: number, itemData?: any): Promise<boolean> => {
    // Проверить что элемент еще не в сравнении
    if (isInCompare(type, itemId)) {
      return false
    }
    
    // Проверить лимит для типа
    if (!canAddMore.value(type)) {
      error.value = `Максимум ${maxItemsPerType.value} элементов для сравнения`
      return false
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/user/compare', {
        type,
        item_id: itemId,
        item_data: itemData
      })
      
      if (response.data?.compare_item) {
        // Добавить в начало списка
        compareItems.value.unshift(response.data.compare_item)
        return true
      }
      
      return false
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка добавления в сравнение'
      logger.error('Failed to add to compare:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  const removeFromCompare = async (type: 'master' | 'service' | 'ad', itemId: number): Promise<boolean> => {
    const compareIndex = compareItems.value.findIndex(c => c.type === type && c.itemId === itemId)
    if (compareIndex === -1) return false
    
    // Оптимистичное удаление
    const removedItem = compareItems.value.splice(compareIndex, 1)[0]
    
    try {
      if (removedItem) {
        await axios.delete(`/api/user/compare/${removedItem.id}`)
      }
      return true
    } catch (err: any) {
      // Откатить изменения
      if (removedItem) {
        compareItems.value.splice(compareIndex, 0, removedItem)
      }
      error.value = err.response?.data?.message || 'Ошибка удаления из сравнения'
      logger.error('Failed to remove from compare:', err)
      return false
    }
  }
  
  const toggleCompare = async (type: 'master' | 'service' | 'ad', itemId: number, itemData?: any): Promise<boolean> => {
    if (isInCompare(type, itemId)) {
      return removeFromCompare(type, itemId)
    } else {
      return addToCompare(type, itemId, itemData)
    }
  }
  
  const isInCompare = (type: 'master' | 'service' | 'ad', itemId: number): boolean => {
    return compareItems.value.some(c => c.type === type && c.itemId === itemId)
  }
  
  const getCompareItem = (type: 'master' | 'service' | 'ad', itemId: number): CompareItem | null => {
    return compareItems.value.find(c => c.type === type && c.itemId === itemId) || null
  }
  
  const clearAllCompare = async (): Promise<boolean> => {
    if (!hasCompareItems.value) return true
    
    const backupItems = [...compareItems.value]
    compareItems.value = []
    
    try {
      await axios.delete('/api/user/compare/all')
      return true
    } catch (err: any) {
      // Откатить изменения
      compareItems.value = backupItems
      error.value = err.response?.data?.message || 'Ошибка очистки списка сравнения'
      logger.error('Failed to clear compare:', err)
      return false
    }
  }
  
  const clearCompareByType = async (type: 'master' | 'service' | 'ad'): Promise<boolean> => {
    const typeItems = compareItems.value.filter(c => c.type === type)
    if (typeItems.length === 0) return true
    
    // Оптимистичное удаление
    compareItems.value = compareItems.value.filter(c => c.type !== type)
    
    try {
      await axios.delete(`/api/user/compare/type/${type}`)
      return true
    } catch (err: any) {
      // Откатить изменения
      compareItems.value = [...compareItems.value, ...typeItems].sort((a, b) => 
        new Date(b.addedAt).getTime() - new Date(a.addedAt).getTime()
      )
      error.value = err.response?.data?.message || `Ошибка очистки сравнения типа ${type}`
      logger.error(`Failed to clear ${type} compare:`, err)
      return false
    }
  }
  
  const syncCompare = async (): Promise<void> => {
    if (isLoading.value) return
    
    try {
      await loadCompareItems()
    } catch (err) {
      logger.warn('Compare sync failed:', err)
    }
  }
  
  const generateCompareReport = async (type: 'master' | 'service' | 'ad'): Promise<any | null> => {
    const itemsToCompare = compareByType.value[type]
    if (itemsToCompare.length < 2) {
      error.value = 'Для сравнения нужно минимум 2 элемента'
      return null
    }
    
    try {
      const response = await axios.post(`/api/user/compare/report/${type}`, {
        item_ids: itemsToCompare.map(item => item.itemId)
      })
      
      return response.data?.report || null
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка генерации отчета сравнения'
      logger.error('Failed to generate compare report:', err)
      return null
    }
  }
  
  const exportCompare = async (): Promise<Blob | null> => {
    try {
      const response = await axios.get('/api/user/compare/export', {
        responseType: 'blob'
      })
      
      return new Blob([response.data], { type: 'application/json' })
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка экспорта списка сравнения'
      logger.error('Failed to export compare:', err)
      return null
    }
  }
  
  const clearError = (): void => {
    error.value = null
  }
  
  const reset = (): void => {
    compareItems.value = []
    error.value = null
    isLoading.value = false
    lastSyncTime.value = null
  }
  
  // Не загружаем автоматически - API может быть недоступен
  // loadCompareItems() - вызывается вручную когда нужно
  
  return {
    // State
    compareItems,
    isLoading,
    error,
    maxItemsPerType,
    lastSyncTime,
    
    // Getters
    totalCount,
    stats,
    compareByType,
    recentCompare,
    hasCompareItems,
    canAddMore,
    
    // Actions
    loadCompareItems,
    addToCompare,
    removeFromCompare,
    toggleCompare,
    isInCompare,
    getCompareItem,
    clearAllCompare,
    clearCompareByType,
    syncCompare,
    generateCompareReport,
    exportCompare,
    clearError,
    reset
  }
})