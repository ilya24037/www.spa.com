<!-- resources/js/src/widgets/masters-catalog/MastersCatalog.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Мобильная кнопка фильтров -->
    <button
      @click="toggleMobileFilters"
      :class="MOBILE_FILTER_BUTTON_CLASSES"
    >
      <svg :class="FILTER_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"/>
      </svg>
      Фильтры
      <span v-if="activeFiltersCount > 0" :class="FILTER_COUNT_CLASSES">
        {{ activeFiltersCount }}
      </span>
    </button>

    <div :class="LAYOUT_CLASSES">
      <!-- Боковая панель с фильтрами -->
      <div :class="getSidebarClasses()">
        <SidebarWrapper v-model="showFilters">
          <template #header>
            <h2 :class="SIDEBAR_TITLE_CLASSES">Фильтры</h2>
            <button
              @click="closeMobileFilters"
              :class="CLOSE_MOBILE_BUTTON_CLASSES"
            >
              <svg :class="CLOSE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </template>
          
          <FilterPanel
            :available-categories="availableCategories"
            :filtered-count="filteredMasters.length"
            @update="handleFilterUpdate"
            @apply="handleFilterApply"
          />
        </SidebarWrapper>
      </div>

      <!-- Основной контент -->
      <div :class="MAIN_CONTENT_CLASSES">
        <ContentCard>
        <!-- Контролы сортировки и отображения -->
        <div :class="CONTROLS_CLASSES">
          <div :class="RESULTS_INFO_CLASSES">
            <span :class="RESULTS_COUNT_CLASSES">
              {{ filteredMasters.length }} {{ getMastersWord() }}
            </span>
            <span v-if="currentCity" :class="CITY_INFO_CLASSES">
              в {{ currentCity }}
            </span>
          </div>

          <div :class="VIEW_CONTROLS_CLASSES">
            <!-- Переключатель вида -->
            <div :class="VIEW_TOGGLE_CLASSES">
              <button
                @click="setViewMode('grid')"
                :class="getViewButtonClasses('grid')"
                title="Сетка"
              >
                <svg :class="VIEW_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
              </button>
              
              <button
                @click="setViewMode('list')"
                :class="getViewButtonClasses('list')"
                title="Список"
              >
                <svg :class="VIEW_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
              </button>

              <button
                @click="setViewMode('map')"
                :class="getViewButtonClasses('map')"
                title="Карта"
              >
                <svg :class="VIEW_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
              </button>
            </div>

            <!-- Сортировка -->
            <select
              v-model="sortBy"
              @change="handleSortChange"
              :class="SORT_SELECT_CLASSES"
            >
              <option value="rating">По рейтингу</option>
              <option value="reviews_count">По отзывам</option>
              <option value="price_from">По цене</option>
              <option value="created_at">Новые</option>
              <option value="views_count">Популярные</option>
            </select>
          </div>
        </div>

        <!-- Контент в зависимости от режима просмотра -->
        <div :class="CONTENT_WRAPPER_CLASSES">
          <!-- Режим сетки -->
          <div v-if="viewMode === 'grid'" :class="GRID_CONTAINER_CLASSES">
            <MasterCard
              v-for="master in paginatedMasters"
              :key="master.id"
              :master="master"
              @click="goToMaster(master)"
              @favorite="handleToggleFavorite(master)"
            />
          </div>

          <!-- Режим списка -->
          <div v-else-if="viewMode === 'list'" :class="LIST_CONTAINER_CLASSES">
            <MasterCardListItem
              v-for="master in paginatedMasters"
              :key="master.id"
              :master="master"
              @click="goToMaster(master)"
              @favorite="handleToggleFavorite(master)"
            />
          </div>

          <!-- Режим карты -->
          <div v-else-if="viewMode === 'map'" :class="MAP_CONTAINER_CLASSES">
            <UniversalMap
              :markers="mapMarkers"
              :height="600"
              mode="full"
              :title="`Мастера в ${currentCity || 'городе'}`"
              :subtitle="`${filteredMasters.length} мастеров`"
              show-stats
              @marker-click="handleMarkerClick"
            />
          </div>
        </div>

        <!-- Пагинация -->
        <div v-if="viewMode !== 'map' && hasMorePages" :class="PAGINATION_CLASSES">
          <button
            @click="loadMoreMasters"
            :disabled="loadingMore"
            :class="LOAD_MORE_BUTTON_CLASSES"
          >
            {{ loadingMore ? 'Загрузка...' : 'Показать еще' }}
          </button>
        </div>

        <!-- Состояние загрузки -->
        <div v-if="loading && !filteredMasters.length" :class="LOADING_CLASSES">
          <svg :class="LOADING_ICON_CLASSES" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
          <span>Загрузка мастеров...</span>
        </div>

        <!-- Пустое состояние -->
        <div v-else-if="!loading && !filteredMasters.length" :class="EMPTY_STATE_CLASSES">
          <svg :class="EMPTY_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <h3 :class="EMPTY_TITLE_CLASSES">Мастера не найдены</h3>
          <p :class="EMPTY_DESCRIPTION_CLASSES">
            Попробуйте изменить параметры поиска или фильтры
          </p>
          <button
            @click="clearFilters"
            :class="CLEAR_FILTERS_BUTTON_CLASSES"
          >
            Сбросить фильтры
          </button>
        </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { SidebarWrapper, ContentCard } from '@/src/shared/layouts/components'
