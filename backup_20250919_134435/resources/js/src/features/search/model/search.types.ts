/**
 * TypeScript типы для функциональности поиска
 * Следуем FSD архитектуре и best practices проекта
 */

// ========== Базовые типы ==========

/**
 * Типы элементов поиска
 */
export type SearchItemType = 'service' | 'master' | 'location' | 'category'

/**
 * Приоритет подсказки
 */
export type SuggestionPriority = 'high' | 'medium' | 'low'

// ========== Интерфейсы данных ==========

/**
 * Подсказка поиска
 */
export interface SearchSuggestion {
  id?: string | number
  title: string
  category?: string
  type: SearchItemType
  url: string
  popularity?: number
  priority?: SuggestionPriority
  icon?: string
  description?: string
  metadata?: Record<string, any>
}

/**
 * Элемент истории поиска
 */
export interface SearchHistoryItem {
  query: string
  timestamp: number
  resultsCount?: number
  category?: string
}

/**
 * Популярный тег поиска
 */
export interface PopularTag {
  id: string | number
  label: string
  url?: string
  count?: number
  trending?: boolean
}

/**
 * Фильтры поиска
 */
export interface SearchFilters {
  category?: string
  location?: string
  priceRange?: [number, number]
  rating?: number
  availability?: boolean
  services?: string[]
  workFormat?: 'salon' | 'home' | 'both'
}

/**
 * Результат поиска
 */
export interface SearchResult {
  id: number
  title: string
  description: string
  image?: string
  rating: number
  price: number
  url: string
  type: 'master' | 'service'
  highlights?: string[]
}

// ========== Props компонентов ==========

/**
 * Props основного компонента SearchBar
 */
export interface SearchBarProps {
  modelValue?: string
  placeholder?: string
  maxSuggestions?: number
  debounceDelay?: number
  showHistory?: boolean
  showPopularTags?: boolean
  catalogButtonText?: string
  disabled?: boolean
  loading?: boolean
  autofocus?: boolean
}

/**
 * Props кнопки каталога
 */
export interface CatalogButtonProps {
  text?: string
  isOpen?: boolean
  disabled?: boolean
}

/**
 * Props поля ввода поиска
 */
export interface SearchInputProps {
  modelValue: string
  placeholder?: string
  disabled?: boolean
  loading?: boolean
  autofocus?: boolean
  showClearButton?: boolean
}

/**
 * Props выпадающего списка
 */
export interface SearchDropdownProps {
  show: boolean
  suggestions: SearchSuggestion[]
  history: SearchHistoryItem[]
  popularTags: PopularTag[]
  searchQuery: string
  loading?: boolean
  selectedIndex?: number
  showHistory?: boolean
  showPopularTags?: boolean
}

/**
 * Props секции истории
 */
export interface HistorySectionProps {
  items: SearchHistoryItem[]
  onSelect: (item: SearchHistoryItem) => void
  onRemove: (item: SearchHistoryItem) => void
  onClear: () => void
}

/**
 * Props секции подсказок
 */
export interface SuggestionsSectionProps {
  items: SearchSuggestion[]
  selectedIndex?: number
  onSelect: (item: SearchSuggestion) => void
  highlightQuery?: string
}

/**
 * Props секции популярных тегов
 */
export interface PopularTagsSectionProps {
  tags: PopularTag[]
  onSelect: (tag: PopularTag) => void
}

// ========== События (Emits) ==========

/**
 * События SearchBar
 */
export interface SearchBarEmits {
  (event: 'update:modelValue', value: string): void
  (event: 'search', query: string): void
  (event: 'toggle-catalog'): void
  (event: 'clear'): void
  (event: 'focus'): void
  (event: 'blur'): void
}

/**
 * События SearchInput
 */
export interface SearchInputEmits {
  (event: 'update:modelValue', value: string): void
  (event: 'enter'): void
  (event: 'clear'): void
  (event: 'focus'): void
  (event: 'blur'): void
}

/**
 * События SearchDropdown
 */
