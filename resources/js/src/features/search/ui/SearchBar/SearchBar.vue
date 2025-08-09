<!-- FSD SearchBar (точная копия из Backap/js/Components/Header/SearchBar.vue) -->
<template>
  <!-- Синий контейнер с белым полем внутри -->
  <div class="flex items-center h-12 rounded-2xl bg-blue-600 pl-2 pr-2 shadow-sm w-full max-w-2xl">
    <!-- Dropdown "Каталог" слева (минимальные отступы) -->
    <button 
      @click="$emit('toggle-catalog')"
      class="px-1 py-2 rounded-md bg-blue-600 text-white flex items-center text-sm font-medium mr-px hover:bg-blue-700 transition whitespace-nowrap border-r border-blue-500"
    >
      Каталог
      <svg class="ml-0.5 w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
        <path d="m8.784 10.339 2.207-2.412c.668-.731.345-1.097-.858-1.097H5.859c-1.204 0-1.49.439-.887 1.097l2.207 2.412c.802.877.802.877 1.605 0"/>
      </svg>
    </button>
    
    <!-- Контейнер для белого поля (без рамки) -->
    <div class="flex-1 relative">
      <input 
        v-model="searchQuery"
        type="text" 
        placeholder="Искать на MASSAGIST"
        class="w-full text-base font-medium text-gray-900 bg-white rounded-lg outline-none px-3 py-2.5 placeholder-gray-400"
        @keyup.enter="search"
        @focus="showSuggestions = true"
        @blur="hideSuggestions"
      >
    </div>
    
    <!-- Кнопка "Поиск" (минимальные отступы) -->
    <button 
      @click="search"
      class="px-1 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition text-sm font-medium border-l border-blue-500 ml-px"
    >
      Поиск
    </button>
    
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
        class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
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



