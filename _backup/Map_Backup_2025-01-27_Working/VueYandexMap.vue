<template>
  <div class="vue-yandex-map-container">
    <!-- Поиск адреса -->
    <div class="mb-4 relative">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Поиск адреса..."
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        @input="handleSearchInput"
        @keydown.enter="handleSearchInput"
      />
      <!-- Подсказки -->
      <div v-if="showSuggestions && suggestions.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
        <div
          v-for="(suggestion, index) in suggestions"
          :key="index"
          class="px-3 py-2 hover:bg-gray-100 cursor-pointer"
          @click="selectSuggestion(suggestion)"
        >
          <div class="font-medium">{{ suggestion.displayName }}</div>
          <div v-if="suggestion.description" class="text-sm text-gray-500">{{ suggestion.description }}</div>
        </div>
      </div>
    </div>

    <!-- Карта с фиксированным маркером по центру -->
    <div class="vue-yandex-map relative" :style="{ height: height + 'px' }">
      <!-- Фиксированный маркер по центру карты -->
      <div class="fixed-center-marker">
        <div class="marker-pin"></div>
        <div class="marker-pulse"></div>
      </div>
      
      <YandexMap
        ref="mapRef"
        :settings="mapSettings"
        @input="handleMapInput"
        @click="handleMapClick"
      >
        <!-- Схема -->
        <YandexMapDefaultSchemeLayer />
        
        <!-- Слушатель событий карты для отслеживания движения -->
        <YandexMapListener
          :settings="listenerSettings"
        />
        
        <!-- Элементы управления -->
        <YandexMapControls :settings="controlsSettings">
          <YandexMapZoomControl />
        </YandexMapControls>
      </YandexMap>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { 
  YandexMap, 
  YandexMapDefaultSchemeLayer, 
  YandexMapControls, 
  YandexMapZoomControl,
  YandexMapListener
} from 'vue-yandex-maps'

// Props
interface Props {
  initialAddress?: string
  initialCoordinates?: [number, number]
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  initialAddress: '',
  initialCoordinates: () => [37.6176, 55.7558], // Москва по умолчанию
  height: 360
})

// Emits
const emit = defineEmits<{
  addressSelected: [data: { address: string, lat: number, lng: number, precision: string }]
  coordinatesChanged: [data: { lat: number, lng: number }]
}>()

// Состояние
const searchQuery = ref(props.initialAddress)
const suggestions = ref<Array<{displayName: string, description: string, coordinates: [number, number], precision: string}>>([])
const showSuggestions = ref(false)
const mapRef = ref()
const currentCoordinates = ref<[number, number]>(props.initialCoordinates)
const currentZoom = ref(12)
let searchTimeout: NodeJS.Timeout | null = null
let geocodeTimeout: NodeJS.Timeout | null = null

// Настройки карты (реактивные для обновления)
const mapSettings = computed(() => ({
  location: {
    center: currentCoordinates.value,
    zoom: currentZoom.value
  },
  mode: 'raster'
}))

// Настройки элементов управления
const controlsSettings = {
  position: 'right'
}

// Настройки слушателя событий карты
const listenerSettings = {
  onActionEnd: handleActionEnd
}

// Обработка ввода карты (получение экземпляра карты)
const handleMapInput = (map: any) => {
  if (map) {
    mapRef.value = map
  }
}

// Обработка окончания действия (движение, зум)
function handleActionEnd(event: any) {
  if (event && event.location) {
    const [lng, lat] = event.location.center
    
    // Обновляем текущие координаты
    currentCoordinates.value = [lng, lat]
    currentZoom.value = event.location.zoom || currentZoom.value
    
    // Эмитим событие изменения координат
    emit('coordinatesChanged', { lat, lng })
    
    // Получаем адрес для новых координат
    getAddressFromCoordinates(lat, lng)
  }
}