import { FilterPanel } from '@/src/features/masters-filter'
import { UniversalMap } from '@/src/features/map'
import { MasterCard, MasterCardListItem, useMasterList } from '@/src/entities/master'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-6'
const MOBILE_FILTER_BUTTON_CLASSES = 'md:hidden flex items-center gap-2 w-full justify-center py-3 px-4 bg-blue-600 text-white rounded-lg font-medium'
const FILTER_ICON_CLASSES = 'w-5 h-5'
const FILTER_COUNT_CLASSES = 'ml-1 px-2 py-0.5 text-xs bg-blue-500 rounded-full'
const LAYOUT_CLASSES = 'flex gap-6'
const SIDEBAR_BASE_CLASSES = 'w-80 flex-shrink-0'
const SIDEBAR_MOBILE_CLASSES = 'fixed inset-0 z-40 md:relative md:inset-auto'
const SIDEBAR_TITLE_CLASSES = 'font-semibold text-lg'
const CLOSE_MOBILE_BUTTON_CLASSES = 'md:hidden p-2 hover:bg-gray-100 rounded-lg'
const CLOSE_ICON_CLASSES = 'w-5 h-5'
const MAIN_CONTENT_CLASSES = 'flex-1 min-w-0'
const CONTROLS_CLASSES = 'flex items-center justify-between flex-wrap gap-4 mb-6'
const RESULTS_INFO_CLASSES = 'flex items-center gap-2'
const RESULTS_COUNT_CLASSES = 'font-semibold text-gray-900'
const CITY_INFO_CLASSES = 'text-gray-600'
const VIEW_CONTROLS_CLASSES = 'flex items-center gap-4'
const VIEW_TOGGLE_CLASSES = 'flex border border-gray-300 rounded-lg overflow-hidden'
const VIEW_BUTTON_BASE_CLASSES = 'p-2 transition-colors'
const VIEW_BUTTON_ACTIVE_CLASSES = 'bg-blue-600 text-white'
const VIEW_BUTTON_INACTIVE_CLASSES = 'bg-white text-gray-600 hover:bg-gray-50'
const VIEW_ICON_CLASSES = 'w-5 h-5'
const SORT_SELECT_CLASSES = 'px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
const CONTENT_WRAPPER_CLASSES = 'min-h-[400px]'
const GRID_CONTAINER_CLASSES = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'
const LIST_CONTAINER_CLASSES = 'space-y-4'
const MAP_CONTAINER_CLASSES = 'rounded-lg overflow-hidden shadow-sm'
const PAGINATION_CLASSES = 'flex justify-center mt-8'
const LOAD_MORE_BUTTON_CLASSES = 'px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white rounded-lg font-medium transition-colors'
const LOADING_CLASSES = 'flex flex-col items-center justify-center py-12 text-gray-500'
const LOADING_ICON_CLASSES = 'w-8 h-8 animate-spin mb-4'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-12 h-12 mx-auto text-gray-400 mb-4'
const EMPTY_TITLE_CLASSES = 'text-lg font-medium text-gray-900 mb-2'
const EMPTY_DESCRIPTION_CLASSES = 'text-gray-600 mb-4'
const CLEAR_FILTERS_BUTTON_CLASSES = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors'

