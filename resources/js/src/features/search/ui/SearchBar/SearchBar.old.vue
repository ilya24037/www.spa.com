<!-- SearchBar с Tailwind CSS -->
<template>
  <div class="relative w-full max-w-3xl">
    <!-- Основной контейнер -->
    <div class="flex items-center h-10 bg-gray-50 border-2 border-gray-100 rounded-full w-full overflow-hidden transition-all duration-200 focus-within:border-blue-500 focus-within:bg-white">
    
    <!-- Кнопка Каталог -->
    <button 
      @click="$emit('toggle-catalog')"
      class="flex items-center gap-1 h-full px-3 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors whitespace-nowrap border-r border-blue-700"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      <span class="hidden sm:inline">Каталог</span>
      <svg class="w-3 h-3 opacity-80" fill="currentColor" viewBox="0 0 24 24">
        <path d="M7 10l5 5 5-5H7z"/>
      </svg>
    </button>
    
    <!-- Разделитель -->
    <div class="w-px h-5 bg-gray-300 mx-0.5"></div>
    
    <!-- Поле поиска -->
    <div class="flex-1 relative flex items-center">
      <input 
        v-model="searchQuery"
        type="text" 
        placeholder="Искать на MASSAGIST"
        class="w-full h-full px-3 bg-transparent border-none outline-none text-sm text-gray-900 placeholder-gray-500"
        @keyup.enter="search"
        @focus="showSuggestions = true"
        @blur="hideSuggestions"
      >
      
      <!-- Кнопка очистки -->
      <button 
        v-if="searchQuery"
        @click="clearSearch"
        class="absolute right-2 p-1 text-gray-400 hover:text-gray-600 transition-colors"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 8.586L4.707 3.293a1 1 0 00-1.414 1.414L8.586 10l-5.293 5.293a1 1 0 001.414 1.414L10 11.414l5.293 5.293a1 1 0 001.414-1.414L11.414 10l5.293-5.293a1 1 0 00-1.414-1.414L10 8.586z"/>
        </svg>
      </button>
    </div>
    
    <!-- Кнопка поиска -->
    <button 
      @click="search"
      class="h-full px-3 bg-blue-600 text-white hover:bg-blue-700 transition-colors"
    >
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.892 15.064a8 8 0 1 0-2.828 2.828l2.522 2.522a2 2 0 1 0 2.828-2.828zM11 16a5 5 0 1 1 0-10 5 5 0 0 1 0 10"/>
      </svg>
    </button>
    
  </div>
  
  <!-- Dropdown с подсказками -->
  <Transition
    enter-active-class="transition ease-out duration-100"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition ease-in duration-75"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div 
      v-if="showSuggestions && (suggestions.length > 0 || searchHistory.length > 0)"
      class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 max-h-96 overflow-y-auto"
    >
      <!-- История поиска -->
      <div v-if="searchHistory.length > 0 && !searchQuery" class="py-3">
        <div class="px-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">История поиска</div>
        <button
          v-for="item in searchHistory"
          :key="'history-' + item"
          @mousedown.prevent="selectSuggestion(item)"
          class="flex items-center gap-3 w-full px-4 py-2 hover:bg-gray-50 transition-colors text-left"
        >
          <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2M7 5.5v3l2.5 1.5.5-.8-2-1.2V5.5z"/>
          </svg>
          <span class="text-sm text-gray-700">{{ item }}</span>
        </button>
      </div>
      
      <!-- Подсказки -->
      <div v-if="suggestions.length > 0" class="py-3 border-t border-gray-100">
        <div v-if="searchQuery" class="px-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Возможно, вы ищете</div>
        <button
          v-for="suggestion in suggestions"
          :key="suggestion"
          @mousedown.prevent="selectSuggestion(suggestion)"
          class="flex items-center gap-3 w-full px-4 py-2 hover:bg-gray-50 transition-colors text-left"
        >
          <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.089.083.176.17l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.193-.179zm-5.242.658a5 5 0 1 1 0-10 5 5 0 0 1 0 10"/>
          </svg>
          <span class="text-sm text-gray-700">{{ suggestion }}</span>
        </button>
      </div>
      
      <!-- Популярные запросы -->
      <div v-if="!searchQuery" class="py-3 border-t border-gray-100">
        <div class="px-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Популярные запросы</div>
        <div class="flex flex-wrap gap-2 px-4">
          <button
            v-for="tag in popularTags"
            :key="tag"
            @click="selectSuggestion(tag)"
            class="px-3 py-1.5 bg-gray-100 hover:bg-blue-600 hover:text-white text-sm text-gray-700 rounded-full transition-colors"
          >
            {{ tag }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const emit = defineEmits(['toggle-catalog'])

const searchQuery = ref('')
const showSuggestions = ref(false)
const searchHistory = ref([])

// Популярные теги как в Ozon
const popularTags = ref([
  'массаж спины',
  'тайский массаж',
  'спа процедуры',
  'антицеллюлитный',
  'релакс массаж',
  'лимфодренаж'
])

// Загрузка истории из localStorage
onMounted(() => {
  const history = localStorage.getItem('searchHistory')
  if (history) {
    searchHistory.value = JSON.parse(history)
  }
})

const suggestions = computed(() => {
  if (!searchQuery.value) return []
  
  const popularSearches = [
    'Классический массаж',
    'Тайский массаж',
    'Массаж спины',
    'СПА процедуры',
    'Антицеллюлитный массаж',
    'Релакс массаж',
    'Лимфодренажный массаж',
    'Спортивный массаж',
    'Медовый массаж',
    'Аромамассаж'
  ]
  
  return popularSearches.filter(item => 
    item.toLowerCase().includes(searchQuery.value.toLowerCase())
  ).slice(0, 6)
})

const search = () => {
  if (searchQuery.value.trim()) {
    // Добавляем в историю
    addToHistory(searchQuery.value)
    router.get('/search', { q: searchQuery.value })
  }
}

const addToHistory = (query) => {
  const history = searchHistory.value.filter(item => item !== query)
  history.unshift(query)
  searchHistory.value = history.slice(0, 5)
  localStorage.setItem('searchHistory', JSON.stringify(searchHistory.value))
}

const selectSuggestion = (suggestion) => {
  searchQuery.value = suggestion
  showSuggestions.value = false
  search()
}

const clearSearch = () => {
  searchQuery.value = ''
  showSuggestions.value = false
}

const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}
</script>