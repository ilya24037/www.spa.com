<template>
  <div class="address-map-section">
    <!-- Заголовок секции -->
    <div class="mb-6">
      <h3 class="text-base font-medium text-gray-900 mb-2">Ваш адрес</h3>
      <p class="text-sm text-gray-600 leading-relaxed mb-4">
        Клиенты выбирают исполнителя по точному адресу, когда ищут услуги поблизости.
      </p>
    </div>

    <!-- Поисковая строка -->
    <div class="search-container relative mb-4">
      <input
        v-model="searchQuery"
        @input="handleSearchInput"
        @focus="showSuggestions = true"
        @blur="hideSuggestions"
        type="text"
        placeholder="Введите адрес для поиска..."
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
        :class="{ 'pr-10': searchQuery }"
      />
      
      <!-- Кнопка сброса адреса -->
      <button
        v-if="searchQuery"
        type="button"
        @click="clearAddress"
        class="absolute top-1/2 right-3 -translate-y-1/2 p-1.5 bg-transparent border-0 cursor-pointer text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-full transition-colors focus:outline-none"
        title="Очистить адрес"
      >
        <span class="text-sm">×</span>
      </button>
      
      <!-- Список подсказок -->
      <div v-if="showSuggestions && suggestions.length > 0" class="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <div
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @click="selectSuggestion(suggestion)"
          class="px-4 py-3 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
        >
          <div class="font-medium text-gray-900">{{ suggestion.name }}</div>
          <div class="text-sm text-gray-500">{{ suggestion.description }}</div>
        </div>
      </div>
    </div>

    <!-- Карта -->
    <div class="map-container rounded-lg overflow-hidden border border-gray-200">
      <!-- Vue Yandex Maps компонент с обработкой ошибок -->
      <Suspense>
        <template #default>
          <YandexMap
            :settings="mapSettings"
            :width="'100%'"
            :height="'320px'"
            @click="handleMapClick"
          >
            <YandexMapDefaultSchemeLayer />
            
            <!-- Слушатель событий карты для обратного геокодинга -->
            <YandexMapListener :settings="listenerSettings" />
            
            <!-- Элементы управления картой -->
            <YandexMapControls :settings="{ position: 'right' }">
              <YandexMapControl>
                <div class="flex flex-col bg-white rounded shadow-md">
                  <button 
                    type="button"
                    @click.stop="zoomIn" 
                    class="px-3 py-2 hover:bg-gray-100 border-b text-lg font-bold"
                    title="Приблизить"
                  >
                    +
                  </button>
                  <button 
                    type="button"
                    @click.stop="zoomOut" 
                    class="px-3 py-2 hover:bg-gray-100 text-lg font-bold"
                    title="Отдалить"
                  >
                    −
                  </button>
                </div>
              </YandexMapControl>
            </YandexMapControls>
          </YandexMap>
        </template>
        
        <template #fallback>
          <div class="w-full h-80 bg-gray-100 rounded-lg flex items-center justify-center">
            <div class="text-center">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
              <p class="text-gray-600">Загрузка карты...</p>
            </div>
          </div>
        </template>
      </Suspense>
      
      <!-- Центральный маркер -->
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-full pointer-events-none z-10">
        <div class="w-8 h-8 bg-red-500 rounded-full border-2 border-white shadow-lg relative">
          <div class="absolute top-full left-1/2 -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-red-500"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * AddressMapSection - компонент карты и поиска адреса (стабильная версия)
 * 
 * Ответственность:
 * - Yandex Maps интеграция через vue-yandex-maps
 * - Поиск адреса с подсказками через Yandex Geocoder API
 * - Геокодинг и обратный геокодинг
 * - Обработка кликов и движения карты
 * - Управление зумом
 * 
 * Интерфейс:
 * - Props: initialAddress, initialCoordinates, initialZoom
 * - Emits: update:address, update:coordinates, data-changed
 * 
 * ВЕРСИЯ: Восстановленная стабильная версия без Singleton
 */

import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue'
import { YandexMap, YandexMapDefaultSchemeLayer, YandexMapControls, YandexMapControl, YandexMapListener } from 'vue-yandex-maps'

// Интерфейсы
interface Props {
  initialAddress?: string
  initialCoordinates?: { lat: number; lng: number }
  initialZoom?: number
}

interface Emits {
  'update:address': [address: string]
  'update:coordinates': [coords: { lat: number; lng: number }]
  'data-changed': [data: { address: string; coordinates: { lat: number; lng: number } | null }]
}