const props = defineProps({
  initialMasters: {
    type: Array,
    default: () => []
  },
  currentCity: {
    type: String,
    default: ''
  },
  availableCategories: {
    type: Array,
    default: () => []
  }
})

// Состояние
const viewMode = ref('grid') // grid, list, map
const showFilters = ref(false)
const sortBy = ref('rating')
const loadingMore = ref(false)
const currentPage = ref(1)
const perPage = 12

// Композабл для работы с мастерами
const { masters, loading, filters, search, setFilters, clearFilters: storeClearFilters } = useMasterList()

// Вычисляемые свойства
const activeFiltersCount = computed(() => {
  return Object.values(filters.value).filter(value => 
    value !== null && value !== '' && value !== false && 
    !(Array.isArray(value) && value.length === 0)
  ).length
})

const filteredMasters = computed(() => masters.value)

const paginatedMasters = computed(() => {
  const start = 0
  const end = currentPage.value * perPage
  return filteredMasters.value.slice(start, end)
})

const hasMorePages = computed(() => 
  paginatedMasters.value.length < filteredMasters.value.length
)

const mapMarkers = computed(() => 
  filteredMasters.value.map(master => ({
    id: master.id,
    price: master.price_from,
    name: master.name,
    is_premium: master.is_premium,
    is_verified: master.is_verified,
    tooltip: {
      title: master.name,
      subtitle: master.specialty
    }
  }))
)

// Методы
const getSidebarClasses = () => {
  return [
    SIDEBAR_BASE_CLASSES,
    showFilters.value ? SIDEBAR_MOBILE_CLASSES : 'hidden md:block'
  ].join(' ')
}

const getViewButtonClasses = (mode) => {
  return [
    VIEW_BUTTON_BASE_CLASSES,
    viewMode.value === mode ? VIEW_BUTTON_ACTIVE_CLASSES : VIEW_BUTTON_INACTIVE_CLASSES
  ].join(' ')
}

const setViewMode = (mode) => {
  viewMode.value = mode
  if (mode === 'map') {
    // Для карты показываем все результаты
    currentPage.value = Math.ceil(filteredMasters.value.length / perPage)
  }
}

const toggleMobileFilters = () => {
  showFilters.value = !showFilters.value
}

const closeMobileFilters = () => {
  showFilters.value = false
}

const handleFilterUpdate = (newFilters) => {
  setFilters(newFilters)
  currentPage.value = 1 // Сбрасываем пагинацию
  closeMobileFilters() // Закрываем мобильные фильтры
}

const handleFilterApply = (newFilters) => {
  handleFilterUpdate(newFilters)
}

const handleSortChange = () => {
  // Обновляем сортировку через фильтры
  setFilters({ ...filters.value, sort_by: sortBy.value })
  currentPage.value = 1
}

const loadMoreMasters = () => {
  if (!hasMorePages.value || loadingMore.value) return
  
  loadingMore.value = true
  currentPage.value++
  
  // Имитируем загрузку
  setTimeout(() => {
    loadingMore.value = false
  }, 500)
}

const goToMaster = (master) => {
  router.visit(`/masters/${master.id}`)
}

const handleToggleFavorite = async (master) => {
  // Обновляем локально
  master.is_favorite = !master.is_favorite
  
  // Здесь бы был API вызов
}

const handleMarkerClick = (marker) => {
  goToMaster({ id: marker.id })
}

const clearFilters = () => {
  storeClearFilters()
  currentPage.value = 1
}

const getMastersWord = () => {
  const count = filteredMasters.value.length
  const lastDigit = count % 10
  const lastTwoDigits = count % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'мастеров'
  if (lastDigit === 1) return 'мастер'
  if (lastDigit >= 2 && lastDigit <= 4) return 'мастера'
  return 'мастеров'
}

// Жизненный цикл
onMounted(() => {
  // Инициализируем мастеров если есть начальные данные
  if (props.initialMasters.length > 0) {
    masters.value = props.initialMasters
  }
})

// Закрываем мобильные фильтры при клике вне области
watch(showFilters, (show) => {
  if (show) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>