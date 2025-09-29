<template>
  <div class="service-area-map">
    <h2>Карта с радиусом обслуживания</h2>
    
    <!-- Панель управления -->
    <div class="controls-panel">
      <div class="control-group">
        <label>Радиус обслуживания:</label>
        <div class="radius-controls">
          <input 
            v-model.number="serviceRadius"
            type="range"
            min="1"
            max="50"
            step="1"
            class="radius-slider"
          >
          <input 
            v-model.number="serviceRadius"
            type="number"
            min="1"
            max="50"
            class="radius-input"
          >
          <span class="radius-unit">км</span>
        </div>
      </div>
      
      <div class="control-group">
        <label>Цвет зоны:</label>
        <div class="color-options">
          <button 
            v-for="color in colorOptions"
            :key="color.value"
            @click="selectedColor = color.value"
            :class="['color-btn', { active: selectedColor === color.value }]"
            :style="{ backgroundColor: color.value }"
            :title="color.name"
          ></button>
        </div>
      </div>

      <div class="control-group">
        <label>
          <input 
            v-model="showMultipleAreas"
            type="checkbox"
          >
          Множественные точки обслуживания
        </label>
      </div>
    </div>

    <!-- Информация о покрытии -->
    <div class="coverage-info">
      <h3>Зона покрытия:</h3>
      <div class="info-grid">
        <div class="info-item">
          <strong>Площадь покрытия:</strong>
          <span>{{ coverageArea }} км²</span>
        </div>
        <div class="info-item">
          <strong>Количество точек:</strong>
          <span>{{ servicePoints.length }}</span>
        </div>
        <div class="info-item" v-if="estimatedPopulation">
          <strong>Примерное население:</strong>
          <span>{{ estimatedPopulation.toLocaleString('ru-RU') }} чел.</span>
        </div>
        <div class="info-item" v-if="coveredDistricts.length">
          <strong>Покрываемые районы:</strong>
          <span>{{ coveredDistricts.join(', ') }}</span>
        </div>
      </div>
      
      <button 
        v-if="servicePoints.length > 0"
        @click="clearAllPoints"
        class="btn-clear-all"
      >
        Очистить все точки
      </button>
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
      height="500px"
      @click="onMapClick"
      class="map-container"
    >
      <!-- Слои карты -->
      <yandex-map-default-scheme-layer />
      <yandex-map-default-features-layer />
      
      <!-- Круги радиуса для каждой точки -->
      <yandex-map-circle
        v-for="(point, index) in servicePoints"
        :key="`circle-${index}`"
        :settings="{
          coordinates: point.coordinates,
          radius: serviceRadius * 1000, // в метрах
          fill: {
            color: selectedColor,
            opacity: 0.3
          },
          stroke: {
            color: selectedColor,
            opacity: 0.8,
            width: 2
          }
        }"
      />
      
      <!-- Маркеры точек обслуживания -->
      <yandex-map-marker
        v-for="(point, index) in servicePoints"
        :key="`marker-${index}`"
        :settings="{
          coordinates: point.coordinates,
          draggable: true
        }"
        @drag-end="(e) => onMarkerDragEnd(e, index)"
      >
        <div class="service-marker" @click="removePoint(index)">
          <svg width="32"
               height="40"
               viewBox="0 0 32 40"
               fill="none">
            <path d="M16 0C7.164 0 0 7.164 0 16C0 24.836 16 40 16 40S32 24.836 32 16C32 7.164 24.836 0 16 0Z" :fill="selectedColor"/>
            <text x="16"
                  y="21"
                  text-anchor="middle"
                  fill="white"
                  font-size="14"
                  font-weight="bold">{{ index + 1 }}</text>
          </svg>
          <div class="remove-hint">Клик для удаления</div>
        </div>
      </yandex-map-marker>

      <!-- Полигон общей зоны покрытия (если несколько точек) -->
      <yandex-map-polygon
        v-if="showMultipleAreas && servicePoints.length > 1"
        :settings="{
          coordinates: [convexHullCoordinates],
          fill: {
            color: selectedColor,
            opacity: 0.1
          },
          stroke: {
            color: selectedColor,
            opacity: 0.5,
            width: 3,
            style: 'dashed'
          }
        }"
      />

      <!-- Контролы -->
      <yandex-map-controls position="right">
        <yandex-map-zoom-control />
      </yandex-map-controls>

      <yandex-map-controls position="top-left">
        <yandex-map-geolocation-control />
      </yandex-map-controls>

      <yandex-map-controls position="bottom-left">
        <yandex-map-ruler-control />
      </yandex-map-controls>
    </yandex-map>

    <!-- Инструкции -->
    <div class="instructions">
      <h3>Как использовать:</h3>
      <ul>
        <li>Кликните на карту, чтобы добавить точку обслуживания</li>
        <li>Перетащите маркер для изменения позиции</li>
        <li>Кликните на маркер, чтобы удалить точку</li>
        <li>Настройте радиус с помощью ползунка</li>
        <li>Включите "Множественные точки" для работы с несколькими локациями</li>
        <li>Используйте линейку (внизу слева) для измерения расстояний</li>
      </ul>
    </div>

    <!-- Предустановленные примеры -->
    <div class="examples-panel">
      <h3>Примеры конфигураций:</h3>
      <div class="examples-grid">
        <button @click="loadExample('single')" class="example-btn">
          Одна точка (5 км)
        </button>
        <button @click="loadExample('multi')" class="example-btn">
          Сеть точек (3 км)
        </button>
        <button @click="loadExample('large')" class="example-btn">
          Большой радиус (25 км)
        </button>
        <button @click="loadExample('metro')" class="example-btn">
          У станций метро
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { 
  YandexMap, 
  YandexMapMarker,
  YandexMapCircle,
  YandexMapPolygon,
  YandexMapControls,
  YandexMapZoomControl,
  YandexMapGeolocationControl,
  YandexMapRulerControl,
  YandexMapDefaultSchemeLayer,
  YandexMapDefaultFeaturesLayer
} from 'vue-yandex-maps'

