<!-- Главная страница - полная FSD миграция -->
<template>
  <div>
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />
    
    <div>
      <!-- Заголовок -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
          Мастера массажа в {{ currentCity }}
        </h1>
        <p class="text-gray-600 mt-2">
          Найдите лучших мастеров массажа в вашем городе
        </p>
      </div>
      
      <!-- Двухколоночный layout: фильтры слева, карта/карточки справа -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Левая колонка: Только фильтры -->
        <div class="lg:col-span-1">
          <!-- Фильтры извлечены из MastersCatalog -->
          <FilterPanel @apply="handleFiltersApplied" @reset="handleFiltersReset">
            <FilterCategory 
              title="Категории услуг"
              icon="🛠️"
              :active="false"
            >
              <div class="space-y-2">
                <BaseCheckbox
                  v-for="category in categories"
                  :key="category.id"
                  :model-value="false"
                  :label="category.name"
                  @update:modelValue="handleCategoryToggle(category.id, $event)"
                />
              </div>
            </FilterCategory>
          </FilterPanel>
        </div>
        
        <!-- Правая колонка: Карта сверху, карточки снизу -->
        <div class="lg:col-span-3">
          <!-- Карта (всегда видна) -->
          <div class="mb-6">
            <div v-if="isLoading" class="map-loading">
              <div class="flex items-center justify-center h-96">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3">Загрузка карты...</span>
              </div>
            </div>
            <div v-else>
              <YandexMapPicker
                :markers="mapMarkers"
                :multiple="true"
                :clusterize="false"
                :show-single-marker="false"
                :height="400"
                :center="mapCenter"
                :zoom="mapZoom"
                @marker-click="handleMapMarkerClick"
                @bounds-change="handleBoundsChange"
              />
            </div>
            
            <!-- Панель информации о выбранном мастере -->
            <transition name="slide-up">
              <div v-if="mapSelectedMaster" class="absolute bottom-5 left-5 right-5 max-w-[400px] bg-white rounded-xl p-4 shadow-xl z-10 sm:left-5 sm:right-5 sm:max-w-none">
                <button @click="mapSelectedMaster = null" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-gray-100 border-0 flex items-center justify-center cursor-pointer transition-colors hover:bg-gray-200 z-10">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <MasterCard
                  :master="mapSelectedMaster"
                  :is-favorite="isFavorite(mapSelectedMaster.id)"
                  @toggle-favorite="toggleFavorite"
                  @booking="handleBooking"
                />
              </div>
            </transition>
          </div>
          
          <!-- Карточки мастеров (всегда видны под картой) -->
          <div>
            <MastersCatalog
              :masters="allMasters"
              :categories="categories"
              :districts="districts"
              :current-city="currentCity"
              :loading="isLoading"
              :error="error"
              :enable-virtual-scroll="enableVirtualScroll"
              :virtual-scroll-height="800"
              :view-mode="'grid'"
              @filters-applied="handleFiltersApplied"
              @master-favorited="handleMasterFavorited"
              @booking-requested="handleBookingRequested"
              @sorting-changed="handleSortingChanged"
              @retry="handleRetry"
              @load-more="handleLoadMore"
              @view-change="handleViewChange"
            >
              <!-- Кастомный master card через slot -->
              <template #master="{ master, index }">
                <MasterCard 
                  :master="master"
                  :index="index"
                  :is-favorite="isFavorite(master.id)"
                  @toggle-favorite="toggleFavorite"
                  @booking="() => handleBooking(master.id)"
                  @quick-view="openQuickView"
                />
              </template>
              
              <!-- Кастомная пагинация -->
              <template #pagination>
                <Pagination 
                  v-if="masters?.links" 
                  :links="masters.links" 
                />
              </template>
            </MastersCatalog>
          </div>
        </div>
      </div>
      
      <!-- Персонализированные рекомендации -->
      <RecommendedSection
        v-if="allMasters.length > 0"
        :masters="allMasters"
        title="Рекомендуем для вас"
        subtitle="На основе ваших предпочтений"
        section-id="personalized"
        type="personalized"
        :is-favorite="isFavorite"
        @toggle-favorite="toggleFavorite"
        @booking="handleBooking"
        @quick-view="openQuickView"
      />
      
      <!-- Популярные мастера -->
      <RecommendedSection
        v-if="allMasters.length > 0"
        :masters="allMasters"
        title="Популярные мастера"
        subtitle="Выбор наших пользователей"
        section-id="popular"
        type="popular"
        :show-indicators="true"
        :is-favorite="isFavorite"
        @toggle-favorite="toggleFavorite"
        @booking="handleBooking"
        @quick-view="openQuickView"
      />
      
      <!-- Quick View Modal -->
      <QuickViewModal
        :is-open="quickView.isOpen.value"
        :master="quickView.currentMaster.value"
        :is-favorite="quickView.currentMaster.value ? isFavorite(quickView.currentMaster.value.id) : false"
        @close="quickView.closeQuickView"
        @toggle-favorite="toggleFavorite"
        @booking="handleBooking"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'

