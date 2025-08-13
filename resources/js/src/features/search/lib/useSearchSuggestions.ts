/**
 * Composable для управления подсказками поиска
 * Загружает подсказки с сервера или использует локальные данные
 */

import { ref, computed, type Ref } from 'vue'
import { debounce } from '@/src/shared/lib/utils'
import { useSearchStore } from '../model/search.store'
import type { 
  SearchSuggestion, 
  UseSearchSuggestionsReturn,
  SearchFilters
} from '../model/search.types'

// Дефолтные популярные подсказки
const DEFAULT_SUGGESTIONS: SearchSuggestion[] = [
  { 
    title: 'Классический массаж', 
    category: 'Массаж', 
    type: 'service', 
    url: '/services/classic-massage',
    popularity: 95
  },
  { 
    title: 'Тайский массаж', 
    category: 'Массаж', 
    type: 'service', 
    url: '/services/thai-massage',
    popularity: 88
  },
  { 
    title: 'Массаж спины', 
    category: 'Массаж', 
    type: 'service', 
    url: '/services/back-massage',
    popularity: 92
  },
  { 
    title: 'СПА процедуры', 
    category: 'Релакс', 
    type: 'service', 
    url: '/services/spa',
    popularity: 85
  },
  { 
    title: 'Антицеллюлитный массаж', 
    category: 'Коррекция', 
    type: 'service', 
    url: '/services/anti-cellulite',
    popularity: 78
  },
  { 
    title: 'Релакс массаж', 
    category: 'Релакс', 
    type: 'service', 
    url: '/services/relax-massage',
    popularity: 82
  },
  { 
    title: 'Лимфодренажный массаж', 
    category: 'Оздоровительный', 
    type: 'service', 
    url: '/services/lymphatic-drainage',
    popularity: 75
  },
  { 
    title: 'Спортивный массаж', 
    category: 'Спортивный', 
    type: 'service', 
    url: '/services/sports-massage',
    popularity: 70
  }
]

/**
 * Опции для composable
 */
interface UseSearchSuggestionsOptions {
  debounceDelay?: number
  maxSuggestions?: number
  useCache?: boolean
  cacheTimeout?: number
}

/**
 * Кеш для подсказок
 */
interface SuggestionsCache {
  [query: string]: {
    suggestions: SearchSuggestion[]
    timestamp: number
  }
}

/**
 * Composable для работы с подсказками поиска
 */
