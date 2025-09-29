<template>
  <div class="yandex-map-vue">
    <!-- Ошибка загрузки карты -->
    <div v-if="mapError" class="map-error">
      <div class="error-content">
        <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <p class="error-message">{{ mapError }}</p>
        <button @click="() => { mapError = null; mapReady = false }" class="retry-button">
          Повторить попытку
        </button>
      </div>
    </div>
    
    <!-- Простая карта с минимальными слоями -->
    <YandexMap
      v-if="!mapError"
      v-model:center="mapCenter"
      v-model:zoom="mapZoom"
      :settings="{
        location: {
          center: mapCenter,
          zoom: mapZoom
        },
        mode: 'raster',
        theme: 'light'
      }"
      :width="width"
      :height="`${height}px`"
      @click="onMapClick"
      @map-was-initialized="onMapInitialized"
      @error="onMapError"
    >
      <!-- Обязательные слои для работы маркеров -->
      <YandexMapDefaultSchemeLayer :settings="{}" />
      <YandexMapDefaultFeaturesLayer :settings="{}" />
      
      <!-- Маркер выбранной точки для подачи объявления -->
      <YandexMapMarker
        v-if="selectedCoordinates"
        :settings="{
          coordinates: selectedCoordinates,
          draggable: true
        }"
        @drag-end="onMarkerDragEnd"
      >
        <div class="custom-marker selected-marker">
          <svg width="32" height="40" viewBox="0 0 32 40" fill="none">
            <path d="M16 0C7.164 0 0 7.164 0 16C0 24.836 16 40 16 40S32 24.836 32 16C32 7.164 24.836 0 16 0Z" fill="#34A853"/>
            <circle cx="16" cy="16" r="6" fill="white"/>
          </svg>
        </div>
      </YandexMapMarker>
      
      <!-- Маркеры объявлений/мастеров -->
      <YandexMapMarker
        v-for="master in filteredMasters"
        :key="master.id"
        :settings="{
          coordinates: [
            Number(master.lat || master.latitude || 0), 
            Number(master.lng || master.longitude || 0)
          ],
          draggable: false
        }"
        @click="() => onMasterClick(master)"
      >
        <div class="custom-marker master-marker">
          <svg width="32" height="40" viewBox="0 0 32 40" fill="none">
            <path d="M16 0C7.164 0 0 7.164 0 16C0 24.836 16 40 16 40S32 24.836 32 16C32 7.164 24.836 0 16 0Z" fill="#4285F4"/>
            <circle cx="16" cy="16" r="6" fill="white"/>
          </svg>
        </div>
      </YandexMapMarker>
    </YandexMap>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { 
  YandexMap, 
  YandexMapMarker,
  YandexMapDefaultSchemeLayer,
  YandexMapDefaultFeaturesLayer
} from 'vue-yandex-maps'

// TypeScript интерфейсы
interface Master {
  id: number
  lat?: number
  lng?: number
  latitude?: number
  longitude?: number
  name: string
  photo?: string
}

interface Location {
  address: string
  city?: string
  district?: string
  coordinates: [number, number]
}

interface Props {
  masters?: Master[]
  height?: number
  center?: [number, number]
  zoom?: number
  showSearch?: boolean
  showLocationInfo?: boolean
  showControls?: boolean
  width?: string
}

const props = withDefaults(defineProps<Props>(), {
  masters: () => [],
  height: 400,
  center: () => [55.7558, 37.6173], // Москва по умолчанию
  zoom: 10,
  showSearch: false,
  showLocationInfo: false,
  showControls: true,
  width: '100%'
})

const emit = defineEmits<{
  ready: []
  addressSelect: [location: Location]
  markerClick: [master: Master]
}>()

// Реактивные переменные для карты
const mapCenter = ref<[number, number]>(props.center)
const mapZoom = ref(props.zoom)

// Выбранные координаты и адрес
const selectedCoordinates = ref<[number, number] | null>(null)
const selectedLocation = ref<Location | null>(null)

// Фильтруем мастеров с корректными координатами
const filteredMasters = computed(() => {
  return props.masters.filter(master => {
    const lat = master.lat || master.latitude
    const lng = master.lng || master.longitude
    return lat !== undefined && 
           lng !== undefined && 
           !isNaN(lat) && 
           !isNaN(lng) && 
           isFinite(lat) && 
           isFinite(lng)
  })
})