interface Suggestion {
  name: string
  description: string
  coordinates: { lat: number; lng: number }
}

// Props
const props = withDefaults(defineProps<Props>(), {
  initialAddress: '',
  initialCoordinates: () => ({ lat: 55.7558, lng: 37.6176 }), // Москва по умолчанию
  initialZoom: 12
})

const emit = defineEmits<Emits>()

// Состояние поиска адреса
const searchQuery = ref(props.initialAddress)
const suggestions = ref<Suggestion[]>([])
const showSuggestions = ref(false)
const isSearching = ref(false)

// Состояние карты
const currentCoordinates = ref<[number, number]>([
  props.initialCoordinates?.lat ?? 55.7558, 
  props.initialCoordinates?.lng ?? 37.6176
])
const currentZoom = ref(props.initialZoom)
const isNavigating = ref(false) // Флаг программной навигации

// Таймеры для debounce
let actionEndTimeout: ReturnType<typeof setTimeout> | null = null

// Отслеживание предыдущих координат для определения реального движения
let previousCoordinates: [number, number] | null = null

// Обработка завершения действий на карте (движение, зум)
const handleActionEnd = (event: any) => {
  try {
    if (event && event.location && Array.isArray(event.location.center) && event.location.center.length >= 2) {
      const [lng, lat] = event.location.center
      const zoom = event.location.zoom
      
      // Проверяем что координаты валидные
      if (typeof lng === 'number' && typeof lat === 'number') {
        // Обновляем зум всегда
        if (typeof zoom === 'number') {
          currentZoom.value = zoom
        }
        
        // Проверяем существенно ли изменились координаты (порог 0.00001 градуса ~ 1 метр)
        const coordinatesChanged = !previousCoordinates || 
          Math.abs(previousCoordinates[0] - lng) > 0.00001 || 
          Math.abs(previousCoordinates[1] - lat) > 0.00001
        
        if (coordinatesChanged) {
          // Координаты действительно изменились - обновляем
          currentCoordinates.value = [lng, lat]
          previousCoordinates = [lng, lat]
          
          // Debounce для обратного геокодинга при движении карты
          if (actionEndTimeout) {
            clearTimeout(actionEndTimeout)
          }
          
          actionEndTimeout = setTimeout(() => {
            // Получаем адрес только если не происходит навигация
            if (!isNavigating.value) {
              performReverseGeocoding(lat, lng)
            }
          }, 500) // Задержка 500мс для плавного движения карты
        }
        // Если координаты не изменились (только зум) - не вызываем геокодинг
      }
    }
  } catch (error) {
    console.warn('Ошибка обработки завершения действия на карте:', error)
  }
}

// Настройки карты с принудительным растровым режимом
const mapSettings = computed(() => ({
  location: {
    center: currentCoordinates.value,
    zoom: currentZoom.value
  },
  mode: 'raster', // Принудительно растровый режим (исправляет vector: internal error)
  behaviors: ['default', 'scrollZoom', 'dblClickZoom', 'drag'], // Поведения карты
  controls: [] // Отключаем стандартные контролы
}))

// Настройки слушателя карты для обратного геокодинга
const listenerSettings = {
  onActionEnd: handleActionEnd
}

// Методы работы с картой
const zoomIn = () => {
  if (currentZoom.value < 18) {
    currentZoom.value++
  }
}

const zoomOut = () => {
  if (currentZoom.value > 0) {
    currentZoom.value--
  }
}

// Обработка клика по карте
const handleMapClick = (event: any) => {
  const coords = event.coordinates || event.detail?.coordinates
  if (coords && Array.isArray(coords) && coords.length >= 2) {
    const [lng, lat] = coords // Карта возвращает [lng, lat]
    currentCoordinates.value = [lng, lat]
    previousCoordinates = [lng, lat] // Обновляем и предыдущие координаты
    
    // При клике сразу вызываем геокодинг без задержки
    performReverseGeocoding(lat, lng) // Обратный геокодинг принимает (lat, lng)
  }
}

