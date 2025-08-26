import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { logger } from '@/src/shared/utils/logger'

// TypeScript интерфейсы
export interface SearchSuggestion {
  title: string
  category?: string
  type: 'service' | 'master' | 'location'
  url: string
  popularity?: number
}

export interface SearchFilters {
  category?: string
  location?: string
  priceRange?: [number, number]
  rating?: number
  availability?: boolean
}

export interface SearchResult {
  id: number
  title: string
  description: string
  image?: string
  rating: number
  price: number
  url: string
  type: 'master' | 'service'
}

export const useSearchStore = defineStore('search', () => {
  // State
  const suggestions = ref<SearchSuggestion[]>([])
  const searchHistory = ref<string[]>([])
  const recentResults = ref<SearchResult[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Популярные запросы по умолчанию
  const popularSearches: SearchSuggestion[] = [
    { title: 'Классический массаж', category: 'Массаж', type: 'service', url: '/services/classic-massage' },
    { title: 'Тайский массаж', category: 'Массаж', type: 'service', url: '/services/thai-massage' },
    { title: 'Массаж спины', category: 'Массаж', type: 'service', url: '/services/back-massage' },
    { title: 'СПА процедуры', category: 'Релакс', type: 'service', url: '/services/spa' },
    { title: 'Антицеллюлитный массаж', category: 'Коррекция', type: 'service', url: '/services/anti-cellulite' },
    { title: 'Релакс массаж', category: 'Релакс', type: 'service', url: '/services/relax-massage' },
    { title: 'Мастера в центре', category: 'Расположение', type: 'location', url: '/masters?location=center' },
    { title: 'Мастера рядом с метро', category: 'Расположение', type: 'location', url: '/masters?near_metro=true' }
  ]
  
  // Getters
  const hasHistory = computed(() => searchHistory.value.length > 0)
  const recentSearches = computed(() => searchHistory.value.slice(-5).reverse())
  
  // Actions
  const loadSuggestions = async (query: string): Promise<void> => {
    if (!query.trim()) {
      suggestions.value = popularSearches.slice(0, 6)
      return
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      // Попробовать загрузить с сервера
      const response = await axios.get('/api/search/suggestions', {
        params: { q: query, limit: 8 }
      })
      
      suggestions.value = response.data.suggestions || []
    } catch (err) {
      // Fallback на локальный поиск
      suggestions.value = popularSearches
        .filter(item => 
          item.title.toLowerCase().includes(query.toLowerCase()) ||
          (item.category && item.category.toLowerCase().includes(query.toLowerCase()))
        )
        .slice(0, 6)
        
      logger.warn('Failed to load search suggestions from API, using fallback:', err)
    } finally {
      isLoading.value = false
    }
  }
  
  const addToHistory = (query: string): void => {
    const trimmedQuery = query.trim()
    if (!trimmedQuery) return
    
    // Удалить существующий запрос если есть
    const index = searchHistory.value.indexOf(trimmedQuery)
    if (index > -1) {
      searchHistory.value.splice(index, 1)
    }
    
    // Добавить в начало
    searchHistory.value.unshift(trimmedQuery)
    
    // Ограничить до 20 записей
    if (searchHistory.value.length > 20) {
      searchHistory.value = searchHistory.value.slice(0, 20)
    }
    
    // Сохранить в localStorage
    try {
      localStorage.setItem('search_history', JSON.stringify(searchHistory.value))
    } catch (err) {
      logger.warn('Failed to save search history:', err)
    }
  }
  
  const clearHistory = (): void => {
    searchHistory.value = []
    try {
      localStorage.removeItem('search_history')
    } catch (err) {
      logger.warn('Failed to clear search history:', err)
    }
  }
  
  const removeFromHistory = (query: string): void => {
    const index = searchHistory.value.indexOf(query)
    if (index > -1) {
      searchHistory.value.splice(index, 1)
      try {
        localStorage.setItem('search_history', JSON.stringify(searchHistory.value))
      } catch (err) {
        logger.warn('Failed to update search history:', err)
      }
    }
  }
  
  const loadHistory = (): void => {
    try {
      const saved = localStorage.getItem('search_history')
      if (saved) {
        searchHistory.value = JSON.parse(saved)
      }
    } catch (err) {
      logger.warn('Failed to load search history:', err)
      searchHistory.value = []
    }
  }
  
  const performSearch = async (query: string, filters?: SearchFilters): Promise<SearchResult[]> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/search', {
        params: {
          q: query,
          ...filters
        }
      })
      
      const results = response.data.results || []
      recentResults.value = results
      
      // Добавить в историю
      addToHistory(query)
      
      return results
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Ошибка поиска'
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  const clearError = (): void => {
    error.value = null
  }
  
  const reset = (): void => {
    suggestions.value = []
    recentResults.value = []
    error.value = null
    isLoading.value = false
  }
  
  // Инициализация при создании store
  loadHistory()
  
  return {
    // State
    suggestions,
    searchHistory,
    recentResults,
    isLoading,
    error,
    
    // Getters
    hasHistory,
    recentSearches,
    
    // Actions
    loadSuggestions,
    addToHistory,
    clearHistory,
    removeFromHistory,
    loadHistory,
    performSearch,
    clearError,
    reset
  }
})