interface ServicePoint {
  coordinates: [number, number]
  address?: string
}

// Состояние карты
const mapCenter = ref<[number, number]>([55.755864, 37.617698]) // Москва
const mapZoom = ref(11)

// Точки обслуживания
const servicePoints = ref<ServicePoint[]>([])
const showMultipleAreas = ref(false)

// Настройки радиуса
const serviceRadius = ref(5) // в километрах

// Цветовые опции
const colorOptions = [
  { name: 'Синий', value: '#4285F4' },
  { name: 'Зеленый', value: '#34A853' },
  { name: 'Красный', value: '#EA4335' },
  { name: 'Оранжевый', value: '#FBBC04' },
  { name: 'Фиолетовый', value: '#9333EA' },
]
const selectedColor = ref('#4285F4')

// Вычисляемые свойства
const coverageArea = computed(() => {
  // Площадь круга = π * r²
  const totalArea = servicePoints.value.length * Math.PI * Math.pow(serviceRadius.value, 2)
  return totalArea.toFixed(1)
})

const estimatedPopulation = computed(() => {
  // Примерная плотность населения Москвы: 4950 чел/км²
  const density = 4950
  return Math.round(parseFloat(coverageArea.value) * density)
})

const coveredDistricts = computed(() => {
  // Упрощенное определение районов
  const districts: string[] = []
  
  servicePoints.value.forEach(point => {
    const lat = point.coordinates[0]
    const lng = point.coordinates[1]
    
    if (lat > 55.77 && lng > 37.62) districts.push('Басманный')
    else if (lat > 55.77 && lng < 37.62) districts.push('Тверской')
    else if (lat < 55.74 && lng > 37.62) districts.push('Таганский')
    else if (lat < 55.74 && lng < 37.62) districts.push('Якиманка')
    else districts.push('Центральный')
  })
  
  return [...new Set(districts)]
})

// Вычисление выпуклой оболочки для полигона
const convexHullCoordinates = computed(() => {
  if (servicePoints.value.length < 3) {
    return []
  }
  
  // Упрощенная версия - просто соединяем точки по кругу
  const expanded = servicePoints.value.map(point => {
    const angles = [0, 60, 120, 180, 240, 300]
    return angles.map(angle => {
      const rad = (angle * Math.PI) / 180
      const dx = (serviceRadius.value / 111) * Math.cos(rad) // примерное преобразование км в градусы
      const dy = (serviceRadius.value / 111) * Math.sin(rad)
      return [
        point.coordinates[0] + dy,
        point.coordinates[1] + dx / Math.cos(point.coordinates[0] * Math.PI / 180)
      ]
    })
  }).flat()
  
  return expanded
})

// Обработчики событий
const onMapClick = (event: any) => {
  if (!showMultipleAreas.value && servicePoints.value.length > 0) {
    // В режиме одной точки заменяем существующую
    servicePoints.value = [{
      coordinates: event.coordinates
    }]
  } else {
    // В режиме множественных точек добавляем новую
    servicePoints.value.push({
      coordinates: event.coordinates
    })
  }
}

const onMarkerDragEnd = (event: any, index: number) => {
  if (event.coordinates && servicePoints.value[index]) {
    servicePoints.value[index].coordinates = event.coordinates
  }
}

const removePoint = (index: number) => {
  servicePoints.value.splice(index, 1)
}

const clearAllPoints = () => {
  servicePoints.value = []
}

