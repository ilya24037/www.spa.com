<!--
  Панель фильтров мастеров
  Реализует drawer с фильтрами для мобильной и десктопной версии
  Использует мобильный подход по умолчанию (drawer снизу)
-->
<template>
  <Teleport to="body">
    <!-- Оверлей -->
    <Transition name="overlay">
      <div 
        v-if="modelValue"
        class="fixed inset-0 bg-black/50 z-50 lg:hidden"
        @click="handleClose"
      />
    </Transition>

    <!-- Панель фильтров -->
    <Transition name="drawer">
      <div 
        v-if="modelValue"
        class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-2xl shadow-xl lg:relative lg:inset-auto lg:rounded-xl lg:shadow-lg lg:max-w-sm"
        :class="[
          'lg:block',
          modelValue ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'
        ]"
      >
        <!-- Хедер панели -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 lg:px-6">
          <!-- Тайтл и счетчик -->
          <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-gray-900">
              Фильтры
            </h2>
            <span 
              v-if="activeFiltersCount > 0" 
              class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded-full"
            >
              {{ activeFiltersCount }}
            </span>
          </div>

          <!-- Кнопки управления -->
          <div class="flex items-center gap-2">
            <!-- Сброс фильтров -->
            <button
              v-if="hasActiveFilters"
              @click="handleReset"
              class="text-sm text-gray-500 hover:text-gray-700 transition-colors"
            >
              Сбросить
            </button>
            
            <!-- Закрыть (только мобильный) -->
            <button
              @click="handleClose"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors lg:hidden"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Контент панели -->
        <div class="overflow-y-auto max-h-[70vh] lg:max-h-none">
          <!-- Услуги -->
          <FilterCategory 
            title="Услуги" 
            icon="✂️"
            :active="filters.services.length > 0"
            :count="filters.services.length"
          >
            <div class="space-y-2">
              <div 
                v-for="service in options.services"
                :key="service.id"
                class="flex items-center gap-3"
              >
                <input
                  :id="`service-${service.id}`"
                  type="checkbox"
                  :checked="filters.services.includes(service.id)"
                  @change="toggleService(service.id)"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <label 
                  :for="`service-${service.id}`"
                  class="flex-1 text-sm text-gray-700 cursor-pointer"
                >
                  {{ service.name }}
                  <span class="text-gray-400 ml-1">({{ service.mastersCount }})</span>
                </label>
              </div>
            </div>
          </FilterCategory>

          <!-- Цена -->
          <FilterCategory 
            title="Цена" 
            icon="₽"
            :active="filters.priceRange.min > 0 || filters.priceRange.max < 10000"
          >
            <div class="space-y-4">
              <!-- Быстрые пресеты -->
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="preset in options.priceRanges"
                  :key="preset.label"
                  @click="setPriceRange(preset.min, preset.max || 10000)"
                  class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                  :class="[
                    filters.priceRange.min === preset.min && filters.priceRange.max === (preset.max || 10000)
                      ? 'border-blue-500 bg-blue-50 text-blue-700'
                      : 'text-gray-700'
                  ]"
                >
                  {{ preset.label }}
                </button>
              </div>
              
              <!-- Слайдер диапазона -->
              <div class="space-y-2">
                <div class="flex justify-between text-sm text-gray-600">
                  <span>{{ filters.priceRange.min }} ₽</span>
                  <span>{{ filters.priceRange.max }} ₽</span>
                </div>
                <div class="relative">
                  <input
                    type="range"
                    :min="0"
                    :max="10000"
                    :step="100"
                    :value="filters.priceRange.min"
                    @input="updatePriceMin($event.target.value)"
                    class="absolute w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                  />
                  <input
                    type="range"
                    :min="0"
                    :max="10000"
                    :step="100"
                    :value="filters.priceRange.max"
                    @input="updatePriceMax($event.target.value)"
                    class="absolute w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                  />
                </div>
              </div>
            </div>
          </FilterCategory>

          <!-- Рейтинг -->
          <FilterCategory 
            title="Рейтинг" 
            icon="⭐"
            :active="filters.rating.min > 0 || filters.rating.onlyWithReviews"
          >
            <div class="space-y-3">
              <!-- Минимальный рейтинг -->
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">От</span>
                <div class="flex gap-1">
                  <button
                    v-for="rating in [1, 2, 3, 4, 5]"
                    :key="rating"
                    @click="setRatingFilter(rating, filters.rating.onlyWithReviews)"
                    class="text-lg"
                    :class="[
                      rating <= filters.rating.min ? 'text-yellow-400' : 'text-gray-300'
                    ]"
                  >
                    ⭐
                  </button>
                </div>
                <button
                  @click="setRatingFilter(0, filters.rating.onlyWithReviews)"
                  class="text-sm text-gray-500 hover:text-gray-700"
                >
                  Сбросить
                </button>
              </div>
              
              <!-- Только с отзывами -->
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="filters.rating.onlyWithReviews"
                  @change="setRatingFilter(filters.rating.min, $event.target.checked)"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Только с отзывами</span>
              </label>
            </div>
          </FilterCategory>

          <!-- Где принимает -->
          <FilterCategory 
            title="Где принимает" 
            icon="🏠"
            :active="filters.serviceLocation.length > 0"
            :count="filters.serviceLocation.length"
          >
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="filters.serviceLocation.includes('home')"
                  @change="toggleServiceLocation('home')"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Выезд на дом</span>
              </label>
              
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="filters.serviceLocation.includes('salon')"
                  @change="toggleServiceLocation('salon')"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">В салоне</span>
              </label>
            </div>
          </FilterCategory>

          <!-- Доступность -->
          <FilterCategory 
            title="Доступность" 
            icon="📅"
            :active="filters.availability.availableToday || filters.availability.availableTomorrow || filters.availability.availableThisWeek"
          >
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="filters.availability.availableToday"
                  @change="updateAvailability('availableToday', $event.target.checked)"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Доступен сегодня</span>
              </label>
              
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="filters.availability.availableTomorrow"
                  @change="updateAvailability('availableTomorrow', $event.target.checked)"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Доступен завтра</span>
              </label>
              
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="filters.availability.availableThisWeek"
                  @change="updateAvailability('availableThisWeek', $event.target.checked)"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Доступен на неделе</span>
              </label>
            </div>
          </FilterCategory>

          <!-- Сортировка -->
          <FilterCategory 
            title="Сортировка" 
            icon="↕️"
            :active="filters.sorting !== 'relevance'"
          >
            <div class="space-y-2">
              <label
                v-for="option in SORTING_OPTIONS"
                :key="option.value"
                class="flex items-center gap-3 cursor-pointer"
              >
                <input
                  type="radio"
                  :value="option.value"
                  :checked="filters.sorting === option.value"
                  @change="setSorting(option.value)"
                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">{{ option.label }}</span>
              </label>
            </div>
          </FilterCategory>
        </div>

        <!-- Футер с кнопкой применения -->
        <div class="p-4 border-t border-gray-200 bg-white lg:px-6">
          <div class="flex gap-3">
            <!-- История фильтров -->
            <button
              v-if="filterHistory.length > 0"
              @click="goBack"
              class="flex-shrink-0 px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              ←
            </button>

            <!-- Применить фильтры -->
            <button
              @click="handleApply"
              :disabled="loading"
              class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition-colors font-medium"
            >
              <span v-if="loading">Применение...</span>
              <span v-else>{{ applyButtonText }}</span>
              <span 
                v-if="masterCount !== undefined && !loading"
                class="ml-2 px-2 py-1 text-xs bg-white text-blue-600 rounded-full"
              >
                {{ masterCount }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  useFilterStore, 
  SORTING_OPTIONS,
  type FilterPanelProps,
  type FilterEvents
} from '../../model'

