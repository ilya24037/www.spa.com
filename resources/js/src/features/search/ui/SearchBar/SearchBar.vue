<!-- Рефакторенный SearchBar с использованием базовых компонентов -->
<template>
  <div ref="containerRef" class="relative w-full max-w-4xl">
    <!-- Основной контейнер с синим фоном -->
    <div class="flex items-center h-12 bg-blue-600 border border-blue-600 rounded-lg overflow-hidden transition-all duration-200 focus-within:shadow-lg">
      
      <!-- Кнопка Каталог -->
      <button
        @click="handleToggleCatalog"
        class="flex items-center gap-1 h-full px-4 text-white hover:bg-blue-700 transition-colors whitespace-nowrap rounded-l-lg"
      >
        <svg class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <span class="hidden sm:inline font-medium">Каталог</span>
        <svg 
          class="w-3 h-3 ml-1 opacity-80 transition-transform duration-200"
          :class="{ 'rotate-180': catalogOpen }"
          fill="currentColor" 
          viewBox="0 0 24 24"
        >
          <path d="M7 10l5 5 5-5H7z"/>
        </svg>
      </button>
      
      <!-- Поле поиска с белым фоном -->
      <div class="flex-1 h-11 relative flex items-center bg-white rounded-md mx-1 my-auto">
        <label :for="searchInputId" class="sr-only">Поиск по сайту</label>
        <input
          :id="searchInputId"
          ref="searchInputRef"
          v-model="searchQuery"
          type="search"
          name="site-search"
          :placeholder="placeholder"
          :disabled="disabled"
          :autofocus="autofocus"
          :aria-label="placeholder"
          class="w-full h-full bg-transparent border-none outline-none text-gray-700 placeholder-gray-500 px-4"
          @input="handleInput"
          @keydown.enter="handleSearch"
          @focus="handleFocus"
          @blur="handleBlur"
        />
        
        <!-- Кнопка очистки -->
        <button
          v-if="searchQuery && !isLoading"
          @click="handleClear"
          class="absolute right-2 p-1 text-gray-400 hover:text-gray-600 transition-colors"
          type="button"
          aria-label="Очистить"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 8.586L4.707 3.293a1 1 0 00-1.414 1.414L8.586 10l-5.293 5.293a1 1 0 001.414 1.414L10 11.414l5.293 5.293a1 1 0 001.414-1.414L11.414 10l5.293-5.293a1 1 0 00-1.414-1.414L10 8.586z"/>
          </svg>
        </button>
        
        <!-- Индикатор загрузки -->
        <div v-if="isLoading" class="absolute right-2">
          <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
        </div>
      </div>
      
      <!-- Кнопка поиска -->
      <button
        @click="handleSearch"
        :disabled="disabled"
        class="h-full px-6 text-white hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed rounded-r-lg"
        aria-label="Найти"
      >
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
      </button>
    </div>
    
    <!-- Dropdown с подсказками используя базовый Dropdown -->
    <Dropdown
      :open="showDropdown"
      align="left"
      width="full"
      content-classes="p-0 bg-white max-h-96 overflow-y-auto"
      @close="handleCloseDropdown"
    >
      <template #content>
        <SearchDropdownContent
          :suggestions="enrichedSuggestions"
          :history="recentHistory"
          :popular-tags="popularTags"
          :search-query="searchQuery"
          :loading="isLoading"
          :selected-index="selectedIndex"
          :show-history="showHistory"
          :show-popular-tags="showPopularTags"
          @select-suggestion="handleSelectSuggestion"
          @select-history="handleSelectHistory"
          @select-tag="handleSelectTag"
          @remove-history="handleRemoveHistory"
          @clear-history="handleClearHistory"
        />
      </template>
    </Dropdown>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Базовый Dropdown компонент
import Dropdown from '@/src/shared/ui/molecules/Navigation/Dropdown.vue'

// Компонент содержимого dropdown
import SearchDropdownContent from '../SearchDropdown/SearchDropdownContent.vue'

// Composables
import { useGlobalSearchHistory } from '../../lib/useSearchHistory'
import { useSearchSuggestions } from '../../lib/useSearchSuggestions'
import { useSearchKeyboard } from '../../lib/useSearchKeyboard'
import { useId } from '@/src/shared/composables/useId'

// Types
import type { 
  SearchBarProps,
  SearchSuggestion,
  SearchHistoryItem,
  PopularTag
} from '../../model/search.types'

// Props
const props = withDefaults(defineProps<SearchBarProps>(), {
  modelValue: '',
  placeholder: 'Искать на MASSAGIST',
  maxSuggestions: 8,
  debounceDelay: 300,
  showHistory: true,
  showPopularTags: true,
  catalogButtonText: 'Каталог',
  disabled: false,
  loading: false,
  autofocus: false
})

// Emits
const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
  (event: 'search', query: string): void
  (event: 'toggle-catalog'): void
  (event: 'clear'): void
  (event: 'focus'): void
  (event: 'blur'): void
}>()

// Refs
const containerRef = ref<HTMLElement>()
const searchInputRef = ref<HTMLInputElement>()
const searchInputId = useId('search-input')

// State
const searchQuery = ref(props.modelValue)
const showDropdown = ref(false)
const catalogOpen = ref(false)
const isFocused = ref(false)

// Composables
const history = useGlobalSearchHistory()
const suggestions = useSearchSuggestions({
  debounceDelay: props.debounceDelay,
  maxSuggestions: props.maxSuggestions
})

// Популярные теги
const popularTags = ref<PopularTag[]>([
  { id: '1', label: 'массаж спины' },
  { id: '2', label: 'тайский массаж' },
  { id: '3', label: 'спа процедуры' },
  { id: '4', label: 'антицеллюлитный' },
  { id: '5', label: 'релакс массаж' },
  { id: '6', label: 'лимфодренаж' }
])