// Загрузка примеров
const loadExample = (type: string) => {
  switch(type) {
    case 'single':
      serviceRadius.value = 5
      servicePoints.value = [
        { coordinates: [55.755864, 37.617698] }
      ]
      showMultipleAreas.value = false
      break
      
    case 'multi':
      serviceRadius.value = 3
      servicePoints.value = [
        { coordinates: [55.755864, 37.617698] },
        { coordinates: [55.764366, 37.606755] },
        { coordinates: [55.739625, 37.653377] },
        { coordinates: [55.763594, 37.590223] }
      ]
      showMultipleAreas.value = true
      break
      
    case 'large':
      serviceRadius.value = 25
      servicePoints.value = [
        { coordinates: [55.755864, 37.617698] }
      ]
      showMultipleAreas.value = false
      mapZoom.value = 9
      break
      
    case 'metro':
      serviceRadius.value = 2
      servicePoints.value = [
        { coordinates: [55.757152, 37.615573] }, // Охотный ряд
        { coordinates: [55.757469, 37.618885] }, // Театральная
        { coordinates: [55.756741, 37.622350] }, // Площадь Революции
        { coordinates: [55.761498, 37.624669] }, // Кузнецкий мост
      ]
      showMultipleAreas.value = true
      mapZoom.value = 13
      break
  }
}

// Отслеживание изменений для родительского компонента
const emit = defineEmits<{
  'coverage-updated': [data: {
    points: ServicePoint[],
    radius: number,
    area: number,
    population: number
  }]
}>()

watch([servicePoints, serviceRadius], () => {
  emit('coverage-updated', {
    points: servicePoints.value,
    radius: serviceRadius.value,
    area: parseFloat(coverageArea.value),
    population: estimatedPopulation.value || 0
  })
}, { deep: true })
</script>

<style scoped>
.service-area-map {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: Arial, sans-serif;
}

h2 {
  color: #333;
  margin-bottom: 20px;
}

/* Панель управления */
.controls-panel {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.control-group label {
  font-weight: 500;
  color: #495057;
  font-size: 14px;
}

/* Контролы радиуса */
.radius-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.radius-slider {
  width: 150px;
  height: 6px;
  -webkit-appearance: none;
  appearance: none;
  background: #ddd;
  border-radius: 3px;
  outline: none;
}

.radius-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  background: #4285F4;
  border-radius: 50%;
  cursor: pointer;
}

.radius-slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  background: #4285F4;
  border-radius: 50%;
  cursor: pointer;
}

.radius-input {
  width: 60px;
  padding: 6px 8px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 14px;
}

.radius-unit {
  color: #6c757d;
  font-size: 14px;
}

/* Выбор цвета */
.color-options {
  display: flex;
  gap: 8px;
}

.color-btn {
  width: 32px;
  height: 32px;
  border: 2px solid transparent;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.3s;
}

.color-btn:hover {
  transform: scale(1.1);
}

.color-btn.active {
  border-color: #333;
  box-shadow: 0 0 0 2px white, 0 0 0 4px #333;
}

/* Информация о покрытии */
.coverage-info {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.coverage-info h3 {
  margin-top: 0;
  color: #495057;
  margin-bottom: 15px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 15px;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item strong {
  color: #6c757d;
  font-size: 13px;
  margin-bottom: 4px;
}

.info-item span {
  color: #212529;
  font-size: 16px;
  font-weight: 500;
}

.btn-clear-all {
  padding: 8px 16px;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s;
}

.btn-clear-all:hover {
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
.service-marker {
  position: relative;
  cursor: pointer;
  transition: transform 0.2s;
}

.service-marker:hover {
  transform: scale(1.1);
}

.remove-hint {
  position: absolute;
  bottom: -25px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0,0,0,0.8);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  white-space: nowrap;
  opacity: 0;
  transition: opacity 0.2s;
  pointer-events: none;
}

.service-marker:hover .remove-hint {
  opacity: 1;
}

/* Инструкции */
.instructions {
  margin-top: 20px;
  padding: 15px;
  background: #e8f4f8;
  border-left: 4px solid #4285F4;
  border-radius: 4px;
}

.instructions h3 {
  margin-top: 0;
  color: #4285F4;
}

.instructions ul {
  margin: 10px 0;
  padding-left: 20px;
}

.instructions li {
  margin: 5px 0;
  color: #555;
}

/* Панель примеров */
.examples-panel {
  margin-top: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.examples-panel h3 {
  margin-top: 0;
  color: #495057;
  margin-bottom: 15px;
}

.examples-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.example-btn {
  padding: 12px 16px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 14px;
  color: #495057;
}

.example-btn:hover {
  background: #4285F4;
  color: white;
  border-color: #4285F4;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(66, 133, 244, 0.2);
}
</style>