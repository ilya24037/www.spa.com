<!-- resources/js/Components/Header/SearchBar.vue -->
<template>
  <div class="flex items-center flex-1 max-w-3xl">
    <!-- Кнопка каталога -->
    <button 
      @click="$emit('toggle-catalog')"
      class="bg-blue-600 text-white px-5 py-2.5 rounded-l-lg flex items-center hover:bg-blue-700 transition font-medium whitespace-nowrap"
    >
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      Каталог
    </button>
    
    <!-- Поле поиска -->
    <div class="flex-1 relative">
      <input 
        v-model="searchQuery"
        type="text" 
        placeholder="Найти мастера или услугу"
        class="w-full px-4 py-2.5 border-y border-r-0 border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @keyup.enter="search"
        @focus="showSuggestions = true"
        @blur="hideSuggestions"
      >
      
      <!-- Подсказки поиска -->
      <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div 
          v-if="showSuggestions && suggestions.length > 0"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-b-lg shadow-lg z-50"
        >
          <button
            v-for="suggestion in suggestions"
            :key="suggestion"
            @mousedown.prevent="selectSuggestion(suggestion)"
            class="block w-full text-left px-4 py-2 hover:bg-gray-100 text-sm"
          >
            {{ suggestion }}
          </button>
        </div>
      </Transition>
    </div>
    
    <!-- Кнопка поиска -->
    <button 
      @click="search"
      class="bg-blue-600 text-white px-6 py-2.5 rounded-r-lg hover:bg-blue-700 transition font-medium"
    >
      Найти
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const emit = defineEmits(['toggle-catalog'])

const searchQuery = ref('')
const showSuggestions = ref(false)

const suggestions = computed(() => {
  if (!searchQuery.value) return []
  
  const popularSearches = [
    'Классический массаж',
    'Тайский массаж',
    'Массаж спины',
    'СПА процедуры',
    'Антицеллюлитный массаж',
    'Релакс массаж'
  ]
  
  return popularSearches.filter(item => 
    item.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const search = () => {
  if (searchQuery.value.trim()) {
    router.get('/search', { q: searchQuery.value })
  }
}

const selectSuggestion = (suggestion) => {
  searchQuery.value = suggestion
  showSuggestions.value = false
  search()
}

const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}
</script>