// Computed
const recentHistory = computed(() => history.recentSearches.value.slice(0, 5))

const enrichedSuggestions = computed(() => {
  return suggestions.enrichSuggestions(suggestions.suggestions.value)
})

const isLoading = computed(() => props.loading || suggestions.isLoading.value)

const totalItems = computed(() => {
  let count = 0
  if (!searchQuery.value && props.showHistory) {
    count += recentHistory.value.length
  }
  count += enrichedSuggestions.value.length
  if (!searchQuery.value && props.showPopularTags) {
    count += popularTags.value.length
  }
  return count
})

// Keyboard navigation
const keyboard = useSearchKeyboard({
  itemsCount: totalItems.value,
  onSelect: (index) => {
    handleSelectByIndex(index)
  },
  onEnter: () => {
    handleSearch()
  },
  onEscape: () => {
    handleCloseDropdown()
  },
  enabled: showDropdown.value
})

const selectedIndex = computed(() => keyboard.selectedIndex.value)

// Watchers
watch(() => props.modelValue, (newValue) => {
  searchQuery.value = newValue
})

watch(searchQuery, (newQuery) => {
  emit('update:modelValue', newQuery)
  
  if (newQuery) {
    suggestions.loadSuggestions(newQuery)
    showDropdown.value = true
  } else {
    suggestions.clearSuggestions()
  }
  
  // Сбрасываем выделение при изменении запроса
  keyboard.resetSelection()
})

watch(totalItems, (newCount) => {
  // Обновляем количество элементов для навигации
  keyboard.resetSelection()
})

// Methods
const handleToggleCatalog = () => {
  catalogOpen.value = !catalogOpen.value
  emit('toggle-catalog')
}

const handleInput = (value: string) => {
  searchQuery.value = value
}

const handleSearch = () => {
  const query = searchQuery.value.trim()
  if (!query) return
  
  // Добавляем в историю
  history.addToHistory(query)
  
  // Закрываем dropdown
  showDropdown.value = false
  
  // Выполняем поиск
  emit('search', query)
  router.get('/search', { q: query })
}

const handleClear = () => {
  searchQuery.value = ''
  suggestions.clearSuggestions()
  keyboard.resetSelection()
  emit('clear')
}

const handleFocus = () => {
  isFocused.value = true
  showDropdown.value = true
  emit('focus')
}

const handleBlur = () => {
  // Задержка для возможности клика по элементам dropdown
  setTimeout(() => {
    if (!isFocused.value) {
      showDropdown.value = false
    }
  }, 200)
  
  isFocused.value = false
  emit('blur')
}

const handleSelectSuggestion = (suggestion: SearchSuggestion) => {
  searchQuery.value = suggestion.title
  history.addToHistory(suggestion.title, { category: suggestion.category })
  handleSearch()
}

const handleSelectHistory = (item: SearchHistoryItem) => {
  searchQuery.value = item.query
  handleSearch()
}

const handleSelectTag = (tag: PopularTag) => {
  searchQuery.value = tag.label
  handleSearch()
}

const handleRemoveHistory = (item: SearchHistoryItem) => {
  history.removeFromHistory(item)
}

const handleClearHistory = () => {
  history.clearHistory()
}

const handleCloseDropdown = () => {
  showDropdown.value = false
  keyboard.resetSelection()
  if (searchInputRef.value) {
    searchInputRef.value.blur()
  }
}

const handleSelectByIndex = (index: number) => {
  const historyLength = !searchQuery.value && props.showHistory ? recentHistory.value.length : 0
  const suggestionsLength = enrichedSuggestions.value.length
  
  if (index < historyLength) {
    // Выбран элемент истории
    handleSelectHistory(recentHistory.value[index])
  } else if (index < historyLength + suggestionsLength) {
    // Выбрана подсказка
    handleSelectSuggestion(enrichedSuggestions.value[index - historyLength])
  } else {
    // Выбран популярный тег
    const tagIndex = index - historyLength - suggestionsLength
    if (tagIndex < popularTags.value.length) {
      handleSelectTag(popularTags.value[tagIndex])
    }
  }
}

// Keyboard event handling
const handleKeyDown = (event: KeyboardEvent) => {
  if (showDropdown.value) {
    keyboard.handleKeyDown(event)
  }
}

// Lifecycle
onMounted(() => {
  // Загружаем историю
  history.loadHistory()
  
  // Добавляем обработчик клавиатуры
  if (containerRef.value) {
    containerRef.value.addEventListener('keydown', handleKeyDown)
  }
  
  // Получаем популярные подсказки
  if (!searchQuery.value) {
    const popular = suggestions.getPopularSuggestions(6)
    if (popular.length > 0) {
      suggestions.suggestions.value = popular
    }
  }
})

// Public API
defineExpose({
  focus: () => {
    if (searchInputRef.value) {
      searchInputRef.value.focus()
    }
  },
  blur: () => {
    if (searchInputRef.value) {
      searchInputRef.value.blur()
    }
  },
  clear: handleClear,
  search: handleSearch
})
</script>

<style scoped>
/* Скрытие элементов визуально, но оставляя доступными для скринридеров */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
/* Убираем стандартную кнопку очистки в поле поиска */
input[type="search"]::-webkit-search-cancel-button,
input[type="search"]::-webkit-search-decoration,
input[type="search"]::-webkit-search-results-button,
input[type="search"]::-webkit-search-results-decoration {
  -webkit-appearance: none;
  appearance: none;
}

/* Стили для Firefox */
input[type="search"] {
  -moz-appearance: textfield;
}
</style>