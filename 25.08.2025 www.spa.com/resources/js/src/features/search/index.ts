/**
 * Экспорт функциональности поиска
 */

// Компоненты
export { default as SearchBar } from './ui/SearchBar/SearchBar.vue'
export { default as SearchDropdownContent } from './ui/SearchDropdown/SearchDropdownContent.vue'

// Старые компоненты (deprecated - будут удалены)
// export { default as CatalogButton } from './ui/CatalogButton/CatalogButton.vue'
// export { default as SearchInput } from './ui/SearchInput/SearchInput.vue'
// export { default as SearchDropdown } from './ui/SearchDropdown/SearchDropdown.vue'

// Composables
export { useSearchHistory, useGlobalSearchHistory } from './lib/useSearchHistory'
export { useSearchSuggestions } from './lib/useSearchSuggestions'
export { useSearchKeyboard, useKeyboardNavigation } from './lib/useSearchKeyboard'

// Store
export { useSearchStore } from './model/search.store'

// Types
export type {
  // Базовые типы
  SearchItemType,
  SuggestionPriority,
  
  // Интерфейсы данных
  SearchSuggestion,
  SearchHistoryItem,
  PopularTag,
  SearchFilters,
  SearchResult,
  
  // Props компонентов
  SearchBarProps,
  CatalogButtonProps,
  SearchInputProps,
  SearchDropdownProps,
  
  // Emits
  SearchBarEmits,
  SearchInputEmits,
  SearchDropdownEmits,
  
  // Composables
  UseSearchHistoryReturn,
  UseSearchSuggestionsReturn,
  UseSearchKeyboardOptions,
  UseSearchKeyboardReturn,
  
  // API
  SuggestionsApiResponse,
  SearchApiParams,
  
  // Утилиты
  SearchConfig,
  SearchAnalytics
} from './model/search.types'

// Константы
export { DEFAULT_SEARCH_CONFIG, NAVIGATION_KEYS } from './model/search.types'

// Хелперы
export { 
  formatHistoryTime, 
  groupHistoryByDate 
} from './lib/useSearchHistory'

export { 
  highlightMatch, 
  groupSuggestionsByCategory,
  sortSuggestions
} from './lib/useSearchSuggestions'

export {
  createListNavigation,
  useFocusTrap
} from './lib/useSearchKeyboard'