// Обработчик клика по карте
const onMapClick = (event: any) => {
  if (props.showSearch && event.coordinates) {
    selectedCoordinates.value = event.coordinates
    selectedLocation.value = {
      address: `Точка на карте: ${event.coordinates[0].toFixed(6)}, ${event.coordinates[1].toFixed(6)}`,
      coordinates: event.coordinates
    }
    emit('addressSelect', selectedLocation.value)
  }
}

// Обработчик перетаскивания маркера
const onMarkerDragEnd = (event: any) => {
  if (event.coordinates) {
    selectedCoordinates.value = event.coordinates
    selectedLocation.value = {
      address: `Точка на карте: ${event.coordinates[0].toFixed(6)}, ${event.coordinates[1].toFixed(6)}`,
      coordinates: event.coordinates
    }
    emit('addressSelect', selectedLocation.value)
  }
}

// Обработчик клика по маркеру мастера
const onMasterClick = (master: Master) => {
  emit('markerClick', master)
}

// Поиск адреса через Geocoder API
async function searchAddress(query: string): Promise<boolean> {
  if (!query || query.length < 3) return false
  
  try {
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18&format=json&geocode=${encodeURIComponent(query)}&results=1`
    )
    
    if (!response.ok) throw new Error('Geocoder API error')
    
    const data = await response.json()
    const geoObjects = data.response?.GeoObjectCollection?.featureMember || []
    
    if (geoObjects.length > 0) {
      const firstResult = geoObjects[0]
      const coords = firstResult.GeoObject.Point.pos.split(' ').map(Number)
      
      // Валидация координат
      if (!isFinite(coords[0]) || !isFinite(coords[1])) {
        console.warn('Invalid coordinates received from Geocoder')
        return false
      }
      
      // Устанавливаем новый центр карты
      mapCenter.value = [coords[1], coords[0]] // [lat, lng]
      mapZoom.value = 15
      
      // Добавляем маркер
      selectedCoordinates.value = [coords[1], coords[0]]
      selectedLocation.value = {
        address: firstResult.GeoObject.name,
        coordinates: [coords[1], coords[0]]
      }
      
      emit('addressSelect', selectedLocation.value)
      return true
    }
    return false
  } catch (error) {
    console.error('Search address error:', error)
    // Не прерываем работу карты при ошибке поиска
    return false
  }
}

// Метод для установки центра карты
const setCenter = (coords: [number, number]) => {
  mapCenter.value = coords
  mapZoom.value = 15
}

// Метод для получения текущей локации
const getSelectedLocation = () => selectedLocation.value

// Метод для очистки выбора
const clearSelection = () => {
  selectedCoordinates.value = null
  selectedLocation.value = null
}

// Следим за изменением пропсов center и zoom
watch(() => props.center, (newCenter) => {
  if (newCenter) {
    mapCenter.value = newCenter
  }
})

watch(() => props.zoom, (newZoom) => {
  if (newZoom) {
    mapZoom.value = newZoom
  }
})

// Состояние карты и обработчики ошибок
const mapReady = ref(false)
const mapError = ref<string | null>(null)

// Обработчик инициализации карты
const onMapInitialized = (mapInstance: any) => {
  mapReady.value = true
  console.log('✅ Карта успешно инициализирована', mapInstance)
  emit('ready')
}

// Обработчик ошибок карты
const onMapError = (error: any) => {
  mapError.value = error?.message || 'Произошла ошибка при загрузке карты'
  console.error('❌ Ошибка карты:', error)
}

// Эмитим событие готовности при монтировании
onMounted(() => {
  // Событие ready теперь отправляется в onMapInitialized
})

// Expose методы для внешнего использования
defineExpose({
  searchAddress,
  setCenter,
  getSelectedLocation,
  clearSelection
})
</script>

<style scoped>
.yandex-map-vue {
  width: 100%;
}

.custom-marker {
  cursor: pointer;
  transform: translate(-16px, -40px);
}

.master-marker:hover {
  transform: translate(-16px, -42px);
  transition: transform 0.2s ease;
}

.location-info {
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Стили для отображения ошибок */
.map-error {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 400px;
  background: #fef2f2;
  border: 2px dashed #fca5a5;
  border-radius: 12px;
}

.error-content {
  text-align: center;
  padding: 32px;
}

.error-icon {
  width: 48px;
  height: 48px;
  color: #dc2626;
  margin: 0 auto 16px;
}

.error-message {
  color: #dc2626;
  font-weight: 500;
  margin-bottom: 16px;
  font-size: 16px;
}

.retry-button {
  background: #dc2626;
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.retry-button:hover {
  background: #b91c1c;
}
</style>