<template>
  <div class="filters-container space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
      <button @click="resetFilters" class="text-sm text-blue-600 hover:text-blue-700">
        Сбросить все
      </button>
    </div>

    <!-- Поиск -->
    <div class="filter-section">
      <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
      <input 
        v-model="searchQuery"
        @input="handleSearch"
        type="text" 
        placeholder="Найти мастера..."
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </div>

    <!-- Цена -->
    <div class="filter-section">
      <h3 class="text-sm font-medium text-gray-900 mb-3">Цена за час</h3>
      <div class="space-y-2">
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">До 2000 ₽</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">2000 - 3000 ₽</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">3000 - 4000 ₽</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">Свыше 4000 ₽</span>
        </label>
      </div>
    </div>

    <!-- Рейтинг -->
    <div class="filter-section">
      <h3 class="text-sm font-medium text-gray-900 mb-3">Рейтинг</h3>
      <div class="space-y-2">
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">⭐⭐⭐⭐⭐ 5.0</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">⭐⭐⭐⭐ 4.0+</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">⭐⭐⭐ 3.0+</span>
        </label>
      </div>
    </div>

    <!-- Специализация -->
    <div class="filter-section">
      <h3 class="text-sm font-medium text-gray-900 mb-3">Специализация</h3>
      <div class="space-y-2">
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">Классический массаж</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">Тайский массаж</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">Спортивный массаж</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">Антицеллюлитный</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">Релакс массаж</span>
        </label>
      </div>
    </div>

    <!-- Доступность -->
    <div class="filter-section">
      <h3 class="text-sm font-medium text-gray-900 mb-3">Доступность</h3>
      <div class="space-y-2">
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">Онлайн сейчас</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">С выездом</span>
        </label>
        <label class="flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 rounded">
          <span class="ml-2 text-sm text-gray-700">В салоне</span>
        </label>
      </div>
    </div>

    <!-- Город -->
    <div class="filter-section">
      <h3 class="text-sm font-medium text-gray-900 mb-3">Город</h3>
      <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <option value="">Выберите город</option>
        <option v-for="city in cities" :key="city" :value="city">
          {{ city }}
        </option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  },
  cities: {
    type: Array,
    default: () => []
  }
})

const searchQuery = ref(props.filters.search || '')

const resetFilters = () => {
  searchQuery.value = ''
  router.get('/', {}, {
    preserveState: true,
    preserveScroll: true
  })
}

const handleSearch = () => {
  router.get('/', { 
    search: searchQuery.value 
  }, {
    preserveState: true,
    preserveScroll: true
  })
}
</script>

<style scoped>
.filter-section {
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.filter-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.form-checkbox {
  border-radius: 0.25rem;
  border-color: #d1d5db;
}

.form-checkbox:checked {
  background-color: #3b82f6;
  border-color: #3b82f6;
}
</style>