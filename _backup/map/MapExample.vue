<template>
  <div class="map-example">
    <h2>Базовый пример Яндекс.Карт 3.0</h2>
    
    <!-- Информация о выбранной точке -->
    <div v-if="selectedCoordinates" class="info-panel">
      <h3>Выбранная точка:</h3>
      <p>Широта: {{ selectedCoordinates[0].toFixed(6) }}</p>
      <p>Долгота: {{ selectedCoordinates[1].toFixed(6) }}</p>
      <p v-if="selectedAddress">Адрес: {{ selectedAddress }}</p>
      <button @click="clearSelection" class="btn-clear">Очистить</button>
    </div>

    <!-- Контейнер карты -->
    <yandex-map
      v-model:center="center"
      v-model:zoom="zoom"
      :settings="{
        apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
        lang: 'ru_RU',
        coordorder: 'latlong',
        enterprise: false,
        version: '3.0'
      }"
      width="100%"
      height="500px"
      @click="onMapClick"
    >
      <!-- Слои карты -->
      <yandex-map-default-scheme-layer />
      <yandex-map-default-features-layer />
      
      <!-- Маркер выбранной точки -->
      <yandex-map-marker
        v-if="selectedCoordinates"
        :settings="{
          coordinates: selectedCoordinates,
          draggable: true
        }"
        @drag-end="onMarkerDragEnd"
      >
        <div class="custom-marker">
          <svg width="32" height="40" viewBox="0 0 32 40" fill="none">
            <path d="M16 0C7.164 0 0 7.164 0 16C0 24.836 16 40 16 40S32 24.836 32 16C32 7.164 24.836 0 16 0Z" fill="#FF0000"/>
            <circle cx="16" cy="16" r="6" fill="white"/>
          </svg>
        </div>
      </yandex-map-marker>

      <!-- Контролы карты -->
      <yandex-map-controls position="right">
        <yandex-map-zoom-control />
      </yandex-map-controls>

      <yandex-map-controls position="top-left">
        <yandex-map-geolocation-control />
      </yandex-map-controls>

      <yandex-map-controls position="top-right">
        <yandex-map-fullscreen-control />
      </yandex-map-controls>
    </yandex-map>

    <!-- Инструкции -->
    <div class="instructions">
      <h3>Инструкции:</h3>
      <ul>
        <li>Кликните на карту, чтобы выбрать точку</li>
        <li>Перетащите маркер для изменения позиции</li>
        <li>Используйте колесо мыши для масштабирования</li>
        <li>Кнопка геолокации определит ваше местоположение</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  YandexMap, 
  YandexMapMarker,
  YandexMapControls,
  YandexMapZoomControl,
  YandexMapGeolocationControl,
  YandexMapFullscreenControl,
  YandexMapDefaultSchemeLayer,
  YandexMapDefaultFeaturesLayer
} from 'vue-yandex-maps'

// Координаты центра карты (Москва)
const center = ref([55.755864, 37.617698])
const zoom = ref(12)

// Выбранные координаты
const selectedCoordinates = ref<[number, number] | null>(null)
const selectedAddress = ref<string>('')

// Обработчик клика по карте
const onMapClick = (event: any) => {
  if (event.coordinates) {
    selectedCoordinates.value = event.coordinates
    geocodeCoordinates(event.coordinates)
  }
}

// Обработчик перетаскивания маркера
const onMarkerDragEnd = (event: any) => {
  if (event.coordinates) {
    selectedCoordinates.value = event.coordinates
    geocodeCoordinates(event.coordinates)
  }
}

// Геокодирование координат в адрес
const geocodeCoordinates = async (coords: [number, number]) => {
  try {
    // Здесь должен быть запрос к API геокодера
    // Для примера используем заглушку
    selectedAddress.value = `Адрес для координат ${coords[0].toFixed(4)}, ${coords[1].toFixed(4)}`
    
    // В реальном приложении:
    // const response = await fetch(`https://geocode-maps.yandex.ru/1.x/?apikey=ваш-ключ&format=json&geocode=${coords[1]},${coords[0]}`)
    // const data = await response.json()
    // selectedAddress.value = data.response.GeoObjectCollection.featureMember[0].GeoObject.metaDataProperty.GeocoderMetaData.text
  } catch (error) {
    console.error('Ошибка геокодирования:', error)
    selectedAddress.value = ''
  }
}

// Очистка выбора
const clearSelection = () => {
  selectedCoordinates.value = null
  selectedAddress.value = ''
}

onMounted(() => {
  console.log('MapExample компонент загружен')
})
</script>

<style scoped>
.map-example {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: Arial, sans-serif;
}

h2 {
  color: #333;
  margin-bottom: 20px;
}

.info-panel {
  background: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.info-panel h3 {
  margin-top: 0;
  color: #555;
}

.info-panel p {
  margin: 5px 0;
  color: #666;
}

.btn-clear {
  margin-top: 10px;
  padding: 8px 16px;
  background: #ff4444;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-clear:hover {
  background: #cc0000;
}

.custom-marker {
  cursor: pointer;
  animation: bounce 0.5s ease-in-out;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.instructions {
  margin-top: 20px;
  padding: 15px;
  background: #e8f4f8;
  border-left: 4px solid #0078ff;
  border-radius: 4px;
}

.instructions h3 {
  margin-top: 0;
  color: #0078ff;
}

.instructions ul {
  margin: 10px 0;
  padding-left: 20px;
}

.instructions li {
  margin: 5px 0;
  color: #555;
}
</style>