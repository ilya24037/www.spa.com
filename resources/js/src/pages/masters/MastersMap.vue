<template>
  <div class="masters-map-page">
    <!-- Заголовок страницы -->
    <PageHeader
      title="Мастера на карте"
      :breadcrumbs="breadcrumbs"
    />
    
    <!-- Основной контент: фильтры + карта -->
    <div class="masters-map-container">
      <!-- Левая панель с фильтрами -->
      <aside class="filters-sidebar">
        <FilterPanel 
          @apply="handleFiltersApply" 
          @reset="handleFiltersReset"
        />
      </aside>
      
      <!-- Карта с мастерами -->
      <main class="map-container">
        <!-- Панель поиска над картой -->
        <div class="map-search-bar">
          <SearchBar
            v-model="searchQuery"
            placeholder="Адрес или район..."
            @search="handleAddressSearch"
          />
          
          <!-- Кнопки быстрых действий -->
          <div class="map-actions">
            <SecondaryButton
              @click="toggleListView"
              size="sm"
            >
              <template #icon>
                <svg v-if="!showList" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
              </template>
              {{ showList ? 'Карта' : 'Список' }}
            </SecondaryButton>
            
            <span class="masters-count">
              Найдено: {{ mapMarkers.length }} мастеров
            </span>
          </div>
        </div>
        
        <!-- Карта или список -->
        <div class="map-content">
          <!-- Режим карты -->
          <YandexMapPicker
            v-if="!showList"
            ref="mapRef"
            :multiple="true"
            :markers="mapMarkers"
            :clusterize="true"
            :show-single-marker="false"
            :height="mapHeight"
            :center="mapCenter"
            :zoom="mapZoom"
            @marker-click="handleMarkerClick"
            @cluster-click="handleClusterClick"
            @bounds-change="handleBoundsChange"
          />
          
          <!-- Режим списка -->
          <div v-else class="masters-list">
            <MasterCard
              v-for="master in masters"
              :key="master.id"
              :master="master"
              class="master-list-item"
              @click="selectMaster(master)"
            />
          </div>
        </div>
        
        <!-- Детальная информация о выбранном мастере -->
        <transition name="slide-up">
          <div v-if="selectedMaster" class="master-details-panel">
            <button 
              @click="selectedMaster = null"
              class="close-button"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            
            <MasterDetailCard
              :master="selectedMaster"
              :compact="true"
              @book="handleBooking"
            />
          </div>
        </transition>
      </main>
    </div>
    
    <!-- Модальное окно бронирования -->
    <BookingModal
      v-if="bookingMaster"
      :master="bookingMaster"
      @close="bookingMaster = null"
      @success="handleBookingSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import YandexMapPicker from '@/src/shared/ui/molecules/YandexMapPicker/YandexMapPicker.vue'
import FilterPanel from '@/src/features/masters-filter/ui/FilterPanel/FilterPanel.vue'
import SearchBar from '@/src/features/search/ui/SearchBar/SearchBar.vue'
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'
import MasterDetailCard from '@/src/entities/master/ui/MasterDetailCard/MasterDetailCard.vue'
import BookingModal from '@/src/entities/booking/ui/BookingModal/BookingModal.vue'
import PageHeader from '@/src/shared/ui/molecules/PageHeader/PageHeader.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'
import { useMapWithMasters } from '@/src/features/map/composables/useMapWithMasters'
import { useFilterStore } from '@/src/features/masters-filter/model/filter.store'

// Composables
const {
  masters,
  mapMarkers,
  isLoading,
  error,
  selectedMaster,
  mapCenter,
  mapZoom,
  loadMasters,
  handleMarkerClick,
  handleClusterClick,
  handleBoundsChange,
  updateFilterLocation
} = useMapWithMasters()

const filterStore = useFilterStore()

// Refs
const mapRef = ref()
const searchQuery = ref('')
const showList = ref(false)
const bookingMaster = ref(null)
const mapHeight = ref(600)

// Хлебные крошки
const breadcrumbs = [
  { label: 'Главная', href: '/' },
  { label: 'Мастера', href: '/masters' },
  { label: 'Карта', href: '/masters/map' }
]

// Обработчики
const handleFiltersApply = () => {
  loadMasters()
}

const handleFiltersReset = () => {
  filterStore.resetFilters()
  loadMasters()
}

const handleAddressSearch = async (query: string) => {
  if (mapRef.value) {
    await mapRef.value.searchAddress(query)
  }
}

const toggleListView = () => {
  showList.value = !showList.value
}

const selectMaster = (master: any) => {
  selectedMaster.value = master
  
  // Если в режиме карты - центрируем на мастере
  if (!showList.value && mapRef.value && master.lat && master.lng) {
    mapRef.value.updateCenter({ lat: master.lat, lng: master.lng }, 16)
  }
}

const handleBooking = (master: any) => {
  bookingMaster.value = master
}

const handleBookingSuccess = () => {
  bookingMaster.value = null
  // Можно показать уведомление об успешном бронировании
}

// Вычисление высоты карты
const calculateMapHeight = () => {
  const header = 80 // Примерная высота хедера
  const searchBar = 60 // Высота панели поиска
  const padding = 40 // Отступы
  mapHeight.value = window.innerHeight - header - searchBar - padding
}

// Lifecycle
onMounted(() => {
  calculateMapHeight()
  window.addEventListener('resize', calculateMapHeight)
  
  // Загружаем опции фильтров
  filterStore.loadFilterOptions()
  
  // Загружаем фильтры из localStorage
  filterStore.loadFiltersFromStorage()
})

onUnmounted(() => {
  window.removeEventListener('resize', calculateMapHeight)
})
</script>

<style scoped>
.masters-map-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.masters-map-container {
  display: flex;
  gap: 24px;
  padding: 24px;
  max-width: 1920px;
  margin: 0 auto;
}

.filters-sidebar {
  flex-shrink: 0;
  position: sticky;
  top: 24px;
  height: fit-content;
  max-height: calc(100vh - 48px);
  overflow-y: auto;
}

.map-container {
  flex: 1;
  min-width: 0;
  position: relative;
}

.map-search-bar {
  background: white;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  display: flex;
  gap: 16px;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.map-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.masters-count {
  font-size: 14px;
  color: #6b7280;
  white-space: nowrap;
}

.map-content {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  position: relative;
}

.masters-list {
  padding: 24px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  max-height: 600px;
  overflow-y: auto;
}

.master-list-item {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.master-list-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.master-details-panel {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-radius: 16px 16px 0 0;
  padding: 24px;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
  max-height: 400px;
  overflow-y: auto;
  z-index: 10;
}

.close-button {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f3f4f6;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}

.close-button:hover {
  background: #e5e7eb;
}

/* Анимация появления панели */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

/* Мобильная адаптация */
@media (max-width: 1024px) {
  .masters-map-container {
    flex-direction: column;
  }
  
  .filters-sidebar {
    position: static;
    max-height: none;
  }
  
  .map-search-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .map-actions {
    justify-content: space-between;
  }
}

@media (max-width: 640px) {
  .masters-map-container {
    padding: 12px;
  }
  
  .masters-list {
    grid-template-columns: 1fr;
    padding: 12px;
  }
  
  .master-details-panel {
    padding: 16px;
  }
}
</style>