<!-- Содержимое выпадающего списка поиска для использования с базовым Dropdown -->
<template>
  <div class="search-dropdown-content">
    <!-- История поиска -->
    <div 
      v-if="showHistory && history.length > 0 && !searchQuery" 
      class="py-3 border-b border-gray-100"
    >
      <div class="px-4 pb-2 flex items-center justify-between">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
          История поиска
        </span>
        <button
          @click="$emit('clear-history')"
          class="text-xs text-gray-400 hover:text-red-500 transition-colors"
          type="button"
        >
          Очистить
        </button>
      </div>
      <div
        v-for="(item, index) in history"
        :key="'history-' + item.timestamp"
        @mousedown.prevent="selectHistoryItem(item)"
        :class="getItemClasses(index, 'history')"
        class="flex items-center gap-3 w-full px-4 py-2 hover:bg-gray-50 transition-colors cursor-pointer"
        :data-search-item="true"
        :aria-selected="selectedIndex === index"
        role="button"
        tabindex="0"
      >
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2M7 5.5v3l2.5 1.5.5-.8-2-1.2V5.5z"/>
        </svg>
        <span class="text-sm text-gray-700 flex-1 truncate">{{ item.query }}</span>
        <button
          @click.stop="removeHistoryItem(item)"
          @mousedown.stop
          class="text-gray-400 hover:text-red-500 p-1 rounded-full hover:bg-gray-100"
          type="button"
          aria-label="Удалить из истории"
        >
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 8.586L4.707 3.293a1 1 0 00-1.414 1.414L8.586 10l-5.293 5.293a1 1 0 001.414 1.414L10 11.414l5.293 5.293a1 1 0 001.414-1.414L11.414 10l5.293-5.293a1 1 0 00-1.414-1.414L10 8.586z"/>
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Подсказки -->
    <div 
      v-if="suggestions.length > 0" 
      class="py-3"
      :class="{ 'border-b border-gray-100': showPopularTags && popularTags.length > 0 && !searchQuery }"
    >
      <div v-if="searchQuery" class="px-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
        Возможно, вы ищете
      </div>
      <button
        v-for="(suggestion, index) in suggestions"
        :key="'suggestion-' + index"
        @mousedown.prevent="selectSuggestion(suggestion)"
        :class="getItemClasses(getHistoryOffset() + index, 'suggestion')"
        class="flex items-center gap-3 w-full px-4 py-2 hover:bg-gray-50 transition-colors text-left"
        type="button"
        :data-search-item="true"
        :aria-selected="selectedIndex === getHistoryOffset() + index"
      >
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.089.083.176.17l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.193-.179zm-5.242.658a5 5 0 1 1 0-10 5 5 0 0 1 0 10"/>
        </svg>
        <div class="flex-1 min-w-0">
          <div class="text-sm text-gray-700 truncate" v-html="highlightMatch(suggestion.title, searchQuery)"></div>
          <div v-if="suggestion.category" class="text-xs text-gray-500">{{ suggestion.category }}</div>
        </div>
        <span v-if="suggestion.type === 'service'" class="text-xs text-blue-600 font-medium">
          Услуга
        </span>
        <span v-else-if="suggestion.type === 'master'" class="text-xs text-green-600 font-medium">
          Мастер
        </span>
      </button>
    </div>
    
    <!-- Популярные запросы -->
    <div 
      v-if="showPopularTags && popularTags.length > 0 && !searchQuery" 
      class="py-3"
    >
      <div class="px-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
        Популярные запросы
      </div>
      <div class="flex flex-wrap gap-2 px-4">
        <button
          v-for="(tag, index) in popularTags"
          :key="'tag-' + index"
          @click="selectTag(tag)"
          :class="getTagClasses(getFullOffset() + index)"
          class="px-3 py-1.5 bg-gray-100 hover:bg-blue-600 hover:text-white text-sm text-gray-700 rounded-full transition-colors"
          type="button"
          :data-search-item="true"
          :aria-selected="selectedIndex === getFullOffset() + index"
        >
          {{ tag.label }}
        </button>
      </div>
    </div>

    <!-- Пустое состояние -->
    <div 
      v-if="!loading && suggestions.length === 0 && history.length === 0 && popularTags.length === 0"
      class="py-8 text-center text-gray-500"
    >
      <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
      </svg>
      <p class="text-sm">Начните вводить запрос</p>
    </div>

    <!-- Индикатор загрузки -->
    <div v-if="loading" class="py-4 text-center">
      <svg class="animate-spin h-6 w-6 text-blue-600 mx-auto"
           xmlns="http://www.w3.org/2000/svg"
           fill="none"
           viewBox="0 0 24 24">
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
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { 
  SearchSuggestion,
  SearchHistoryItem,
  PopularTag
} from '../../model/search.types'

// Props
interface Props {
  suggestions?: SearchSuggestion[]
  history?: SearchHistoryItem[]
  popularTags?: PopularTag[]
  searchQuery?: string
  loading?: boolean
  selectedIndex?: number
  showHistory?: boolean
  showPopularTags?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  suggestions: () => [],
  history: () => [],
  popularTags: () => [],
  searchQuery: '',
  loading: false,
  selectedIndex: -1,
  showHistory: true,
  showPopularTags: true
})

// Emits
const emit = defineEmits<{
  (event: 'select-suggestion', item: SearchSuggestion): void
  (event: 'select-history', item: SearchHistoryItem): void
  (event: 'select-tag', tag: PopularTag): void
  (event: 'remove-history', item: SearchHistoryItem): void
  (event: 'clear-history'): void
}>()

// Methods
const getHistoryOffset = () => {
  return props.searchQuery ? 0 : props.history.length
}

const getFullOffset = () => {
  return getHistoryOffset() + props.suggestions.length
}

const getItemClasses = (index: number, type: 'history' | 'suggestion') => {
  const classes = []
  if (props.selectedIndex === index) {
    classes.push('bg-gray-100 outline outline-2 outline-blue-500')
  }
  return classes.join(' ')
}

const getTagClasses = (index: number) => {
  const classes = []
  if (props.selectedIndex === index) {
    classes.push('ring-2 ring-blue-500 ring-offset-2')
  }
  return classes.join(' ')
}

const selectHistoryItem = (item: SearchHistoryItem) => {
  emit('select-history', item)
}

const removeHistoryItem = (item: SearchHistoryItem) => {
  emit('remove-history', item)
}

const selectSuggestion = (suggestion: SearchSuggestion) => {
  emit('select-suggestion', suggestion)
}

const selectTag = (tag: PopularTag) => {
  emit('select-tag', tag)
}

const highlightMatch = (text: string, query: string): string => {
  if (!query) return text
  
  const regex = new RegExp(`(${query})`, 'gi')
  return text.replace(regex, '<mark class="bg-yellow-200 font-medium">$1</mark>')
}
</script>

<style scoped>
.search-dropdown-content {
  @apply w-full;
}

/* Убираем стандартные стили фокуса для кнопок в dropdown */
.search-dropdown-content button:focus {
  @apply outline-none;
}
</style>