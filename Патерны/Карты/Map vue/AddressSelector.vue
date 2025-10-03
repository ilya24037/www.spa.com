<template>
  <div class="address-selector">
    <h2>Компонент выбора адреса с поиском</h2>
    
    <!-- Поле поиска адреса -->
    <div class="search-panel">
      <input 
        v-model="searchQuery"
        @input="onSearchInput"
        type="text"
        placeholder="Введите адрес: улица, дом, город..."
        class="search-input"
      >
      
      <!-- Подсказки поиска -->
      <div v-if="suggestions.length > 0" class="suggestions">
        <div 
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @click="selectSuggestion(suggestion)"
          class="suggestion-item"
        >
          <span class="suggestion-title">{{ suggestion.title }}</span>
          <span class="suggestion-subtitle">{{ suggestion.subtitle }}</span>
        </div>
      </div>
    </div>

    <!-- Выбранный адрес -->
    <div v-if="selectedLocation" class="selected-info">
      <h3>Выбранный адрес:</h3>
      <div class="info-grid">
        <div class="info-item">
          <strong>Полный адрес:</strong>
          <span>{{ selectedLocation.address }}</span>
        </div>
        <div class="info-item">
          <strong>Город:</strong>
          <span>{{ selectedLocation.city || 'Не определен' }}</span>
        </div>
        <div class="info-item">
          <strong>Район:</strong>
          <span>{{ selectedLocation.district || 'Не определен' }}</span>
        </div>
        <div class="info-item">
          <strong>Метро:</strong>
          <span>{{ selectedLocation.metro || 'Не определено' }}</span>
        </div>
        <div class="info-item">
          <strong>Координаты:</strong>
          <span>{{ selectedLocation.coordinates[0].toFixed(6) }}, {{ selectedLocation.coordinates[1].toFixed(6) }}</span>
        </div>
      </div>
      <button @click="clearLocation" class="btn-clear">Очистить адрес</button>
    </div>

    <!-- Карта -->
    <yandex-map
      v-model:center="mapCenter"
      v-model:zoom="mapZoom"
      :settings="{
        apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
        lang: 'ru_RU',
        coordorder: 'latlong',
        enterprise: false,
        version: '3.0'
      }"
      width="100%"
      height="450px"
      @click="onMapClick"
      class="map-container"
    >
      <!-- Слои карты -->
      <yandex-map-default-scheme-layer />
      <yandex-map-default-features-layer />
      
      <!-- Маркер выбранного адреса -->
      <yandex-map-marker
        v-if="selectedLocation"
        :settings="{
          coordinates: selectedLocation.coordinates,
          draggable: true
        }"
        @drag-end="onMarkerDragEnd"
      >
        <div class="address-marker">
          <svg width="40" height="48" viewBox="0 0 40 48" fill="none">
            <path d="M20 0C8.954 0 0 8.954 0 20C0 31.046 20 48 20 48S40 31.046 40 20C40 8.954 31.046 0 20 0Z" fill="#4285F4"/>
            <circle cx="20" cy="20" r="8" fill="white"/>
            <circle cx="20" cy="20" r="3" fill="#4285F4"/>
          </svg>
        </div>
      </yandex-map-marker>

      <!-- Маркеры метро рядом -->
      <yandex-map-marker
        v-for="metro in nearbyMetro"
        :key="metro.name"
        :settings="{
          coordinates: metro.coordinates,
          draggable: false
        }"
      >
        <div class="metro-marker" :title="metro.name">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="11" fill="#D52B1E" stroke="white" stroke-width="2"/>
            <text x="12" y="16" text-anchor="middle" fill="white" font-size="14" font-weight="bold">М</text>
          </svg>
        </div>
      </yandex-map-marker>

      <!-- Контролы -->
      <yandex-map-controls position="right">
        <yandex-map-zoom-control />
      </yandex-map-controls>

      <yandex-map-controls position="top-left">
        <yandex-map-geolocation-control />
      </yandex-map-controls>

      <!-- Поиск на карте -->
      <yandex-map-controls position="top-right">
        <yandex-map-search-control 
          :settings="{
            searchControlFloat: 'right',
            searchControlProvider: 'yandex#search',
            searchControlNoCentering: true
          }"
        />
      </yandex-map-controls>
    </yandex-map>

    <!-- Быстрый выбор районов -->
    <div class="districts-panel">
      <h3>Популярные районы Москвы:</h3>
      <div class="districts-grid">
        <button 
          v-for="district in popularDistricts"
          :key="district.name"
          @click="selectDistrict(district)"
          class="district-btn"
        >
          {{ district.name }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { 
  YandexMap, 
  YandexMapMarker,
  YandexMapControls,
  YandexMapZoomControl,
  YandexMapGeolocationControl,
  YandexMapSearchControl,
  YandexMapDefaultSchemeLayer,
  YandexMapDefaultFeaturesLayer
} from 'vue-yandex-maps'

interface Location {
  address: string
  city: string
  district: string
  metro: string
  coordinates: [number, number]
}

interface Metro {
  name: string
  coordinates: [number, number]
  distance: number
}

// Состояние карты
const mapCenter = ref<[number, number]>([55.755864, 37.617698]) // Москва
const mapZoom = ref(12)

// Поиск
const searchQuery = ref('')
const suggestions = ref<any[]>([])
const searchTimeout = ref<any>(null)

// Выбранная локация
const selectedLocation = ref<Location | null>(null)

// Ближайшие станции метро
const nearbyMetro = ref<Metro[]>([])

// Популярные районы Москвы
const popularDistricts = [
  { name: 'Центр', coordinates: [55.755864, 37.617698] as [number, number] },
  { name: 'Арбат', coordinates: [55.752004, 37.595855] as [number, number] },
  { name: 'Тверская', coordinates: [55.764366, 37.606755] as [number, number] },
  { name: 'Китай-город', coordinates: [55.755426, 37.632123] as [number, number] },
  { name: 'Чистые пруды', coordinates: [55.765152, 37.638861] as [number, number] },
  { name: 'Патриаршие пруды', coordinates: [55.763594, 37.590223] as [number, number] },
  { name: 'Сокольники', coordinates: [55.789766, 37.679985] as [number, number] },
  { name: 'Таганка', coordinates: [55.739625, 37.653377] as [number, number] },
]

// Поиск с задержкой
const onSearchInput = () => {
  clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    searchAddress(searchQuery.value)
  }, 500)
}