// FSD imports
import { MastersCatalog } from '@/src/widgets/masters-catalog'
import { RecommendedSection } from '@/src/widgets/recommended-section'
import { MasterCard } from '@/src/entities/master/ui/MasterCard'
import { Pagination } from '@/src/shared/ui/molecules/Pagination'
import { QuickViewModal, useQuickView } from '@/src/features/quick-view'
import RecommendationService from '@/src/shared/services/RecommendationService'
import { FilterPanel, FilterCategory } from '@/src/features/masters-filter'
import { BaseCheckbox } from '@/src/shared/ui/atoms'
import { logger } from '@/src/shared/utils/logger'
import YandexMapPicker from '@/src/shared/ui/molecules/YandexMapPicker/YandexMapPicker.vue'
import { useMapWithMasters } from '@/src/features/map/composables/useMapWithMasters'
import type { GridView } from '@/src/shared/ui/molecules/GridControls/GridControls.vue'

// Stores - используем основные TypeScript stores
import { useFavoritesStore, type Master } from '@/stores/favorites'
import { useAuthStore } from '@/stores/authStore'

// Props из Inertia 
interface HomePageProps {
  masters?: {
    data: Master[]
    links?: any
    meta?: any
  }
  currentCity?: string
  categories?: Category[]
  districts?: string[]
}

interface Category {
  id: number
  name: string
  slug: string
}

const props = withDefaults(defineProps<HomePageProps>(), {
  currentCity: 'Москва',
  categories: () => [],
  masters: () => ({ data: [] as Master[] }),
  districts: () => []
})

// Stores
const favoritesStore = useFavoritesStore()
const authStore = useAuthStore()

// Quick View
const quickView = useQuickView()

// Local state
const isLoading = ref(false)
const error = ref<string | null>(null)
const enableVirtualScroll = ref(true) // Включить виртуальный скроллинг для больших списков
const allMasters = ref<Master[]>(props.masters?.data || []) // Все загруженные мастера
const currentPage = ref(1) // Текущая страница для виртуального скролла
const viewMode = ref<GridView>('grid') // Режим отображения: карта, сетка или список

// Map composable - передаем данные мастеров с сервера
const {
  mapMarkers,
  mapCenter,
  mapZoom,
  selectedMaster: mapSelectedMaster,
  handleMarkerClick: handleMapMarkerClick,
  handleClusterClick,
  handleBoundsChange
} = useMapWithMasters(props.masters?.data || [])

// Computed
const favoriteIds = computed(() => favoritesStore.favoriteIds)

// Methods
const isFavorite = (masterId: number): boolean => {
  return favoriteIds.value.includes(masterId)
}

const toggleFavorite = async (masterId: number) => {
  try {
    const master = allMasters.value.find(m => m.id === masterId) || 
                  props.masters?.data.find(m => m.id === masterId)
    if (master) {
      await favoritesStore.toggle(master)
      // Отслеживаем для рекомендаций
      RecommendationService.trackFavorite(masterId, !isFavorite(masterId))
    }
  } catch (err) {
    logger.error('Error toggling favorite:', err)
    error.value = 'Ошибка при обновлении избранного'
  }
}

const handleBooking = (masterOrId: number | Master) => {
  const masterId = typeof masterOrId === 'number' ? masterOrId : masterOrId.id
  
  // Отслеживаем намерение бронирования
  RecommendationService.trackBooking(masterId)
  
  if (typeof masterOrId === 'number') {
    // Переход на страницу мастера с модальным окном бронирования
    window.location.href = `/masters/${masterOrId}?booking=true`
  } else {
    // Из Quick View передается объект Master
    window.location.href = `/masters/${masterOrId.id}?booking=true`
  }
}