export interface SearchDropdownEmits {
  (event: 'select-suggestion', item: SearchSuggestion): void
  (event: 'select-history', item: SearchHistoryItem): void
  (event: 'select-tag', tag: PopularTag): void
  (event: 'remove-history', item: SearchHistoryItem): void
  (event: 'clear-history'): void
  (event: 'close'): void
}

// ========== Composables типы ==========

/**
 * Возвращаемый тип useSearchHistory
 */
export interface UseSearchHistoryReturn {
  history: Ref<SearchHistoryItem[]>
  recentSearches: ComputedRef<SearchHistoryItem[]>
  hasHistory: ComputedRef<boolean>
  addToHistory: (query: string, metadata?: Partial<SearchHistoryItem>) => void
  removeFromHistory: (item: SearchHistoryItem) => void
  clearHistory: () => void
  loadHistory: () => void
  saveHistory: () => void
}

/**
 * Возвращаемый тип useSearchSuggestions
 */
export interface UseSearchSuggestionsReturn {
  suggestions: Ref<SearchSuggestion[]>
  isLoading: Ref<boolean>
  error: Ref<string | null>
  loadSuggestions: (query: string) => Promise<void>
  clearSuggestions: () => void
  filterSuggestions: (query: string) => SearchSuggestion[]
}

/**
 * Опции useSearchKeyboard
 */
export interface UseSearchKeyboardOptions {
  itemsCount: number
  onSelect: (index: number) => void
  onEnter: () => void
  onEscape: () => void
  enabled?: boolean
}

/**
 * Возвращаемый тип useSearchKeyboard
 */
export interface UseSearchKeyboardReturn {
  selectedIndex: Ref<number>
  handleKeyDown: (event: KeyboardEvent) => void
  resetSelection: () => void
  selectNext: () => void
  selectPrevious: () => void
}

// ========== API типы ==========

/**
 * Ответ API на запрос подсказок
 */
export interface SuggestionsApiResponse {
  suggestions: SearchSuggestion[]
  total?: number
  categories?: string[]
}

/**
 * Параметры запроса поиска
 */
export interface SearchApiParams {
  q: string
  limit?: number
  offset?: number
  filters?: SearchFilters
  sort?: 'relevance' | 'rating' | 'price' | 'distance'
}

// ========== Утилиты типов ==========

/**
 * Конфигурация поиска
 */
export interface SearchConfig {
  apiUrl: string
  debounceDelay: number
  maxHistoryItems: number
  maxSuggestions: number
  popularTagsLimit: number
  cacheTimeout: number
}

/**
 * Статистика поиска для аналитики
 */
export interface SearchAnalytics {
  query: string
  timestamp: number
  resultsCount: number
  clickedResult?: SearchResult
  searchDuration: number
  source: 'input' | 'suggestion' | 'history' | 'tag'
}

// ========== Type Guards ==========

/**
 * Проверка, является ли объект SearchSuggestion
 */
export function isSearchSuggestion(item: any): item is SearchSuggestion {
  return item && 
    typeof item.title === 'string' && 
    typeof item.type === 'string' &&
    typeof item.url === 'string'
}

/**
 * Проверка, является ли объект SearchHistoryItem
 */
export function isSearchHistoryItem(item: any): item is SearchHistoryItem {
  return item && 
    typeof item.query === 'string' && 
    typeof item.timestamp === 'number'
}

// ========== Константы ==========

/**
 * Дефолтные значения конфигурации
 */
export const DEFAULT_SEARCH_CONFIG: SearchConfig = {
  apiUrl: '/api/search',
  debounceDelay: 300,
  maxHistoryItems: 20,
  maxSuggestions: 8,
  popularTagsLimit: 6,
  cacheTimeout: 5 * 60 * 1000 // 5 минут
}

/**
 * Клавиши навигации
 */
export const NAVIGATION_KEYS = {
  ARROW_UP: 'ArrowUp',
  ARROW_DOWN: 'ArrowDown',
  ENTER: 'Enter',
  ESCAPE: 'Escape',
  TAB: 'Tab'
} as const

// Re-export для удобства импорта
import type { Ref, ComputedRef } from 'vue'

export type { Ref, ComputedRef }