// Поиск адреса (эмуляция)
const searchAddress = async (query: string) => {
  if (query.length < 3) {
    suggestions.value = []
    return
  }

  // Эмуляция поиска
  // В реальном приложении здесь должен быть запрос к API Яндекс.Геокодера
  suggestions.value = [
    { 
      title: `${query}, дом 1`,
      subtitle: 'Москва, Центральный район',
      coordinates: [55.755864, 37.617698]
    },
    { 
      title: `${query}, дом 15`,
      subtitle: 'Москва, Тверской район',
      coordinates: [55.764366, 37.606755]
    },
    { 
      title: `улица ${query}`,
      subtitle: 'Москва, Пресненский район',
      coordinates: [55.763594, 37.590223]
    }
  ]
}

// Выбор подсказки
const selectSuggestion = (suggestion: any) => {
  searchQuery.value = suggestion.title
  suggestions.value = []
  
  const location: Location = {
    address: suggestion.title,
    city: 'Москва',
    district: suggestion.subtitle.split(', ')[1] || '',
    metro: '', // Будет определено позже
    coordinates: suggestion.coordinates
  }
  
  setLocation(location)
}

// Клик по карте
const onMapClick = (event: any) => {
  if (event.coordinates) {
    geocodeCoordinates(event.coordinates)
  }
}

// Перетаскивание маркера
const onMarkerDragEnd = (event: any) => {
  if (event.coordinates) {
    geocodeCoordinates(event.coordinates)
  }
}

// Геокодирование координат
const geocodeCoordinates = async (coords: [number, number]) => {
  // Эмуляция геокодирования
  const location: Location = {
    address: `ул. Примерная, д. ${Math.floor(Math.random() * 100)}`,
    city: 'Москва',
    district: determineDistrict(coords),
    metro: determineNearestMetro(coords),
    coordinates: coords
  }
  
  setLocation(location)
}

// Определение района по координатам (упрощенная версия)
const determineDistrict = (coords: [number, number]): string => {
  const lat = coords[0]
  const lng = coords[1]
  
  if (lat > 55.77 && lng > 37.62) return 'Басманный район'
  if (lat > 55.77 && lng < 37.62) return 'Тверской район'
  if (lat < 55.74 && lng > 37.62) return 'Таганский район'
  if (lat < 55.74 && lng < 37.62) return 'Якиманка'
  
  return 'Центральный район'
}

