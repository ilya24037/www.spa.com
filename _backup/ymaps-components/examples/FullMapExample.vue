<template>
  <div class="map-example-container">
    <!-- Панель управления -->
    <div class="control-panel">
      <h2>🗺️ Демонстрация Yandex Maps компонентов</h2>
      
      <!-- Настройки карты -->
      <div class="control-section">
        <h3>⚙️ Настройки карты</h3>
        <div class="controls-grid">
          <label>
            <input v-model="mapSettings.drag" type="checkbox" />
            Перетаскивание
          </label>
          <label>
            <input v-model="mapSettings.scrollZoom" type="checkbox" />
            Зум колесом
          </label>
          <label>
            <input v-model="mapSettings.dblClickZoom" type="checkbox" />
            Зум двойным кликом
          </label>
          <label>
            <input v-model="mapSettings.multiTouch" type="checkbox" />
            Мультитач
          </label>
          <label>
            <input v-model="mapSettings.ruler" type="checkbox" />
            Линейка
          </label>
          <label>
            <input v-model="mapSettings.locked" type="checkbox" />
            Заблокировать карту
          </label>
        </div>
      </div>

      <!-- Управление метками -->
      <div class="control-section">
        <h3>📍 Метки</h3>
        <div class="button-group">
          <button @click="addRandomMarkers(10)" class="btn btn-primary">
            Добавить 10 меток
          </button>
          <button @click="addRandomMarkers(50)" class="btn btn-primary">
            Добавить 50 меток
          </button>
          <button @click="clearMarkers" class="btn btn-danger">
            Очистить все
          </button>
          <button @click="toggleClustering" class="btn btn-info">
            {{ useClustering ? 'Отключить' : 'Включить' }} кластеризацию
          </button>
        </div>
        <p class="info-text">
          Всего меток: <strong>{{ markers.length }}</strong>
          | Кластеризация: <strong>{{ useClustering ? 'Вкл' : 'Выкл' }}</strong>
        </p>
      </div>

      <!-- Стили меток -->
      <div class="control-section">
        <h3>🎨 Стиль меток</h3>
        <select v-model="markerPreset" class="form-select">
          <option value="islands#blueIcon">Синие</option>
          <option value="islands#redIcon">Красные</option>
          <option value="islands#greenIcon">Зеленые</option>
          <option value="islands#violetIcon">Фиолетовые</option>
          <option value="islands#blackIcon">Черные</option>
          <option value="islands#orangeIcon">Оранжевые</option>
        </select>
        <label class="checkbox-label">
          <input v-model="markersAreDraggable" type="checkbox" />
          Перетаскиваемые метки
        </label>
      </div>

      <!-- Информация о событиях -->
      <div class="control-section">
        <h3>📊 События</h3>
        <div class="event-log" ref="eventLogRef">
          <div v-for="(event, index) in eventLog" :key="index" class="event-item">
            <span class="event-time">{{ event.time }}</span>
            <span class="event-message">{{ event.message }}</span>
          </div>
          <div v-if="eventLog.length === 0" class="event-empty">
            События будут отображаться здесь...
          </div>
        </div>
      </div>

      <!-- Действия -->
      <div class="control-section">
        <h3>🎯 Действия</h3>
        <div class="button-group">
          <button @click="centerMap" class="btn btn-secondary">
            Центрировать карту
          </button>
          <button @click="fitToMarkers" class="btn btn-secondary">
            Показать все метки
          </button>
          <button @click="showBalloonExample" class="btn btn-secondary">
            Показать Balloon
          </button>
          <button @click="animateRandomMarker" class="btn btn-secondary">
            Анимировать метку
          </button>
        </div>
      </div>

      <!-- Статистика -->
      <div class="control-section stats">
        <h3>📈 Статистика</h3>
        <div class="stats-grid">
          <div class="stat-item">
            <span class="stat-label">Текущий зум:</span>
            <span class="stat-value">{{ currentZoom }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Центр карты:</span>
            <span class="stat-value">{{ formatCoords(mapCenter) }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Состояние:</span>
            <span class="stat-value">{{ mapState }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Карта -->
    <div class="map-wrapper">
      <div id="yandex-map" class="map-container"></div>
      
      <!-- Компоненты Yandex Maps -->
      <YMapsBehaviors
        v-if="map"
        ref="behaviorsRef"
        :map="map"
        :drag="mapSettings.drag"
        :scroll-zoom="mapSettings.scrollZoom"
        :dbl-click-zoom="mapSettings.dblClickZoom"
        :multi-touch="mapSettings.multiTouch"
        :ruler="mapSettings.ruler"
        :locked="mapSettings.locked"
        @drag-start="onDragStart"
        @drag-end="onDragEnd"
        @zoom-change="onZoomChange"
        @ready="onBehaviorsReady"
      />

      <YMapsClusterer
        v-if="map && useClustering"
        ref="clustererRef"
        :map="map"
        :placemarks="markers"
        :preset="clusterPreset"
        :grid-size="80"
        :min-cluster-size="3"
        :auto-fit="false"
        @cluster-click="onClusterClick"
        @ready="onClustererReady"
      />

      <template v-if="map && !useClustering">
        <YMapsPlacemark
          v-for="marker in markers"
          :key="marker.id"
          :map="map"
          :position="marker.position"
          :preset="marker.preset"
          :draggable="marker.draggable"
          :icon-content="marker.iconContent"
          :balloon-header="marker.balloonHeader"
          :balloon-body="marker.balloonBody"
          :hint-content="marker.hintContent"
          @click="onMarkerClick(marker)"
          @drag-end="onMarkerDragEnd(marker, $event)"
        />
      </template>

      <YMapsBalloon
        v-if="map && balloonData.show"
        v-model="balloonData.show"
        :map="map"
        :position="balloonData.position"
        :header="balloonData.header"
        :content="balloonData.content"
        :footer="balloonData.footer"
        :close-button="true"
        :auto-pan="true"
        @close="onBalloonClose"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Полный пример использования всех компонентов Yandex Maps
 * Демонстрирует возможности всех извлеченных модулей
 */
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
import YMapsCore from '../core/YMapsCore.js'
import YMapsBehaviors from '../behaviors/MapBehaviors.vue'
import YMapsClusterer from '../modules/Clusterer/Clusterer.vue'
import YMapsPlacemark from '../modules/Placemark/Placemark.vue'
import YMapsBalloon from '../modules/Balloon/Balloon.vue'
import Placemark from '../modules/Placemark/Placemark.js'

// Refs
const map = ref<any>(null)
const mapsCore = ref<YMapsCore | null>(null)
const behaviorsRef = ref<any>(null)
const clustererRef = ref<any>(null)
const eventLogRef = ref<HTMLElement>()

// Reactive состояния
const mapSettings = reactive({
  drag: true,
  scrollZoom: true,
  dblClickZoom: true,
  multiTouch: true,
  ruler: false,
  locked: false
})

const markers = ref<any[]>([])
const useClustering = ref(false)
const markerPreset = ref('islands#blueIcon')
const markersAreDraggable = ref(false)
const currentZoom = ref(10)
const mapCenter = ref([55.753994, 37.622093])
const mapState = ref('Инициализация...')
const eventLog = ref<Array<{ time: string; message: string }>>([])

const balloonData = reactive({
  show: false,
  position: [55.753994, 37.622093] as [number, number],
  header: '',
  content: '',
  footer: ''
})

// Computed
const clusterPreset = computed(() => {
  const color = markerPreset.value.match(/islands#(\w+)Icon/)?.[1] || 'blue'
  return `islands#${color}ClusterIcons`
})

// Methods
const initMap = async () => {
  try {
    mapState.value = 'Загрузка API...'
    
    // Создаем ядро системы
    mapsCore.value = new YMapsCore({
      apiKey: '', // Укажите ваш API ключ
      lang: 'ru_RU',
      coordorder: 'latlong',
      debug: true
    })

    // Загружаем API
    await mapsCore.value.loadAPI()
    mapState.value = 'Создание карты...'

    // Создаем карту
    map.value = await mapsCore.value.createMap('yandex-map', {
      center: mapCenter.value,
      zoom: currentZoom.value,
      controls: ['zoomControl', 'fullscreenControl', 'typeSelector']
    })

    mapState.value = 'Готово'
    logEvent('Карта инициализирована')

    // Добавляем обработчики событий карты
    setupMapEventListeners()

    // Добавляем начальные метки
    await nextTick()
    addRandomMarkers(5)
    
  } catch (error) {
    console.error('Ошибка инициализации карты:', error)
    mapState.value = 'Ошибка'
    logEvent(`Ошибка: ${error.message}`, 'error')
  }
}

const setupMapEventListeners = () => {
  if (!map.value) return

  map.value.events.add('boundschange', (e: any) => {
    const newZoom = e.get('newZoom')
    const newCenter = e.get('newCenter')
    
    if (newZoom !== undefined) {
      currentZoom.value = Math.round(newZoom)
    }
    
    if (newCenter) {
      mapCenter.value = newCenter
    }
  })
}

const addRandomMarkers = (count: number) => {
  const newMarkers = []
  const centerLat = mapCenter.value[0]
  const centerLng = mapCenter.value[1]

  for (let i = 0; i < count; i++) {
    const id = `marker-${Date.now()}-${i}`
    const lat = centerLat + (Math.random() - 0.5) * 0.2
    const lng = centerLng + (Math.random() - 0.5) * 0.2
    
    const marker = useClustering.value
      ? new Placemark(
          [lat, lng],
          {
            balloonContent: `Метка ${markers.value.length + i + 1}`,
            hintContent: `Подсказка для метки ${markers.value.length + i + 1}`
          },
          {
            preset: markerPreset.value,
            draggable: markersAreDraggable.value
          }
        )
      : {
          id,
          position: [lat, lng],
          preset: markerPreset.value,
          draggable: markersAreDraggable.value,
          iconContent: (markers.value.length + i + 1).toString(),
          balloonHeader: `Метка ${markers.value.length + i + 1}`,
          balloonBody: `Координаты: ${lat.toFixed(4)}, ${lng.toFixed(4)}`,
          hintContent: `Метка ${markers.value.length + i + 1}`
        }
    
    newMarkers.push(marker)
  }

  markers.value = [...markers.value, ...newMarkers]
  logEvent(`Добавлено ${count} меток`)
}

const clearMarkers = () => {
  const count = markers.value.length
  markers.value = []
  logEvent(`Удалено ${count} меток`)
}

const toggleClustering = () => {
  useClustering.value = !useClustering.value
  
  // Пересоздаем метки в нужном формате
  if (useClustering.value) {
    // Конвертируем в Placemark объекты для кластеризатора
    const placemarkObjects = markers.value.map(m => {
      if (m instanceof Placemark) return m
      
      return new Placemark(
        m.position,
        {
          balloonContent: m.balloonBody,
          hintContent: m.hintContent
        },
        {
          preset: m.preset,
          draggable: m.draggable
        }
      )
    })
    markers.value = placemarkObjects
  } else {
    // Конвертируем обратно в простые объекты
    const simpleMarkers = markers.value.map((m, index) => ({
      id: `marker-${Date.now()}-${index}`,
      position: m.getPosition ? m.getPosition() : m.position,
      preset: markerPreset.value,
      draggable: markersAreDraggable.value,
      iconContent: (index + 1).toString(),
      balloonHeader: `Метка ${index + 1}`,
      balloonBody: `Описание метки ${index + 1}`,
      hintContent: `Метка ${index + 1}`
    }))
    markers.value = simpleMarkers
  }
  
  logEvent(`Кластеризация ${useClustering.value ? 'включена' : 'отключена'}`)
}

const centerMap = async () => {
  if (!map.value) return
  
  await map.value.setCenter([55.753994, 37.622093], 10, {
    duration: 500
  })
  
  logEvent('Карта центрирована')
}

const fitToMarkers = async () => {
  if (!map.value || markers.value.length === 0) return
  
  if (useClustering.value && clustererRef.value) {
    await clustererRef.value.fitToViewport()
  } else {
    // Вычисляем границы вручную
    const positions = markers.value.map(m => 
      m.getPosition ? m.getPosition() : m.position
    )
    
    const bounds = positions.reduce((acc, pos) => {
      if (!acc) {
        return [[pos[0], pos[1]], [pos[0], pos[1]]]
      }
      return [
        [Math.min(acc[0][0], pos[0]), Math.min(acc[0][1], pos[1])],
        [Math.max(acc[1][0], pos[0]), Math.max(acc[1][1], pos[1])]
      ]
    }, null)
    
    if (bounds) {
      await map.value.setBounds(bounds, {
        checkZoomRange: true,
        zoomMargin: 50
      })
    }
  }
  
  logEvent('Карта масштабирована по меткам')
}

const showBalloonExample = () => {
  balloonData.position = mapCenter.value as [number, number]
  balloonData.header = '🎈 Пример Balloon'
  balloonData.content = `
    <div style="padding: 10px;">
      <p><strong>Это демонстрация компонента Balloon</strong></p>
      <p>Текущие координаты: ${mapCenter.value[0].toFixed(4)}, ${mapCenter.value[1].toFixed(4)}</p>
      <p>Текущий зум: ${currentZoom.value}</p>
    </div>
  `
  balloonData.footer = 'Нажмите крестик для закрытия'
  balloonData.show = true
  
  logEvent('Balloon открыт')
}

const animateRandomMarker = async () => {
  if (markers.value.length === 0) return
  
  const randomIndex = Math.floor(Math.random() * markers.value.length)
  const marker = markers.value[randomIndex]
  
  // Анимация для Vue компонентов пока не реализована
  // Но можно изменить позицию
  if (!useClustering.value) {
    const oldPos = marker.position
    marker.position = [
      oldPos[0] + (Math.random() - 0.5) * 0.01,
      oldPos[1] + (Math.random() - 0.5) * 0.01
    ]
  }
  
  logEvent(`Метка ${randomIndex + 1} анимирована`)
}

const formatCoords = (coords: number[]) => {
  if (!coords || coords.length < 2) return 'Н/Д'
  return `${coords[0].toFixed(4)}, ${coords[1].toFixed(4)}`
}

const logEvent = (message: string, type: string = 'info') => {
  const time = new Date().toLocaleTimeString('ru-RU')
  eventLog.value.unshift({ time, message })
  
  // Ограничиваем лог 20 записями
  if (eventLog.value.length > 20) {
    eventLog.value = eventLog.value.slice(0, 20)
  }
  
  // Прокручиваем к началу
  if (eventLogRef.value) {
    eventLogRef.value.scrollTop = 0
  }
}

// Event handlers
const onDragStart = () => {
  mapState.value = 'Перетаскивание...'
  logEvent('Начало перетаскивания карты')
}

const onDragEnd = () => {
  mapState.value = 'Готово'
  logEvent('Конец перетаскивания карты')
}

const onZoomChange = () => {
  logEvent(`Изменение зума: ${currentZoom.value}`)
}

const onBehaviorsReady = () => {
  logEvent('Менеджер поведений готов')
}

const onClustererReady = () => {
  logEvent('Кластеризатор готов')
}

const onMarkerClick = (marker: any) => {
  logEvent(`Клик по метке: ${marker.balloonHeader}`)
}

const onMarkerDragEnd = (marker: any, event: any) => {
  logEvent(`Метка перемещена: ${marker.balloonHeader}`)
}

const onClusterClick = (cluster: any) => {
  logEvent(`Клик по кластеру`)
}

const onBalloonClose = () => {
  logEvent('Balloon закрыт')
}

// Lifecycle
onMounted(() => {
  initMap()
})

onUnmounted(() => {
  if (mapsCore.value) {
    mapsCore.value.destroy()
  }
})
</script>

<style scoped>
.map-example-container {
  display: flex;
  height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.control-panel {
  width: 400px;
  background: #f8f9fa;
  border-right: 1px solid #dee2e6;
  padding: 20px;
  overflow-y: auto;
}

.control-panel h2 {
  margin: 0 0 20px 0;
  font-size: 1.5rem;
  color: #212529;
}

.control-section {
  background: white;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.control-section h3 {
  margin: 0 0 15px 0;
  font-size: 1.1rem;
  color: #495057;
  font-weight: 600;
}

.controls-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.controls-grid label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 0.9rem;
}

.controls-grid input[type="checkbox"] {
  margin-right: 8px;
}

.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover {
  background: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.btn-info {
  background: #17a2b8;
  color: white;
}

.btn-info:hover {
  background: #138496;
}

.form-select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 0.9rem;
}

.checkbox-label input {
  margin-right: 8px;
}

.info-text {
  margin: 10px 0 0 0;
  font-size: 0.9rem;
  color: #6c757d;
}

.info-text strong {
  color: #212529;
}

.event-log {
  max-height: 150px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  background: #fff;
  padding: 10px;
}

.event-item {
  display: flex;
  gap: 10px;
  padding: 5px 0;
  border-bottom: 1px solid #f1f3f5;
  font-size: 0.85rem;
}

.event-item:last-child {
  border-bottom: none;
}

.event-time {
  color: #868e96;
  font-weight: 500;
  flex-shrink: 0;
}

.event-message {
  color: #495057;
  word-break: break-word;
}

.event-empty {
  color: #adb5bd;
  font-style: italic;
  text-align: center;
  padding: 20px;
}

.stats-grid {
  display: grid;
  gap: 10px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  padding: 8px;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 0.9rem;
}

.stat-label {
  color: #6c757d;
}

.stat-value {
  color: #212529;
  font-weight: 500;
}

.map-wrapper {
  flex: 1;
  position: relative;
}

.map-container {
  width: 100%;
  height: 100%;
}

/* Адаптивность */
@media (max-width: 768px) {
  .map-example-container {
    flex-direction: column;
  }
  
  .control-panel {
    width: 100%;
    height: 40vh;
    border-right: none;
    border-bottom: 1px solid #dee2e6;
  }
  
  .map-wrapper {
    height: 60vh;
  }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .control-panel {
    background: #212529;
    border-color: #495057;
  }
  
  .control-panel h2 {
    color: #f8f9fa;
  }
  
  .control-section {
    background: #343a40;
  }
  
  .control-section h3 {
    color: #adb5bd;
  }
  
  .info-text {
    color: #adb5bd;
  }
  
  .info-text strong {
    color: #f8f9fa;
  }
  
  .event-log {
    background: #343a40;
    border-color: #495057;
  }
  
  .event-item {
    border-color: #495057;
  }
  
  .event-message {
    color: #f8f9fa;
  }
  
  .stat-item {
    background: #495057;
  }
  
  .stat-label {
    color: #adb5bd;
  }
  
  .stat-value {
    color: #f8f9fa;
  }
}
</style>