// Компоненты фильтров
import FilterCategory from '../FilterCategory/FilterCategory.vue'

// =================== PROPS & EMITS ===================

interface Props {
  modelValue: boolean
  loading?: boolean
  masterCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'apply', filters: any): void
  (e: 'reset'): void
  (e: 'close'): void
}

const emit = defineEmits<Emits>()

// =================== STORE ===================

const filterStore = useFilterStore()
const {
  filters,
  isLoading,
  error,
  options,
  filterHistory,
  activeFiltersCount,
  hasActiveFilters,
  applyButtonText,
  setServiceFilter,
  setPriceRange,
  setLocation,
  setRatingFilter,
  setWorkingHoursFilter,
  setServiceLocationFilter,
  setAvailabilityFilter,
  setSorting,
  resetFilters,
  goBack,
  loadFilterOptions
} = filterStore

// =================== ОБРАБОТЧИКИ ===================

const handleClose = () => {
  emit('close')
}

const handleReset = () => {
  resetFilters()
  emit('reset')
}

const handleApply = () => {
  emit('apply', filters.value)
  handleClose()
}

const toggleService = (serviceId: number) => {
  const services = [...filters.value.services]
  const index = services.indexOf(serviceId)
  
  if (index > -1) {
    services.splice(index, 1)
  } else {
    services.push(serviceId)
  }
  
  setServiceFilter(services)
}

const updatePriceMin = (value: string) => {
  const min = parseInt(value)
  setPriceRange(min, Math.max(min, filters.value.priceRange.max))
}

const updatePriceMax = (value: string) => {
  const max = parseInt(value)
  setPriceRange(Math.min(filters.value.priceRange.min, max), max)
}

const toggleServiceLocation = (location: 'home' | 'salon') => {
  const locations = [...filters.value.serviceLocation]
  const index = locations.indexOf(location)
  
  if (index > -1) {
    locations.splice(index, 1)
  } else {
    locations.push(location)
  }
  
  setServiceLocationFilter(locations)
}

const updateAvailability = (field: keyof typeof filters.value.availability, value: boolean) => {
  const availability = { ...filters.value.availability, [field]: value }
  setAvailabilityFilter(availability.availableToday, availability.availableTomorrow, availability.availableThisWeek)
}

// =================== LIFECYCLE ===================

onMounted(() => {
  // Загружаем опции фильтров при монтировании
  loadFilterOptions()
  
  // Загружаем сохраненные фильтры
  filterStore.loadFiltersFromStorage()
})

// Закрытие по Escape
const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.modelValue) {
    handleClose()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
/* Анимации для drawer */
.drawer-enter-active,
.drawer-leave-active {
  transition: transform 0.3s ease-out;
}

.drawer-enter-from,
.drawer-leave-to {
  transform: translateY(100%);
}

@screen lg {
  .drawer-enter-from,
  .drawer-leave-to {
    transform: translateY(0);
  }
}

/* Анимации для overlay */
.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.3s ease-out;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

/* Кастомные стили для range input */
input[type="range"] {
  -webkit-appearance: none;
  background: transparent;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 20px;
  width: 20px;
  background: #3b82f6;
  border-radius: 50%;
  cursor: pointer;
  margin-top: -8px;
}

input[type="range"]::-webkit-slider-track {
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
}
</style>