const openQuickView = (master: Master) => {
  quickView.openQuickView(master)
  // Отслеживаем просмотр для рекомендаций
  RecommendationService.trackMasterView(master)
}

const handleFiltersApplied = (filters: any) => {
  // Применение фильтров через Inertia
  isLoading.value = true
  
  const url = new URL(window.location.href)
  Object.keys(filters).forEach(key => {
    if (filters[key] !== null && filters[key] !== '') {
      url.searchParams.set(key, filters[key])
    } else {
      url.searchParams.delete(key)
    }
  })
  
  window.history.pushState({}, '', url.toString())
  // Здесь должен быть Inertia.get() или router.get()
}

const handleMasterFavorited = (data: { masterId: number, isFavorite: boolean }) => {
  // Обновление состояния избранного из widget
  const master = props.masters?.data.find(m => m.id === data.masterId)
  if (master) {
    if (data.isFavorite) {
      favoritesStore.addToFavorites(master)
    } else {
      favoritesStore.removeFromFavorites(data.masterId)
    }
  }
}

const handleBookingRequested = (masterId: number) => {
  handleBooking(masterId)
}

const handleRetry = () => {
  error.value = null
  window.location.reload()
}

const handleLoadMore = async () => {
  // Симуляция загрузки новых мастеров для виртуального скролла
  logger.info('Loading more masters for virtual scroll')
  
  // В реальном приложении здесь будет API запрос
  // const response = await fetch(`/api/masters?page=${currentPage.value + 1}`)
  // const newMasters = await response.json()
  
  // Для демо добавляем фиктивные данные
  setTimeout(() => {
    const newMasters = Array(20).fill(null).map((_, i) => ({
      id: allMasters.value.length + i + 1,
      name: `Мастер ${allMasters.value.length + i + 1}`,
      photo: '/images/master-placeholder.jpg',
      rating: 4.5 + Math.random() * 0.5,
      reviews_count: Math.floor(Math.random() * 100),
      price_from: 2000 + Math.floor(Math.random() * 3000),
      services: [{ id: 1, name: 'Классический массаж' }],
      district: 'Центральный',
      is_online: Math.random() > 0.5,
      is_premium: Math.random() > 0.7,
      is_verified: Math.random() > 0.5
    }))
    
    allMasters.value = [...allMasters.value, ...newMasters]
    currentPage.value++
  }, 500)
}

const handleSortingChanged = (sortingType: string) => {
  // Обработка смены сортировки
  isLoading.value = true
  
  const url = new URL(window.location.href)
  url.searchParams.set('sort', sortingType)
  
  // В реальном приложении здесь будет Inertia.get() для обновления данных
  window.history.pushState({}, '', url.toString())
  
  // Имитируем загрузку для демо
  setTimeout(() => {
    isLoading.value = false
    logger.info('Сортировка изменена на:', sortingType)
  }, 500)
}

// Обработчик изменения вида отображения
const handleViewChange = (newView: GridView) => {
  viewMode.value = newView
  
  // Сохраняем выбранный вид в localStorage
  localStorage.setItem('mastersViewMode', newView)
  
  logger.info('Режим отображения изменен на:', newView)
}

// Методы для работы с фильтрами
const handleCategoryToggle = (categoryId: string, checked: boolean) => {
  // Логика переключения категории
  // Debug: Toggle category
}

const handleFiltersReset = () => {
  // Логика сброса фильтров
  // Debug: Reset filters
}

// Initialize favorites on mount (only if authenticated)
onMounted(() => {
  // Загружаем избранное только для авторизованных пользователей
  if (authStore.isAuthenticated) {
    favoritesStore.loadFavorites()
  }
  
  // Восстанавливаем сохраненный режим отображения
  const savedViewMode = localStorage.getItem('mastersViewMode') as GridView
  if (savedViewMode && ['map', 'grid', 'list'].includes(savedViewMode)) {
    viewMode.value = savedViewMode
  }
})
</script>

<style scoped>
/* Анимация появления панели с мастером на карте */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

/* Остальные стили мигрированы на Tailwind CSS в template */
</style>