// Получение адреса по координатам (обратный геокодинг)
const getAddressFromCoordinates = async (lat: number, lng: number) => {
  if (geocodeTimeout) {
    clearTimeout(geocodeTimeout)
  }
  
  geocodeTimeout = setTimeout(async () => {
    try {
      const apiKey = '23ff8acc-835f-4e99-8b19-d33c5d346e18'
      const response = await fetch(
        `https://geocode-maps.yandex.ru/1.x/?apikey=${apiKey}&geocode=${lng},${lat}&format=json&results=1`
      )
      
      if (!response.ok) throw new Error('Network response was not ok')
      
      const data = await response.json()
      const geoObjects = data.response?.GeoObjectCollection?.featureMember || []
      
      if (geoObjects.length > 0) {
        const geoObject = geoObjects[0].GeoObject
        const address = geoObject.metaDataProperty.GeocoderMetaData.text
        
        searchQuery.value = address
        
        emit('addressSelected', {
          address,
          lat,
          lng,
          precision: geoObject.metaDataProperty.GeocoderMetaData.precision || 'house'
        })
      }
    } catch (error) {
      console.error('Ошибка обратного геокодинга:', error)
    }
  }, 500)
}

// Поиск адресов через Yandex Geocoder API
const searchAddress = async (query: string) => {
  if (!query || query.length < 2) return []
  
  try {
    const apiKey = '23ff8acc-835f-4e99-8b19-d33c5d346e18'
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?apikey=${apiKey}&geocode=${encodeURIComponent(query)}&results=5&format=json`
    )
    
    if (!response.ok) throw new Error('Network response was not ok')
    
    const data = await response.json()
    const geoObjects = data.response?.GeoObjectCollection?.featureMember || []
    
    return geoObjects.map((item: any) => {
      const geoObject = item.GeoObject
      const pos = geoObject.Point.pos.split(' ').map(Number)
      const coordinates = [pos[1], pos[0]]
      
      return {
        displayName: geoObject.metaDataProperty.GeocoderMetaData.text,
        description: geoObject.description || geoObject.name || '',
        coordinates: coordinates as [number, number],
        precision: geoObject.metaDataProperty.GeocoderMetaData.precision || 'unknown'
      }
    })
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
    return []
  }
}

// Обработка поиска
const handleSearchInput = async () => {
  if (!searchQuery.value.trim()) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(async () => {
    if (searchQuery.value.length >= 2) {
      suggestions.value = await searchAddress(searchQuery.value)
      showSuggestions.value = suggestions.value.length > 0
    } else {
      suggestions.value = []
      showSuggestions.value = false
    }
  }, 300)
}

// Выбор подсказки
const selectSuggestion = (suggestion: any) => {
  searchQuery.value = suggestion.displayName
  suggestions.value = []
  showSuggestions.value = false
  
  // Обновляем координаты карты
  const [lat, lng] = suggestion.coordinates
  currentCoordinates.value = [lng, lat] // Преобразуем обратно в [lng, lat] для карты
  currentZoom.value = 15 // Устанавливаем зум для детального просмотра
  
  // Эмитим событие выбора адреса
  emit('addressSelected', {
    address: suggestion.displayName,
    lat,
    lng,
    precision: suggestion.precision
  })
}

// Обработка клика по карте
const handleMapClick = (event: any) => {
  if (event.coordinates) {
    const [lng, lat] = event.coordinates
    
    // Обновляем текущие координаты
    currentCoordinates.value = [lng, lat]
    
    // Эмитим событие изменения координат
    emit('coordinatesChanged', { lat, lng })
    
    // Получаем адрес для новых координат
    getAddressFromCoordinates(lat, lng)
  }
}

// Следим за изменениями адреса
watch(() => props.initialAddress, (newAddress) => {
  searchQuery.value = newAddress
})

// Следим за изменениями начальных координат
watch(() => props.initialCoordinates, (newCoords) => {
  if (newCoords) {
    currentCoordinates.value = newCoords
  }
})

onMounted(() => {
  // console.log('🗺️ [VueYandexMap] Компонент смонтирован')
})
</script>

<style scoped>
.vue-yandex-map-container {
  @apply w-full relative;
}

.vue-yandex-map {
  @apply w-full rounded-lg overflow-hidden border border-gray-200;
}

/* Фиксированный маркер по центру карты */
.fixed-center-marker {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 10;
  pointer-events: none;
}

.marker-pin {
  width: 24px;
  height: 24px;
  background: #ef4444;
  border: 3px solid #ffffff;
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.marker-pulse {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 40px;
  height: 40px;
  background: #ef4444;
  border-radius: 50%;
  opacity: 0.3;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: translate(-50%, -50%) scale(0.8);
    opacity: 0.3;
  }
  50% {
    transform: translate(-50%, -50%) scale(1.2);
    opacity: 0.1;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.4);
    opacity: 0;
  }
}
</style>