export function useSearchSuggestions(
  options: UseSearchSuggestionsOptions = {}
): UseSearchSuggestionsReturn {
  const {
    debounceDelay = 300,
    maxSuggestions = 8,
    useCache = true,
    cacheTimeout = 5 * 60 * 1000 // 5 минут
  } = options

  // Store
  const searchStore = useSearchStore()

  // State
  const suggestions = ref<SearchSuggestion[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const cache = ref<SuggestionsCache>({})
  const abortController = ref<AbortController | null>(null)

  /**
   * Очистка кеша от устаревших записей
   */
  const cleanupCache = (): void => {
    const now = Date.now()
    Object.keys(cache.value).forEach(key => {
      if (now - cache.value[key].timestamp > cacheTimeout) {
        delete cache.value[key]
      }
    })
  }

  /**
   * Получение подсказок из кеша
   */
  const getCachedSuggestions = (query: string): SearchSuggestion[] | null => {
    if (!useCache) return null

    cleanupCache()
    const cached = cache.value[query.toLowerCase()]
    
    if (cached && Date.now() - cached.timestamp < cacheTimeout) {
      return cached.suggestions
    }
    
    return null
  }

  /**
   * Сохранение подсказок в кеш
   */
  const setCachedSuggestions = (query: string, items: SearchSuggestion[]): void => {
    if (!useCache) return

    cache.value[query.toLowerCase()] = {
      suggestions: items,
      timestamp: Date.now()
    }
  }

  /**
   * Загрузка подсказок с сервера
   */
  const loadSuggestionsFromAPI = async (query: string): Promise<SearchSuggestion[]> => {
    // Отменяем предыдущий запрос
    if (abortController.value) {
      abortController.value.abort()
    }

    // Создаем новый контроллер для отмены
    abortController.value = new AbortController()

    try {
      // Используем store для загрузки
      await searchStore.loadSuggestions(query)
      return searchStore.suggestions || []
    } catch (err: any) {
      if (err.name === 'AbortError') {
        // Запрос был отменен, это нормально
        return []
      }
      throw err
    }
  }

  /**
   * Фильтрация локальных подсказок
   */
  const filterLocalSuggestions = (query: string): SearchSuggestion[] => {
    const lowerQuery = query.toLowerCase()
    
    return DEFAULT_SUGGESTIONS
      .filter(item => {
        const titleMatch = item.title.toLowerCase().includes(lowerQuery)
        const categoryMatch = item.category?.toLowerCase().includes(lowerQuery)
        return titleMatch || categoryMatch
      })
      .sort((a, b) => {
        // Сортировка по релевантности
        const aStartsWith = a.title.toLowerCase().startsWith(lowerQuery)
        const bStartsWith = b.title.toLowerCase().startsWith(lowerQuery)
        
        if (aStartsWith && !bStartsWith) return -1
        if (!aStartsWith && bStartsWith) return 1
        
        // Затем по популярности
        return (b.popularity || 0) - (a.popularity || 0)
      })
      .slice(0, maxSuggestions)
  }

  /**
   * Основная функция загрузки подсказок
   */
  const loadSuggestions = async (query: string): Promise<void> => {
    const trimmedQuery = query.trim()
    
    // Если запрос пустой, показываем популярные
    if (!trimmedQuery) {
      suggestions.value = DEFAULT_SUGGESTIONS
        .sort((a, b) => (b.popularity || 0) - (a.popularity || 0))
        .slice(0, maxSuggestions)
      return
    }

    // Проверяем кеш
    const cached = getCachedSuggestions(trimmedQuery)
    if (cached) {
      suggestions.value = cached
      return
    }

    isLoading.value = true
    error.value = null

    try {
      // Пробуем загрузить с сервера
      const apiSuggestions = await loadSuggestionsFromAPI(trimmedQuery)
      
      if (apiSuggestions.length > 0) {
        suggestions.value = apiSuggestions.slice(0, maxSuggestions)
        setCachedSuggestions(trimmedQuery, suggestions.value)
      } else {
        // Fallback на локальные данные
        suggestions.value = filterLocalSuggestions(trimmedQuery)
      }
    } catch (err: any) {
      // При ошибке используем локальные данные
      console.warn('Failed to load suggestions from API:', err)
      suggestions.value = filterLocalSuggestions(trimmedQuery)
      error.value = 'Не удалось загрузить подсказки'
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Загрузка с debounce
   */
  const debouncedLoadSuggestions = debounce(loadSuggestions, debounceDelay)

  /**
   * Очистка подсказок
   */
  const clearSuggestions = (): void => {
    suggestions.value = []
    error.value = null
    isLoading.value = false
    
    // Отменяем активный запрос
    if (abortController.value) {
      abortController.value.abort()
      abortController.value = null
    }
  }

  /**
   * Фильтрация существующих подсказок
   */
  const filterSuggestions = (query: string): SearchSuggestion[] => {
    const lowerQuery = query.toLowerCase()
    
    return suggestions.value.filter(item =>
      item.title.toLowerCase().includes(lowerQuery) ||
      item.category?.toLowerCase().includes(lowerQuery)
    )
  }

  /**
   * Добавление пользовательской подсказки
   */
  const addCustomSuggestion = (suggestion: SearchSuggestion): void => {
    const exists = suggestions.value.some(
      s => s.title === suggestion.title && s.type === suggestion.type
    )
    
    if (!exists) {
      suggestions.value.unshift(suggestion)
      
      // Ограничиваем количество
      if (suggestions.value.length > maxSuggestions) {
        suggestions.value = suggestions.value.slice(0, maxSuggestions)
      }
    }
  }

  /**
   * Получение подсказок по типу
   */
  const getSuggestionsByType = (type: SearchSuggestion['type']): SearchSuggestion[] => {
    return suggestions.value.filter(s => s.type === type)
  }

  /**
   * Получение популярных подсказок
   */
  const getPopularSuggestions = (limit: number = 6): SearchSuggestion[] => {
    return DEFAULT_SUGGESTIONS
      .sort((a, b) => (b.popularity || 0) - (a.popularity || 0))
      .slice(0, limit)
  }

  /**
   * Обогащение подсказок дополнительными данными
   */
  const enrichSuggestions = (items: SearchSuggestion[]): SearchSuggestion[] => {
    return items.map(item => ({
      ...item,
      icon: getIconForType(item.type),
      priority: calculatePriority(item)
    }))
  }

  /**
   * Получение иконки для типа
   */
  const getIconForType = (type: SearchSuggestion['type']): string => {
    const icons = {
      service: '🎯',
      master: '👤',
      location: '📍',
      category: '📂'
    }
    return icons[type] || '🔍'
  }

  /**
   * Расчет приоритета подсказки
   */
  const calculatePriority = (item: SearchSuggestion): SearchSuggestion['priority'] => {
    const popularity = item.popularity || 0
    
    if (popularity > 90) return 'high'
    if (popularity > 70) return 'medium'
    return 'low'
  }

  /**
   * Сброс состояния
   */
  const reset = (): void => {
    clearSuggestions()
    cache.value = {}
  }

  return {
    // Основные свойства
    suggestions,
    isLoading,
    error,
    
    // Основные методы
    loadSuggestions: debouncedLoadSuggestions,
    clearSuggestions,
    filterSuggestions,
    
    // Дополнительные методы
    addCustomSuggestion,
    getSuggestionsByType,
    getPopularSuggestions,
    enrichSuggestions,
    reset
  }
}

/**
 * Хелпер для подсветки совпадений в тексте
 */
export function highlightMatch(text: string, query: string): string {
  if (!query) return text
  
  const regex = new RegExp(`(${query})`, 'gi')
  return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>')
}

/**
 * Хелпер для группировки подсказок по категориям
 */
export function groupSuggestionsByCategory(
  items: SearchSuggestion[]
): Record<string, SearchSuggestion[]> {
  const groups: Record<string, SearchSuggestion[]> = {}
  
  items.forEach(item => {
    const category = item.category || 'Другое'
    if (!groups[category]) {
      groups[category] = []
    }
    groups[category].push(item)
  })
  
  return groups
}

/**
 * Хелпер для сортировки подсказок
 */
export function sortSuggestions(
  items: SearchSuggestion[],
  sortBy: 'relevance' | 'popularity' | 'alphabetical' = 'relevance'
): SearchSuggestion[] {
  const sorted = [...items]
  
  switch (sortBy) {
    case 'popularity':
      return sorted.sort((a, b) => (b.popularity || 0) - (a.popularity || 0))
    
    case 'alphabetical':
      return sorted.sort((a, b) => a.title.localeCompare(b.title, 'ru'))
    
    case 'relevance':
    default:
      // Предполагаем, что они уже отсортированы по релевантности
      return sorted
  }
}