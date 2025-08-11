import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// Типы
interface CompareItem {
  id: number
  type: 'master' | 'service' | 'ad'
  name: string
  price?: number
  rating?: number
  image?: string
  attributes?: Record<string, any>
}

export const useCompareStore = defineStore('compare', () => {
  // State
  const items = ref<CompareItem[]>([])
  const loading = ref(false)
  const maxItems = 4 // Максимум для сравнения
  
  // Computed
  const hasItems = computed(() => items.value.length > 0)
  const canAddMore = computed(() => items.value.length < maxItems)
  const itemsCount = computed(() => items.value.length)
  
  // Получить ID элементов для сравнения
  const itemIds = computed(() => items.value.map(item => item.id))
  
  // Actions
  // Загрузить элементы из localStorage или API
  const loadCompareItems = async () => {
    try {
      // Сначала пытаемся загрузить из localStorage
      const stored = localStorage.getItem('compareItems')
      if (stored) {
        items.value = JSON.parse(stored)
      }
      
      // Если есть авторизация, синхронизируем с сервером
      const token = localStorage.getItem('token')
      if (token) {
        loading.value = true
        const response = await axios.get('/api/compare')
        if (response.data.items) {
          items.value = response.data.items
          saveToLocalStorage()
        }
      }
    } catch (error) {
      console.error('Ошибка загрузки сравнения:', error)
    } finally {
      loading.value = false
    }
  }
  
  // Добавить элемент для сравнения
  const addItem = async (item: CompareItem) => {
    // Проверяем лимит
    if (items.value.length >= maxItems) {
      throw new Error(`Можно сравнивать максимум ${maxItems} элемента`)
    }
    
    // Проверяем дубликаты
    if (items.value.some(i => i.id === item.id && i.type === item.type)) {
      throw new Error('Этот элемент уже добавлен для сравнения')
    }
    
    // Добавляем
    items.value.push(item)
    saveToLocalStorage()
    
    // Синхронизируем с сервером если авторизован
    const token = localStorage.getItem('token')
    if (token) {
      try {
        await axios.post('/api/compare/add', { 
          id: item.id, 
          type: item.type 
        })
      } catch (error) {
        console.error('Ошибка синхронизации:', error)
      }
    }
  }
  
  // Удалить элемент из сравнения
  const removeItem = async (id: number, type: string) => {
    const index = items.value.findIndex(i => i.id === id && i.type === type)
    if (index > -1) {
      items.value.splice(index, 1)
      saveToLocalStorage()
      
      // Синхронизируем с сервером
      const token = localStorage.getItem('token')
      if (token) {
        try {
          await axios.delete(`/api/compare/${type}/${id}`)
        } catch (error) {
          console.error('Ошибка удаления:', error)
        }
      }
    }
  }
  
  // Очистить все элементы
  const clearAll = async () => {
    items.value = []
    saveToLocalStorage()
    
    // Синхронизируем с сервером
    const token = localStorage.getItem('token')
    if (token) {
      try {
        await axios.delete('/api/compare/clear')
      } catch (error) {
        console.error('Ошибка очистки:', error)
      }
    }
  }
  
  // Проверить, есть ли элемент в сравнении
  const hasItem = (id: number, type: string): boolean => {
    return items.value.some(i => i.id === id && i.type === type)
  }
  
  // Сохранить в localStorage
  const saveToLocalStorage = () => {
    localStorage.setItem('compareItems', JSON.stringify(items.value))
  }
  
  // Получить элементы по типу
  const getItemsByType = (type: string) => {
    return items.value.filter(item => item.type === type)
  }
  
  return {
    // State
    items,
    loading,
    maxItems,
    
    // Computed
    hasItems,
    canAddMore,
    itemsCount,
    itemIds,
    
    // Actions
    loadCompareItems,
    addItem,
    removeItem,
    clearAll,
    hasItem,
    getItemsByType
  }
})