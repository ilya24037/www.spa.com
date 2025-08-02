<!-- resources/js/src/features/masters-filter/ui/FilterPanel/FilterPanel.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Заголовок -->
    <div :class="HEADER_CLASSES">
      <h3 :class="TITLE_CLASSES">Фильтры</h3>
      <button
        v-if="hasActiveFilters"
        @click="clearAllFilters"
        :class="CLEAR_BUTTON_CLASSES"
      >
        Сбросить
      </button>
    </div>

    <!-- Компоненты фильтров -->
    <div :class="FILTERS_CONTAINER_CLASSES">
      <!-- Поиск -->
      <FilterSearch
        :value="filters.search"
        @update="updateFilter('search', $event)"
      />

      <!-- Цена -->
      <FilterPrice
        :from="filters.price_from"
        :to="filters.price_to"
        @update:from="updateFilter('price_from', $event)"
        @update:to="updateFilter('price_to', $event)"
      />

      <!-- Локация -->
      <FilterLocation
        :city="filters.city"
        :district="filters.district"
        :metro="filters.metro"
        @update:city="updateFilter('city', $event)"
        @update:district="updateFilter('district', $event)"
        @update:metro="updateFilter('metro', $event)"
      />

      <!-- Категории -->
      <FilterCategory
        :selected="filters.categories"
        :categories="availableCategories"
        @update="updateFilter('categories', $event)"
      />

      <!-- Рейтинг -->
      <FilterRating
        :value="filters.rating"
        @update="updateFilter('rating', $event)"
      />

      <!-- Дополнительные параметры -->
      <FilterAdditional
        :verified="filters.verified"
        :premium="filters.premium"
        :online="filters.online"
        :home-service="filters.home_service"
        :online-booking="filters.online_booking"
        @update="updateAdditionalFilter"
      />
    </div>

    <!-- Кнопка применить (мобильная версия) -->
    <div :class="MOBILE_ACTIONS_CLASSES">
      <button
        @click="applyFilters"
        :class="APPLY_BUTTON_CLASSES"
        :disabled="!hasChanges"
      >
        Применить ({{ filteredCount }})
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useMastersFilterStore } from '../model/mastersFilterStore'
import FilterSearch from './FilterSearch.vue'
import FilterPrice from './FilterPrice.vue'
import FilterLocation from './FilterLocation.vue'
import FilterCategory from './FilterCategory.vue'
import FilterRating from './FilterRating.vue'
import FilterAdditional from './FilterAdditional.vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-6 p-4'
const HEADER_CLASSES = 'flex items-center justify-between'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const CLEAR_BUTTON_CLASSES = 'text-sm text-red-600 hover:text-red-700 font-medium'
const FILTERS_CONTAINER_CLASSES = 'space-y-6'
const MOBILE_ACTIONS_CLASSES = 'md:hidden pt-4 border-t border-gray-200'
const APPLY_BUTTON_CLASSES = 'w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white font-medium rounded-lg transition-colors'

const props = defineProps({
  availableCategories: {
    type: Array,
    default: () => []
  },
  filteredCount: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update', 'apply'])

// Store
const filterStore = useMastersFilterStore()

// Вычисляемые свойства
const filters = computed(() => filterStore.filters)

const hasActiveFilters = computed(() => filterStore.hasActiveFilters)

const hasChanges = computed(() => filterStore.hasChanges)

// Методы
const updateFilter = (key, value) => {
  filterStore.updateFilter(key, value)
  
  // Автоматически применяем фильтры на десктопе
  if (window.innerWidth >= 768) {
    emit('update', filterStore.filters)
  }
}

const updateAdditionalFilter = (updates) => {
  Object.entries(updates).forEach(([key, value]) => {
    filterStore.updateFilter(key, value)
  })
  
  // Автоматически применяем фильтры на десктопе
  if (window.innerWidth >= 768) {
    emit('update', filterStore.filters)
  }
}

const clearAllFilters = () => {
  filterStore.resetFilters()
  emit('update', filterStore.filters)
}

const applyFilters = () => {
  emit('apply', filterStore.filters)
}
</script>