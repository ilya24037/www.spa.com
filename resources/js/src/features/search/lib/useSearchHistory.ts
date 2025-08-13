/**
 * Composable для управления историей поиска
 * Сохраняет историю в localStorage и предоставляет методы управления
 */

import { ref, computed, type Ref, type ComputedRef } from 'vue'
import type { 
  SearchHistoryItem, 
  UseSearchHistoryReturn 
} from '../model/search.types'

const STORAGE_KEY = 'search_history'
const MAX_HISTORY_ITEMS = 20
const MAX_RECENT_ITEMS = 5

/**
 * Composable для работы с историей поиска
 */
export function useSearchHistory(): UseSearchHistoryReturn {
  // State
  const history = ref<SearchHistoryItem[]>([])
  const isLoaded = ref(false)

  /**
   * Последние поисковые запросы (для отображения)
   */
  const recentSearches = computed(() => {
    return history.value
      .slice(0, MAX_RECENT_ITEMS)
      .sort((a, b) => b.timestamp - a.timestamp)
  })

  /**
   * Есть ли история поиска
   */
  const hasHistory = computed(() => history.value.length > 0)

  /**
   * Загрузка истории из localStorage
   */
  const loadHistory = (): void => {
    if (isLoaded.value) return

    try {
      const saved = localStorage.getItem(STORAGE_KEY)
      if (saved) {
        const parsed = JSON.parse(saved) as SearchHistoryItem[]
        
        // Валидация и очистка данных
        history.value = parsed
          .filter(item => 
            item && 
            typeof item.query === 'string' && 
            typeof item.timestamp === 'number'
          )
          .slice(0, MAX_HISTORY_ITEMS)
      }
    } catch (error) {
      console.warn('Failed to load search history:', error)
      history.value = []
    }

    isLoaded.value = true
  }

  /**
   * Сохранение истории в localStorage
   */
  const saveHistory = (): void => {
    try {
      const dataToSave = history.value.slice(0, MAX_HISTORY_ITEMS)
      localStorage.setItem(STORAGE_KEY, JSON.stringify(dataToSave))
    } catch (error) {
      console.warn('Failed to save search history:', error)
    }
  }

  /**
   * Добавление запроса в историю
   */
  const addToHistory = (
    query: string, 
    metadata: Partial<SearchHistoryItem> = {}
  ): void => {
    const trimmedQuery = query.trim()
    if (!trimmedQuery) return

    // Удаляем существующий запрос, если есть
    const existingIndex = history.value.findIndex(
      item => item.query.toLowerCase() === trimmedQuery.toLowerCase()
    )
    
    if (existingIndex > -1) {
      history.value.splice(existingIndex, 1)
    }

    // Создаем новый элемент истории
    const newItem: SearchHistoryItem = {
      query: trimmedQuery,
      timestamp: Date.now(),
      resultsCount: metadata.resultsCount,
      category: metadata.category
    }

    // Добавляем в начало
    history.value.unshift(newItem)

    // Ограничиваем размер истории
    if (history.value.length > MAX_HISTORY_ITEMS) {
      history.value = history.value.slice(0, MAX_HISTORY_ITEMS)
    }

    // Сохраняем в localStorage
    saveHistory()
  }

  /**
   * Удаление элемента из истории
   */
  const removeFromHistory = (item: SearchHistoryItem): void => {
    const index = history.value.findIndex(
      h => h.query === item.query && h.timestamp === item.timestamp
    )
    
    if (index > -1) {
      history.value.splice(index, 1)
      saveHistory()
    }
  }

  /**
   * Очистка всей истории
   */
  const clearHistory = (): void => {
    history.value = []
    
    try {
      localStorage.removeItem(STORAGE_KEY)
    } catch (error) {
      console.warn('Failed to clear search history:', error)
    }
  }

  /**
   * Обновление метаданных существующего элемента
   */
  const updateHistoryItem = (
    query: string, 
    updates: Partial<SearchHistoryItem>
  ): void => {
    const item = history.value.find(
      h => h.query.toLowerCase() === query.toLowerCase()
    )
    
    if (item) {
      Object.assign(item, updates)
      saveHistory()
    }
  }

  /**
   * Получение элемента истории по запросу
   */
  const getHistoryItem = (query: string): SearchHistoryItem | undefined => {
    return history.value.find(
      h => h.query.toLowerCase() === query.toLowerCase()
    )
  }

  /**
   * Проверка, есть ли запрос в истории
   */
  const isInHistory = (query: string): boolean => {
    return history.value.some(
      h => h.query.toLowerCase() === query.toLowerCase()
    )
  }

  /**
   * Получение истории по категории
   */
  const getHistoryByCategory = (category: string): SearchHistoryItem[] => {
    return history.value.filter(item => item.category === category)
  }

  /**
   * Получение популярных запросов (с наибольшим количеством результатов)
   */
  const getPopularSearches = (limit: number = 5): SearchHistoryItem[] => {
    return [...history.value]
      .filter(item => item.resultsCount && item.resultsCount > 0)
      .sort((a, b) => (b.resultsCount || 0) - (a.resultsCount || 0))
      .slice(0, limit)
  }

  /**
   * Экспорт истории в JSON
   */
  const exportHistory = (): string => {
    return JSON.stringify(history.value, null, 2)
  }

  /**
   * Импорт истории из JSON
   */
  const importHistory = (jsonString: string): boolean => {
    try {
      const imported = JSON.parse(jsonString) as SearchHistoryItem[]
      
      if (Array.isArray(imported)) {
        history.value = imported
          .filter(item => 
            item && 
            typeof item.query === 'string' && 
            typeof item.timestamp === 'number'
          )
          .slice(0, MAX_HISTORY_ITEMS)
        
        saveHistory()
        return true
      }
    } catch (error) {
      console.error('Failed to import search history:', error)
    }
    
    return false
  }

  // Автоматическая загрузка при создании
  loadHistory()

  return {
    // Основные свойства
    history,
    recentSearches,
    hasHistory,
    
    // Основные методы
    addToHistory,
    removeFromHistory,
    clearHistory,
    loadHistory,
    saveHistory,
    
    // Дополнительные методы
    updateHistoryItem,
    getHistoryItem,
    isInHistory,
    getHistoryByCategory,
    getPopularSearches,
    exportHistory,
    importHistory
  }
}

