<!-- Р“Р»РѕР±Р°Р»СЊРЅС‹Р№ РїРѕРёСЃРє - FSD Feature -->
<template>
  <div class="flex items-center flex-1 max-w-3xl">
    <!-- РљРЅРѕРїРєР° РєР°С‚Р°Р»РѕРіР° -->
    <button 
      @click="toggleCatalog"
      :class="catalogButtonClasses"
      :aria-expanded="catalogOpen"
      aria-label="РћС‚РєСЂС‹С‚СЊ РєР°С‚Р°Р»РѕРі СѓСЃР»СѓРі"
    >
      <svg 
        class="w-5 h-5 mr-2" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M4 6h16M4 12h16M4 18h16"
        />
      </svg>
      РљР°С‚Р°Р»РѕРі
    </button>
    
    <!-- РџРѕР»Рµ РїРѕРёСЃРєР° -->
    <div class="flex-1 relative">
      <input 
        ref="searchInput"
        v-model="searchQuery"
        type="text" 
        :placeholder="placeholder"
        :class="searchInputClasses"
        :aria-label="inputAriaLabel"
        @keyup.enter="handleSearch"
        @keyup.escape="clearSearch"
        @focus="handleFocus"
        @blur="handleBlur"
        @input="handleInput"
      >
      
      <!-- РљРЅРѕРїРєР° РѕС‡РёСЃС‚РєРё -->
      <button
        v-if="searchQuery"
        @click="clearSearch"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
        aria-label="РћС‡РёСЃС‚РёС‚СЊ РїРѕРёСЃРє"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      
      <!-- РџРѕРґСЃРєР°Р·РєРё РїРѕРёСЃРєР° -->
      <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div 
          v-if="showSuggestions && (suggestions.length > 0 || isLoading)"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-b-lg shadow-lg z-50 max-h-96 overflow-y-auto"
        >
          <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
          <div v-if="isLoading" class="p-4">
            <div class="animate-pulse space-y-2">
              <div class="h-4 bg-gray-200 rounded w-3/4"></div>
              <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
          </div>
          
          <!-- РџРѕРґСЃРєР°Р·РєРё -->
          <div v-else-if="suggestions.length > 0">
            <div class="p-2 text-xs font-medium text-gray-500 bg-gray-50 border-b">
              РџРѕРїСѓР»СЏСЂРЅС‹Рµ Р·Р°РїСЂРѕСЃС‹
            </div>
            <button
              v-for="(suggestion, index) in suggestions"
              :key="`suggestion-${index}`"
              @mousedown.prevent="selectSuggestion(suggestion)"
              class="block w-full text-left px-4 py-3 hover:bg-blue-50 text-sm transition-colors group"
              :class="{ 'bg-blue-50': highlightedIndex === index }"
            >
              <div class="flex items-center justify-between">
                <span>{{ suggestion.title }}</span>
                <span v-if="suggestion.category" class="text-xs text-gray-400 group-hover:text-blue-600">
                  {{ suggestion.category }}
                </span>
              </div>
            </button>
          </div>
          
          <!-- РќРµС‚ СЂРµР·СѓР»СЊС‚Р°С‚РѕРІ -->
          <div v-else class="p-4 text-center text-gray-500 text-sm">
            РќР°С‡РЅРёС‚Рµ РІРІРѕРґРёС‚СЊ РґР»СЏ РїРѕРёСЃРєР°
          </div>
        </div>
      </Transition>
    </div>
    
    <!-- РљРЅРѕРїРєР° РїРѕРёСЃРєР° -->
    <button 
      @click="handleSearch"
      :disabled="!searchQuery.trim() || isLoading"
      :class="searchButtonClasses"
      :aria-label="searchButtonAriaLabel"
    >
      <svg 
        v-if="!isLoading"
        class="w-5 h-5" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" 
        />
      </svg>
      <div v-else class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
      <span class="ml-2">РќР°Р№С‚Рё</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useSearchStore } from '../../model/search.store'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface SearchSuggestion {
  title: string
  category?: string
  type: 'service' | 'master' | 'location'
  url: string
}

interface Props {
  placeholder?: string
  catalogOpen?: boolean
  initialQuery?: string
  showSuggestions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'РќР°Р№С‚Рё РјР°СЃС‚РµСЂР° РёР»Рё СѓСЃР»СѓРіСѓ...',
  catalogOpen: false,
  initialQuery: '',
  showSuggestions: true
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'toggle-catalog': []
  'search-performed': [query: string]
  'suggestion-selected': [suggestion: SearchSuggestion]
}>()

// Store
const searchStore = useSearchStore()

// Refs
const searchInput = ref<HTMLInputElement>()
const searchQuery = ref(props.initialQuery)
const showSuggestionsState = ref(false)
const highlightedIndex = ref(-1)
const isLoading = ref(false)

// Computed
const suggestions = computed(() => searchStore.suggestions)

const showSuggestions = computed(() => 
  props.showSuggestions && showSuggestionsState.value && searchQuery.value.length > 0
)

const catalogButtonClasses = computed(() => [
  'bg-blue-600 text-white px-5 py-2.5 rounded-l-lg flex items-center font-medium whitespace-nowrap transition-colors',
  'hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  'disabled:opacity-50 disabled:cursor-not-allowed',
  {
    'bg-blue-700': props.catalogOpen
  }
])

const searchInputClasses = computed(() => [
  'w-full px-4 py-2.5 pr-10 border-y border-r-0 border-gray-300 text-gray-900 placeholder-gray-500',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
  'disabled:bg-gray-50 disabled:text-gray-500'
])