// Поиск адресов через Yandex Geocoder API
const performAddressSearch = async (query: string): Promise<Suggestion[]> => {
  if (!query.trim() || query.length < 3) {
    return []
  }

  try {
    isSearching.value = true
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?format=json&geocode=${encodeURIComponent(query)}&results=5&lang=ru_RU&apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18`
    )
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    const found = data?.response?.GeoObjectCollection?.featureMember || []
    
    return found.map((item: any) => {
      const geoObject = item.GeoObject
      const point = geoObject.Point.pos.split(' ')
      
      return {
        name: geoObject.metaDataProperty?.GeocoderMetaData?.text || geoObject.name || 'Адрес',
        description: geoObject.description || '',
        coordinates: { 
          lat: parseFloat(point[1]), 
          lng: parseFloat(point[0]) 
        }
      }
    })
    
  } catch (error) {
    console.warn('Ошибка поиска адреса:', error)
    return []
  } finally {
    isSearching.value = false
  }
}

// Обратный геокодинг (координаты -> адрес)
const performReverseGeocoding = async (lat: number, lng: number) => {
  try {
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?format=json&geocode=${lng},${lat}&results=1&lang=ru_RU&apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18`
    )
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    const found = data?.response?.GeoObjectCollection?.featureMember?.[0]
    
    if (found) {
      const geoObject = found.GeoObject
      const newAddress = geoObject.metaDataProperty?.GeocoderMetaData?.text || geoObject.name || ''
      
      if (newAddress && newAddress !== searchQuery.value) {
        searchQuery.value = newAddress
        emitUpdates(newAddress, { lat, lng })
      }
    }
    
  } catch (error) {
    console.warn('Ошибка обратного геокодинга:', error)
  }
}

// Обработчики поиска
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const handleSearchInput = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(async () => {
    if (searchQuery.value && searchQuery.value.length >= 3) {
      suggestions.value = await performAddressSearch(searchQuery.value)
      showSuggestions.value = suggestions.value.length > 0
    } else {
      suggestions.value = []
      showSuggestions.value = false
    }
  }, 300)
}

const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const selectSuggestion = (suggestion: Suggestion) => {
  searchQuery.value = suggestion.name
  showSuggestions.value = false
  
  // Устанавливаем флаг навигации чтобы не срабатывал обратный геокодинг
  isNavigating.value = true
  
  // Обновляем координаты и зум
  const newCoords: [number, number] = [suggestion.coordinates.lng, suggestion.coordinates.lat]
  currentCoordinates.value = newCoords
  previousCoordinates = newCoords // Обновляем и предыдущие координаты
  currentZoom.value = 15 // Приближаем для детального просмотра
  
  // Сбрасываем флаг навигации через 1000мс (даём время карте переместиться)
  setTimeout(() => {
    isNavigating.value = false
  }, 1000)
  
  emitUpdates(suggestion.name, suggestion.coordinates)
}

const clearAddress = () => {
  searchQuery.value = ''
  suggestions.value = []
  showSuggestions.value = false
  emitUpdates('', null)
}

// Эмиты событий для родительского компонента
const emitUpdates = (address: string, coordinates: { lat: number; lng: number } | null) => {
  emit('update:address', address)
  
  if (coordinates) {
    emit('update:coordinates', coordinates)
  }
  
  emit('data-changed', { address, coordinates })
}

// Watchers для инициализации
watch(() => props.initialAddress, (newAddress) => {
  if (newAddress && newAddress !== searchQuery.value) {
    searchQuery.value = newAddress
  }
}, { immediate: true })

watch(() => props.initialCoordinates, (newCoords) => {
  if (newCoords && newCoords.lat !== undefined && newCoords.lng !== undefined) {
    const coords: [number, number] = [newCoords.lng, newCoords.lat]
    currentCoordinates.value = coords
    previousCoordinates = coords // Инициализируем предыдущие координаты
  }
}, { deep: true, immediate: true })

watch(() => props.initialZoom, (newZoom) => {
  if (typeof newZoom === 'number') {
    currentZoom.value = newZoom
  }
}, { immediate: true })

// Очистка таймеров при размонтировании
onBeforeUnmount(() => {
  if (actionEndTimeout) {
    clearTimeout(actionEndTimeout)
    actionEndTimeout = null
  }
  if (searchTimeout) {
    clearTimeout(searchTimeout)
    searchTimeout = null
  }
})
</script>

<style scoped>
.address-map-section {
  @apply space-y-4;
}

.search-container {
  position: relative;
}

.map-container {
  position: relative;
  background: #f5f5f5;
}

/* Анимации и переходы */
.transition-colors {
  transition: all 0.2s ease-in-out;
}

/* Responsive */
@media (max-width: 640px) {
  .map-container {
    height: 280px !important;
  }
}
</style>