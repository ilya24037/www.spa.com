<template>
  <!-- Overlay -->
  <Transition name="fade">
    <div 
      v-if="show"
      @click="$emit('close')"
      class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
    />
  </Transition>

  <!-- Панель фильтров -->
  <Transition name="slide">
    <div 
      v-if="show"
      class="fixed inset-y-0 left-0 w-full max-w-sm bg-white shadow-xl z-50 lg:hidden overflow-y-auto"
    >
      <!-- Шапка -->
      <div class="sticky top-0 bg-white border-b px-4 py-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Фильтры</h2>
          <button 
            @click="$emit('close')"
            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Контент фильтров -->
      <div class="p-4 space-y-6">
        <!-- Категории -->
        <div class="filter-section">
          <h3 class="font-medium text-gray-900 mb-3">Категория услуг</h3>
          <div class="space-y-2">
            <label 
              v-for="category in categories" 
              :key="category.id"
              class="flex items-center"
            >
              <input 
                type="checkbox"
                :value="category.id"
                :checked="localFilters.categories?.includes(category.id)"
                @change="toggleCategory(category.id)"
                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
              >
              <span class="ml-2 text-sm text-gray-700">{{ category.name }}</span>
            </label>
          </div>
        </div>

        <!-- Цена -->
        <div class="filter-section">
          <h3 class="font-medium text-gray-900 mb-3">Цена за час</h3>
          <div class="space-y-3">
            <div>
              <label class="text-sm text-gray-600">От</label>
              <input 
                v-model.number="localFilters.price_min"
                type="number"
                placeholder="0"
                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
            </div>
            <div>
              <label class="text-sm text-gray-600">До</label>
              <input 
                v-model.number="localFilters.price_max"
                type="number"
                :placeholder="priceRange.max"
                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
            </div>
          </div>
        </div>

        <!-- Рейтинг -->
        <div class="filter-section">
          <h3 class="font-medium text-gray-900 mb-3">Рейтинг</h3>
          <div class="space-y-2">
            <label class="flex items-center">
              <input 
                type="radio" 
                name="rating" 
                value="4"
                v-model="localFilters.rating"
                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
              >
              <span class="ml-2 text-sm text-gray-700 flex items-center">
                4+ 
                <svg class="w-4 h-4 text-yellow-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </span>
            </label>
            <label class="flex items-center">
              <input 
                type="radio" 
                name="rating" 
                value="3"
                v-model="localFilters.rating"
                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
              >
              <span class="ml-2 text-sm text-gray-700 flex items-center">
                3+ 
                <svg class="w-4 h-4 text-yellow-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </span>
            </label>
            <label class="flex items-center">
              <input 
                type="radio" 
                name="rating" 
                value=""
                v-model="localFilters.rating"
                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
              >
              <span class="ml-2 text-sm text-gray-700">Любой</span>
            </label>
          </div>
        </div>

        <!-- Дополнительные опции -->
        <div class="filter-section">
          <h3 class="font-medium text-gray-900 mb-3">Дополнительно</h3>
          <div class="space-y-2">
            <label class="flex items-center">
              <input 
                type="checkbox"
                v-model="localFilters.with_photo"
                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
              >
              <span class="ml-2 text-sm text-gray-700">Только с фото</span>
            </label>
            <label class="flex items-center">
              <input 
                type="checkbox"
                v-model="localFilters.verified"
                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
              >
              <span class="ml-2 text-sm text-gray-700">Проверенные мастера</span>
            </label>
            <label class="flex items-center">
              <input 
                type="checkbox"
                v-model="localFilters.online"
                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
              >
              <span class="ml-2 text-sm text-gray-700">Сейчас онлайн</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="sticky bottom-0 bg-white border-t p-4 space-y-2">
        <button 
          @click="applyFilters"
          class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
        >
          Показать результаты
        </button>
        <button 
          @click="resetFilters"
          class="w-full py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
        >
          Сбросить фильтры
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: Boolean,
  filters: {
    type: Object,
    default: () => ({})
  },
  categories: {
    type: Array,
    default: () => []
  },
  priceRange: {
    type: Object,
    default: () => ({ min: 0, max: 10000 })
  }
})

const emit = defineEmits(['close', 'update'])

// Локальная копия фильтров
const localFilters = ref({
  categories: [],
  price_min: null,
  price_max: null,
  rating: '',
  with_photo: false,
  verified: false,
  online: false,
  ...props.filters
})

// Обновляем локальные фильтры при изменении пропсов
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...localFilters.value, ...newFilters }
}, { deep: true })

// Методы
const toggleCategory = (categoryId) => {
  const categories = localFilters.value.categories || []
  const index = categories.indexOf(categoryId)
  
  if (index > -1) {
    categories.splice(index, 1)
  } else {
    categories.push(categoryId)
  }
  
  localFilters.value.categories = categories
}

const applyFilters = () => {
  emit('update', localFilters.value)
  emit('close')
}

const resetFilters = () => {
  localFilters.value = {
    categories: [],
    price_min: null,
    price_max: null,
    rating: '',
    with_photo: false,
    verified: false,
    online: false
  }
  emit('update', localFilters.value)
}
</script>

<style scoped>
/* Анимация появления фона */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Анимация выдвижения панели */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}

/* Разделитель секций */
.filter-section {
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.filter-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
</style>