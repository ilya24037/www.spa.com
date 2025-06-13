<template>
  <div class="flex-1 max-w-2xl mx-4">
    <form @submit.prevent="handleSearch" class="relative">
      <div class="flex">
        <!-- Выбор категории поиска -->
        <button 
          type="button"
          @click="showCategoryDropdown = !showCategoryDropdown"
          class="flex items-center gap-1 px-4 py-2.5 bg-gray-50 border border-r-0 border-gray-300 rounded-l-lg hover:bg-gray-100 transition-colors"
        >
          <span class="text-sm text-gray-700">{{ searchCategory }}</span>
          <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Поле ввода -->
        <div class="relative flex-1">
          <input 
            v-model="searchQuery"
            @focus="showSuggestions = true"
            @blur="hideSuggestions"
            type="text"
            placeholder="Найти массажиста или услугу"
            class="w-full px-4 py-2.5 border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            autocomplete="off"
          >
          
          <!-- Кнопка очистки -->
          <button 
            v-if="searchQuery"
            type="button"
            @click="searchQuery = ''"
            class="absolute right-12 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Кнопка поиска -->
        <button 
          type="submit"
          class="px-6 py-2.5 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" 
            />
          </svg>
        </button>
      </div>

      <!-- Выпадающий список категорий -->
      <div 
        v-if="showCategoryDropdown"
        class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
      >
        <button 
          v-for="cat in searchCategories"
          :key="cat"
          @click="selectCategory(cat)"
          class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
        >
          {{ cat }}
        </button>
      </div>

      <!-- Подсказки поиска -->
      <div 
        v-if="showSuggestions && (searchTags.length || recentSearches.length)"
        class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
      >
        <!-- Популярные теги -->
        <div v-if="searchTags.length" class="p-4 border-b border-gray-100">
          <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Популярные запросы</h4>
          <div class="flex flex-wrap gap-2">
            <button 
              v-for="tag in searchTags"
              :key="tag"
              @mousedown="selectTag(tag)"
              class="px-3 py-1 bg-gray-100 text-sm rounded-full hover:bg-gray-200 transition-colors"
            >
              {{ tag }}
            </button>
          </div>
        </div>

        <!-- История поиска -->
        <div v-if="recentSearches.length" class="p-4">
          <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Недавние поиски</h4>
          <div class="space-y-1">
            <button 
              v-for="search in recentSearches"
              :key="search"
              @mousedown="searchQuery = search"
              class="flex items-center gap-2 w-full text-left py-2 text-sm hover:text-blue-600 transition-colors"
            >
              <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" 
                />
              </svg>
              {{ search }}
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const searchQuery = ref('')
const searchCategory = ref('Везде')
const showCategoryDropdown = ref(false)
const showSuggestions = ref(false)

const searchCategories = ['Везде', 'Массажисты', 'Услуги', 'Салоны']

const searchTags = ref([
  'массаж спины',
  'классический массаж',
  'с выездом',
  'тайский массаж',
  'антицеллюлитный'
])

const recentSearches = ref([
  'массажист на дом',
  'спортивный массаж',
  'релакс массаж'
])

const selectCategory = (cat) => {
  searchCategory.value = cat
  showCategoryDropdown.value = false
}

const selectTag = (tag) => {
  searchQuery.value = tag
  handleSearch()
}

const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    router.get('/', { 
      search: searchQuery.value,
      category: searchCategory.value !== 'Везде' ? searchCategory.value : null
    })
  }
}
</script>