const searchButtonClasses = computed(() => [
  'bg-blue-600 text-white px-6 py-2.5 rounded-r-lg font-medium transition-colors flex items-center',
  'hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  'disabled:opacity-50 disabled:cursor-not-allowed',
  {
    'cursor-not-allowed opacity-50': !searchQuery.value.trim() || isLoading
  }
])

const inputAriaLabel = computed(() => 
  `РџРѕРёСЃРє РјР°СЃС‚РµСЂРѕРІ Рё СѓСЃР»СѓРі${searchQuery.value ? `, С‚РµРєСѓС‰РёР№ Р·Р°РїСЂРѕСЃ: ${searchQuery.value}` : ''}`
)

const searchButtonAriaLabel = computed(() => 
  isLoading.value ? 'Р’С‹РїРѕР»РЅСЏРµС‚СЃСЏ РїРѕРёСЃРє...' : `Р’С‹РїРѕР»РЅРёС‚СЊ РїРѕРёСЃРє${searchQuery.value ? ` РїРѕ Р·Р°РїСЂРѕСЃСѓ: ${searchQuery.value}` : ''}`
)

// Methods
const toggleCatalog = (): void => {
  emit('toggle-catalog')
}

const handleSearch = async (): Promise<void> => {
  if (!searchQuery.value.trim() || isLoading.value) return
  
  isLoading.value = true
  showSuggestionsState.value = false
  
  try {
    // РЎРѕС…СЂР°РЅРёС‚СЊ РІ store
    searchStore.addToHistory(searchQuery.value)
    
    // Emit СЃРѕР±С‹С‚РёРµ
    emit('search-performed', searchQuery.value)
    
    // РџРµСЂРµР№С‚Рё РЅР° СЃС‚СЂР°РЅРёС†Сѓ РїРѕРёСЃРєР°
    await router.get('/search', { q: searchQuery.value.trim() })
  } catch (error) {
    console.error('Search failed:', error)
  } finally {
    isLoading.value = false
  }
}

const selectSuggestion = (suggestion: SearchSuggestion): void => {
  searchQuery.value = suggestion.title
  showSuggestionsState.value = false
  emit('suggestion-selected', suggestion)
  
  // РџРµСЂРµР№С‚Рё РїРѕ URL РїРѕРґСЃРєР°Р·РєРё РёР»Рё РІС‹РїРѕР»РЅРёС‚СЊ РїРѕРёСЃРє
  if (suggestion.url) {
    router.visit(suggestion.url)
  } else {
    handleSearch()
  }
}

const clearSearch = (): void => {
  searchQuery.value = ''
  showSuggestionsState.value = false
  highlightedIndex.value = -1
  searchInput.value?.focus()
}

const handleFocus = (): void => {
  if (searchQuery.value.length > 0) {
    showSuggestionsState.value = true
    loadSuggestions()
  }
}

const handleBlur = (): void => {
  // Р—Р°РґРµСЂР¶РєР° РґР»СЏ РѕР±СЂР°Р±РѕС‚РєРё РєР»РёРєРѕРІ РїРѕ РїРѕРґСЃРєР°Р·РєР°Рј
  setTimeout(() => {
    showSuggestionsState.value = false
    highlightedIndex.value = -1
  }, 200)
}

const handleInput = (): void => {
  if (searchQuery.value.length > 0) {
    showSuggestionsState.value = true
    loadSuggestions()
  } else {
    showSuggestionsState.value = false
  }
}

const loadSuggestions = async (): Promise<void> => {
  if (!searchQuery.value.trim()) return
  
  try {
    await searchStore.loadSuggestions(searchQuery.value)
  } catch (error) {
    console.error('Failed to load suggestions:', error)
  }
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent): void => {
  if (!showSuggestions.value || suggestions.value.length === 0) return
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(highlightedIndex.value + 1, suggestions.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
      break
    case 'Enter':
      if (highlightedIndex.value >= 0) {
        event.preventDefault()
        const suggestion = suggestions.value[highlightedIndex.value]
        if (suggestion) {
          selectSuggestion(suggestion)
        }
      }
      break
    case 'Escape':
      showSuggestionsState.value = false
      highlightedIndex.value = -1
      break
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  
  // Р¤РѕРєСѓСЃ РЅР° РїРѕР»Рµ РїРѕРёСЃРєР° РїРѕ Ctrl+K РёР»Рё Cmd+K
  const handleGlobalKeydown = (event: KeyboardEvent) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
      event.preventDefault()
      searchInput.value?.focus()
    }
  }
  
  document.addEventListener('keydown', handleGlobalKeydown)
  
  // РћС‡РёСЃС‚РєР° РїСЂРё СЂР°Р·РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
    document.removeEventListener('keydown', handleGlobalKeydown)
  })
})

// Р­РєСЃРїРѕР·РёС†РёСЏ РґР»СЏ template refs
defineExpose({
  focus: () => searchInput.value?.focus(),
  clear: clearSearch,
  search: handleSearch
})
</script>

<style scoped>
/* РљР°СЃС‚РѕРјРЅР°СЏ Р°РЅРёРјР°С†РёСЏ РґР»СЏ СЃРїРёРЅРЅРµСЂР° */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Р РµСЃРїРѕРЅСЃРёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .catalog-text {
    display: none;
  }
  
  .search-text {
    display: none;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.800');
  }
  
  .border-gray-200 {
    border-color: theme('colors.gray.600');
  }
  
  .text-gray-900 {
    color: theme('colors.gray.100');
  }
}
</style>

