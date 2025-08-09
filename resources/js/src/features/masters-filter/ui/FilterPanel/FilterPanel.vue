<!-- Панель фильтров как на скриншоте -->
<template>
  <div class="filter-panel bg-white rounded-lg shadow-sm">
    <!-- Заголовок -->
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-xl font-semibold">Фильтры</h2>
    </div>

    <div class="p-6 space-y-8">
      <!-- 1. Стоимость -->
      <div>
        <h3 class="text-base font-semibold mb-4">Цена, ₽</h3>
        <div class="flex items-center gap-2">
          <input
            v-model.number="filters.priceFrom"
            type="number"
            placeholder="58"
            min="0"
            class="flex-1 min-w-0 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
          >
          <span class="text-gray-400 flex-shrink-0">—</span>
          <input
            v-model.number="filters.priceTo"
            type="number"
            placeholder="167037"
            min="0"
            class="flex-1 min-w-0 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
          >
        </div>
      </div>

      <!-- 2. Вид массажа -->
      <div>
        <h3 class="text-base font-semibold mb-4">Вид массажа</h3>
        <div class="space-y-3">
          <label v-for="type in massageTypes" :key="type.value" class="flex items-center">
            <input
              v-model="filters.massageTypes"
              :value="type.value"
              type="checkbox"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            >
            <span class="ml-3 text-sm text-gray-700">{{ type.label }}</span>
          </label>
        </div>
      </div>

      <!-- 3. Дополнительно -->
      <div>
        <h3 class="text-base font-semibold mb-4">Дополнительно</h3>
        <div class="space-y-3">
          <label v-for="option in additionalOptions" :key="option.value" class="flex items-center">
            <input
              v-model="filters.additional"
              :value="option.value"
              type="checkbox"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            >
            <span class="ml-3 text-sm text-gray-700">{{ option.label }}</span>
          </label>
        </div>
      </div>

      <!-- 4. Рейтинг -->
      <div>
        <h3 class="text-base font-semibold mb-4">Рейтинг</h3>
        <div class="space-y-3">
          <label v-for="rating in ratingOptions" :key="rating.value" class="flex items-center">
            <input
              v-model="filters.rating"
              :value="rating.value"
              type="radio"
              name="rating"
              class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
            >
            <div class="ml-3 flex items-center gap-1">
              <!-- Звезды -->
              <span v-for="i in rating.stars" :key="i" class="text-yellow-400">★</span>
              <span v-for="i in (5 - rating.stars)" :key="`empty-${i}`" class="text-gray-300">★</span>
              <span class="ml-2 text-sm text-gray-700">{{ rating.label }}</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="pt-4 space-y-3">
        <button
          @click="applyFilters"
          class="w-full py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          Показать результаты
        </button>
        <button
          @click="resetFilters"
          class="w-full py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
        >
          Сбросить фильтры
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'

interface Filters {
  priceFrom: number | null
  priceTo: number | null
  massageTypes: string[]
  additional: string[]
  rating: number | null
}

const props = defineProps<{
  modelValue?: Filters
}>()

const emit = defineEmits<{
  'update:modelValue': [filters: Filters]
  'apply': [filters: Filters]
  'reset': []
}>()

// Состояние фильтров
const filters = reactive<Filters>({
  priceFrom: null,
  priceTo: null,
  massageTypes: [],
  additional: [],
  rating: null,
  ...props.modelValue
})

// Опции для фильтров
const massageTypes = [
  { value: 'classic', label: 'Классический массаж' },
  { value: 'sport', label: 'Спортивный массаж' },
  { value: 'thai', label: 'Тайский массаж' },
  { value: 'medical', label: 'Лечебный массаж' },
  { value: 'anti-cellulite', label: 'Антицеллюлитный' },
  { value: 'relaxing', label: 'Расслабляющий' },
  { value: 'facial', label: 'Массаж лица' },
  { value: 'lymphatic', label: 'Лимфодренажный' }
]

const additionalOptions = [
  { value: 'home', label: 'Выезд на дом' },
  { value: 'online', label: 'Онлайн-запись' },
  { value: 'certificate', label: 'Есть сертификаты' }
]

const ratingOptions = [
  { value: 4, stars: 4, label: 'и выше' },
  { value: 3, stars: 3, label: 'и выше' }
]

// Методы
const applyFilters = () => {
  emit('update:modelValue', { ...filters })
  emit('apply', { ...filters })
}

const resetFilters = () => {
  filters.priceFrom = null
  filters.priceTo = null
  filters.massageTypes = []
  filters.additional = []
  filters.rating = null
  emit('reset')
  applyFilters()
}
</script>

<style scoped>
.filter-panel {
  width: 320px;
  min-width: 320px;
  max-width: 320px;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
  overflow-x: hidden;
}

/* Предотвращаем выход контента за границы */
.filter-panel * {
  max-width: 100%;
  box-sizing: border-box;
}

/* Скроллбар стилизация */
.filter-panel::-webkit-scrollbar {
  width: 6px;
}

.filter-panel::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 3px;
}

.filter-panel::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.filter-panel::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>