/**
 * Глобальный экземпляр для переиспользования между компонентами
 */
let globalHistoryInstance: UseSearchHistoryReturn | null = null

/**
 * Получение глобального экземпляра истории поиска
 */
export function useGlobalSearchHistory(): UseSearchHistoryReturn {
  if (!globalHistoryInstance) {
    globalHistoryInstance = useSearchHistory()
  }
  return globalHistoryInstance
}

/**
 * Хелпер для форматирования времени в истории
 */
export function formatHistoryTime(timestamp: number): string {
  const now = Date.now()
  const diff = now - timestamp
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Только что'
  if (minutes < 60) return `${minutes} мин. назад`
  if (hours < 24) return `${hours} ч. назад`
  if (days < 7) return `${days} дн. назад`
  
  return new Date(timestamp).toLocaleDateString('ru-RU')
}

/**
 * Хелпер для группировки истории по дате
 */
export function groupHistoryByDate(
  items: SearchHistoryItem[]
): Record<string, SearchHistoryItem[]> {
  const groups: Record<string, SearchHistoryItem[]> = {}
  const now = new Date()
  const today = now.toDateString()
  const yesterday = new Date(now.getTime() - 86400000).toDateString()

  items.forEach(item => {
    const itemDate = new Date(item.timestamp).toDateString()
    let groupKey: string

    if (itemDate === today) {
      groupKey = 'Сегодня'
    } else if (itemDate === yesterday) {
      groupKey = 'Вчера'
    } else {
      groupKey = new Date(item.timestamp).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long'
      })
    }

    if (!groups[groupKey]) {
      groups[groupKey] = []
    }
    groups[groupKey].push(item)
  })

  return groups
}