// Определение ближайшего метро (упрощенная версия)
const determineNearestMetro = (coords: [number, number]): string => {
  const metroStations = [
    { name: 'Охотный ряд', coords: [55.757152, 37.615573] },
    { name: 'Театральная', coords: [55.757469, 37.618885] },
    { name: 'Площадь Революции', coords: [55.756741, 37.622350] },
    { name: 'Кузнецкий мост', coords: [55.761498, 37.624669] },
    { name: 'Лубянка', coords: [55.759908, 37.627559] },
  ]
  
  let nearest = metroStations[0]
  let minDistance = getDistance(coords, nearest.coords as [number, number])
  
  for (const station of metroStations) {
    const distance = getDistance(coords, station.coords as [number, number])
    if (distance < minDistance) {
      minDistance = distance
      nearest = station
    }
  }
  
  // Обновляем ближайшие станции метро для отображения на карте
  nearbyMetro.value = metroStations
    .map(station => ({
      name: station.name,
      coordinates: station.coords as [number, number],
      distance: getDistance(coords, station.coords as [number, number])
    }))
    .sort((a, b) => a.distance - b.distance)
    .slice(0, 3)
  
  return `${nearest.name} (${(minDistance * 1000).toFixed(0)} м)`
}

// Расчет расстояния между точками (упрощенный)
const getDistance = (coords1: [number, number], coords2: [number, number]): number => {
  const R = 6371 // Радиус Земли в км
  const dLat = (coords2[0] - coords1[0]) * Math.PI / 180
  const dLon = (coords2[1] - coords1[1]) * Math.PI / 180
  const a = 
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(coords1[0] * Math.PI / 180) * Math.cos(coords2[0] * Math.PI / 180) *
    Math.sin(dLon/2) * Math.sin(dLon/2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
  return R * c
}

// Установка локации
const setLocation = (location: Location) => {
  selectedLocation.value = location
  mapCenter.value = location.coordinates
  mapZoom.value = 15
}

// Выбор района
const selectDistrict = (district: any) => {
  mapCenter.value = district.coordinates
  mapZoom.value = 14
  geocodeCoordinates(district.coordinates)
}

// Очистка выбора
const clearLocation = () => {
  selectedLocation.value = null
  searchQuery.value = ''
  nearbyMetro.value = []
  mapZoom.value = 12
}

// Эмиты для родительского компонента
const emit = defineEmits<{
  'location-selected': [location: Location]
  'location-cleared': []
}>()

// Отслеживание изменений
watch(selectedLocation, (newLocation) => {
  if (newLocation) {
    emit('location-selected', newLocation)
  } else {
    emit('location-cleared')
  }
})
</script>

<style scoped>
.address-selector {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: Arial, sans-serif;
}

h2 {
  color: #333;
  margin-bottom: 20px;
}

/* Панель поиска */
.search-panel {
  position: relative;
  margin-bottom: 20px;
}

.search-input {
  width: 100%;
  padding: 12px 16px;
  font-size: 16px;
  border: 2px solid #ddd;
  border-radius: 8px;
  transition: border-color 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #4285F4;
}

/* Подсказки */
.suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-top: 4px;
  max-height: 300px;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  z-index: 1000;
}

.suggestion-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}

.suggestion-item:hover {
  background: #f5f5f5;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-title {
  display: block;
  font-weight: 500;
  color: #333;
}

.suggestion-subtitle {
  display: block;
  font-size: 14px;
  color: #666;
  margin-top: 2px;
}

/* Информация о выбранном адресе */
.selected-info {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.selected-info h3 {
  margin-top: 0;
  color: #495057;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 12px;
  margin: 15px 0;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item strong {
  color: #495057;
  font-size: 14px;
  margin-bottom: 4px;
}

.info-item span {
  color: #212529;
  font-size: 15px;
}

.btn-clear {
  margin-top: 15px;
  padding: 10px 20px;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s;
}

.btn-clear:hover {
  background: #c82333;
}

/* Карта */
.map-container {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}

/* Маркеры */
.address-marker {
  cursor: pointer;
  animation: drop 0.5s ease-in-out;
}

.metro-marker {
  cursor: pointer;
}

@keyframes drop {
  0% { transform: translateY(-20px); opacity: 0; }
  100% { transform: translateY(0); opacity: 1; }
}

/* Панель районов */
.districts-panel {
  margin-top: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.districts-panel h3 {
  margin-top: 0;
  color: #495057;
  margin-bottom: 15px;
}

.districts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
}

.district-btn {
  padding: 10px 15px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 14px;
  color: #495057;
}

.district-btn:hover {
  background: #4285F4;
  color: white;
  border-color: #4285F4;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(66, 133, 244, 0.2);
}
</style>