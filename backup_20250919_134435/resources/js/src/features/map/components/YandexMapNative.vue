<template>
  <div class="yandex-map-native">
    <!-- Контейнер поиска (если включен по режиму) -->
    <div v-if="modeSettings.showSearch" class="map-search-container">
      <div ref="searchControlRef" class="search-control-wrapper"></div>
    </div>

    <!-- Контейнер карты -->
    <div 
      :id="containerId" 
      class="map-container"
      :style="{ width, height: `${height}px` }"
    >
      <!-- Индикатор загрузки -->
      <div v-if="isLoading" class="map-loading">
        <div class="loading-spinner"></div>
        <p>Загрузка карты...</p>
      </div>

      <!-- Сообщение об ошибке -->
      <div v-if="mapError" class="map-error">
        <div class="error-icon">⚠️</div>
        <p>{{ mapError }}</p>
        <button @click="retry" class="retry-button">Повторить</button>
      </div>

      <!-- Центральный маркер (только для режима address-picker) -->
      <div v-if="!isLoading && !mapError && modeSettings.showCenterMarker" class="center-marker">
        <div class="marker-pin"></div>
        <div class="marker-shadow"></div>
      </div>
    </div>

    <!-- Информация о выбранном адресе -->
    <div v-if="showLocationInfo && selectedLocation" class="location-info">
      <h4>📍 Выбранный адрес:</h4>
      <p>{{ selectedLocation.address }}</p>
      <p class="coordinates">
        Координаты: {{ selectedLocation.coordinates[0].toFixed(4) }}, {{ selectedLocation.coordinates[1].toFixed(4) }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, shallowRef, markRaw } from 'vue'
// МАКСИМАЛЬНОЕ УПРОЩЕНИЕ: НЕ используем YMapsCore и SearchControl
// Работаем ПРЯМО с window.ymaps как в "Карта феи"
import { Master } from '@/src/entities/master/model/types'
import { 
  getMasterCoordinates,
  hasMasterCoordinates,
  getMastersWithCoordinates 
} from '@/src/shared/utils/geoHelpers'

// Интерфейсы
type MapMode = 'masters-catalog' | 'address-picker' | 'ad-display'

interface Location {
  address: string
  coordinates: [number, number]
  city?: string
  district?: string
}

interface Props {
  // Режим работы карты
  mode?: MapMode
  
  // Базовые настройки карты
  masters?: Master[]
  adLocation?: [number, number] // Для режима ad-display
  height?: number
  width?: string
  center?: [number, number]
  zoom?: number
  
  // Функциональность (теперь зависит от режима)
  showSearch?: boolean
  showLocationInfo?: boolean
  showControls?: boolean
  enableMarkers?: boolean
  
  // API ключ (опционально)
  apiKey?: string
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'masters-catalog',
  masters: () => [],
  height: 400,
  width: '100%',
  center: () => [55.7558, 37.6173], // Москва
  zoom: 10,
  showSearch: true,
  showLocationInfo: false,
  showControls: true,
  enableMarkers: true,
  apiKey: '23ff8acc-835f-4e99-8b19-d33c5d346e18'
})

const emit = defineEmits<{
  ready: [map: any]
  addressSelect: [location: Location]
  markerClick: [master: Master]
  mapClick: [event: any]
  error: [error: string]
  centerAddressChange: [data: { address: string; coordinates: [number, number] }]
}>()

// Упрощенные реактивные переменные (как "Карта феи")
const containerId = ref(`yandex-map-${Date.now()}`)
const isLoading = ref(true)
const mapError = ref<string | null>(null)
// ОПТИМИЗАЦИЯ: shallowRef для карты (избегаем глубокой реактивности)
const mapInstance = shallowRef<any>(null)
// УБИРАЕМ сложные обертки
const searchControlRef = ref<HTMLElement>()

const selectedLocation = ref<Location | null>(null)
const masterMarkers = shallowRef<any[]>([])

// Вычисляемые свойства
const filteredMasters = computed(() => {
  return getMastersWithCoordinates(props.masters)
})

// Настройки по режимам работы
const modeSettings = computed(() => {
  switch (props.mode) {
    case 'masters-catalog':
      return {
        showCenterMarker: false,
        showMasterMarkers: true,
        showSearch: props.showSearch,
        enableGeocode: false
      }
    case 'address-picker':
      return {
        showCenterMarker: true,
        showMasterMarkers: false,
        showSearch: props.showSearch,
        enableGeocode: true
      }
    case 'ad-display':
      return {
        showCenterMarker: false,
        showMasterMarkers: false,
        showSearch: false,
        enableGeocode: false
      }
    default:
      return {
        showCenterMarker: false,
        showMasterMarkers: true,
        showSearch: props.showSearch,
        enableGeocode: false
      }
  }
})

// Функция debounce для оптимизации запросов к API
const debounce = (func: Function, wait: number) => {
  let timeout: ReturnType<typeof setTimeout>
  return function executedFunction(...args: any[]) {
    const later = () => {
      console.log('⏰ [YandexMapNative] Debounce завершен, выполняю функцию через', wait, 'мс')
      clearTimeout(timeout)
      func(...args)
    }
    
    if (timeout) {
      console.log('⏰ [YandexMapNative] Сбрасываю предыдущий таймер debounce')
      clearTimeout(timeout)
    }
    
    console.log('⏰ [YandexMapNative] Запускаю debounce таймер на', wait, 'мс')
    timeout = setTimeout(later, wait)
  }
}

// МАКСИМАЛЬНО УПРОЩЕННАЯ ИНИЦИАЛИЗАЦИЯ (как "Карта феи")
const initMap = async () => {
  try {
    isLoading.value = true
    mapError.value = null

    console.log('🎯 [YandexMapNative] ПРОСТАЯ ИНИЦИАЛИЗАЦИЯ как "Карта феи"')

    // ПРЯМОЙ доступ к window.ymaps (API уже загружен через CDN)
    const ymaps = (window as any).ymaps
    
    if (!ymaps) {
      throw new Error('Yandex Maps API не загружен. Проверьте подключение CDN в app.blade.php')
    }

    // Ждем готовности API (как в "Карта феи")
    await new Promise<void>((resolve) => {
      if (ymaps.ready) {
        ymaps.ready(() => {
          console.log('✅ [YandexMapNative] Yandex Maps API готов')
          resolve()
        })
      } else {
        resolve() // API уже готов
      }
    })

    // ПРЯМОЕ СОЗДАНИЕ КАРТЫ как в "Карта феи" - БЕЗ ОБЕРТОК!
    console.log('🔄 [YandexMapNative] Создаю карту НАПРЯМУЮ (как "Карта феи"):', props.center)
    const map = new ymaps.Map(containerId.value, {
      center: props.center,
      zoom: props.zoom,
      controls: props.showControls ? ['zoomControl', 'fullscreenControl'] : []
    })

    // markRaw чтобы Vue не делал карту реактивной (ОПТИМИЗАЦИЯ)
    mapInstance.value = markRaw(map)
    console.log('✅ [YandexMapNative] Карта создана напрямую, обернута в markRaw()')

    // Инициализируем поиск (зависит от режима)
    if (modeSettings.value.showSearch) {
      await initSimpleSearchControl()
    }

    // Добавляем метки мастеров (только для masters-catalog)
    if (modeSettings.value.showMasterMarkers && filteredMasters.value.length > 0) {
      await addMasterMarkers()
    }

    // Добавляем маркер объявления (только для ad-display)
    if (props.mode === 'ad-display' && props.adLocation) {
      await addAdMarker(props.adLocation)
    }

    // Настраиваем МИНИМАЛЬНЫЕ события карты
    setupMinimalMapEvents()

    isLoading.value = false
    console.log('🎉 [YandexMapNative] Карта инициализирована ПРОСТО как "Карта феи"')
    emit('ready', mapInstance.value)

  } catch (error) {
    console.error('❌ [YandexMapNative] Ошибка простой инициализации:', error)
    mapError.value = error instanceof Error ? error.message : 'Неизвестная ошибка'
    isLoading.value = false
    emit('error', mapError.value)
  }
}

// ПРОСТОЙ ПОИСК как в "Карта феи" - БЕЗ ОБЕРТОК!
const initSimpleSearchControl = async () => {
  if (!mapInstance.value) return

  try {
    console.log('🔍 [YandexMapNative] Добавляю ПРОСТОЙ поиск (как "Карта феи")')
    
    const ymaps = (window as any).ymaps
    if (!ymaps) return

    // СОЗДАЕМ ПОИСК НАПРЯМУЮ через Yandex API
    const searchControl = new ymaps.control.SearchControl({
      options: {
        provider: 'yandex#search',
        placeholderContent: 'Введите адрес...',
        noPlacemark: true, // Не добавляем маркеры результатов
        resultsPerPage: 5
      }
    })
    
    // Добавляем на карту ПРОСТО
    mapInstance.value.controls.add(searchControl, {
      position: { top: 10, left: 10 }
    })
    
    // Простой обработчик результатов поиска
    searchControl.events.add('resultselect', (e: any) => {
      const result = e.get('target').getResult(e.get('index'))
      if (result) {
        const coords = result.geometry.getCoordinates()
        const address = result.getAddressLine()
        
        selectedLocation.value = {
          address: address,
          coordinates: coords
        }
        
        console.log('✅ [YandexMapNative] Простой поиск: адрес выбран, карта НЕ движется')
        emit('addressSelect', selectedLocation.value)
      }
    })
    
    console.log('✅ [YandexMapNative] Простой поиск добавлен')
    
  } catch (error) {
    console.error('❌ [YandexMapNative] Ошибка создания простого поиска:', error)
  }
}

const addMasterMarkers = async () => {
  if (!mapInstance.value) return

  try {
    console.log('🗺️ [YandexMapNative] Простое добавление меток (как "Карта феи")')
    
    // Проверяем активность карты (упрощенная проверка)
    if (!mapInstance.value || mapInstance.value.isDestroyed?.()) {
      console.debug('⚠️ [YandexMapNative] Карта неактивна или уничтожена')
      return
    }
    
    // Очищаем существующие метки (ПРОСТОЙ способ)
    if (masterMarkers.value.length > 0) {
      console.log('🧹 [YandexMapNative] Очищаю существующие метки...')
      masterMarkers.value.forEach(marker => {
        try {
          if (marker && mapInstance.value && mapInstance.value.geoObjects) {
            mapInstance.value.geoObjects.remove(marker)
          }
        } catch (error) {
          console.warn('⚠️ [YandexMapNative] Ошибка удаления метки:', error)
        }
      })
      masterMarkers.value = []
    }

    // Получаем глобальный объект ymaps
    const ymaps = (window as any).ymaps
    if (!ymaps) {
      console.error('❌ [YandexMapNative] window.ymaps не найден для создания меток!')
      return
    }

    // Создаем новые метки ПРОСТЫМ способом (как в "Карта феи")
    console.log(`📍 [YandexMapNative] Создаю ${filteredMasters.value.length} меток...`)
    
    for (const master of filteredMasters.value) {
      const coordinates = getMasterCoordinates(master)
      if (!coordinates) {
        console.debug('⚠️ [YandexMapNative] Пропускаю мастера без координат:', master.name)
        continue
      }
      
      // НАТИВНОЕ создание метки как в "Карта феи"
      const placemark = new ymaps.Placemark(coordinates, {
        balloonContentHeader: master.name,
        balloonContentBody: `
          <div class="master-balloon">
            ${master.description ? `<p>${master.description}</p>` : ''}
            ${master.photo ? `<img src="${master.photo}" alt="${master.name}" style="max-width: 200px; height: auto;">` : ''}
          </div>
        `,
        hintContent: master.name
      }, {
        preset: 'islands#blueIcon'
      })

      // Обработчик клика по метке
      placemark.events.add('click', () => {
        console.log('🖱️ [YandexMapNative] Клик по метке мастера:', master.name)
        emit('markerClick', master)
      })

      // Добавляем на карту ПРОСТЫМ способом
      mapInstance.value.geoObjects.add(placemark)
      masterMarkers.value.push(placemark)
      
      console.log(`✅ [YandexMapNative] Метка добавлена: ${master.name}`)
    }
    
    console.log(`✅ [YandexMapNative] Все метки добавлены. Всего: ${masterMarkers.value.length}`)
    
  } catch (error) {
    console.error('❌ [YandexMapNative] Ошибка добавления меток:', error)
  }
}

const addAdMarker = async (coordinates: [number, number]) => {
  if (!mapInstance.value) return

  try {
    console.log('📍 [YandexMapNative] Простое добавление маркера объявления (как "Карта феи")')
    
    // Проверяем активность карты
    if (mapsCore.value._isMapActive && !mapsCore.value._isMapActive(mapInstance.value)) {
      console.debug('⚠️ [YandexMapNative] Карта неактивна - пропускаю добавление маркера')
      return
    }
    
    // Получаем глобальный объект ymaps
    const ymaps = (window as any).ymaps
    if (!ymaps) {
      console.error('❌ [YandexMapNative] window.ymaps не найден для создания маркера!')
      return
    }

    // НАТИВНОЕ создание маркера как в "Карта феи"
    const placemark = new ymaps.Placemark(coordinates, {
      balloonContentHeader: '📍 Местоположение услуги',
      balloonContentBody: `
        <div class="ad-balloon">
          <p>Здесь будет оказана услуга</p>
        </div>
      `,
      hintContent: 'Местоположение услуги'
    }, {
      preset: 'islands#redIcon'
    })

    // Добавляем на карту ПРОСТЫМ способом
    mapInstance.value.geoObjects.add(placemark)
    
    // НЕ центрируем на маркере автоматически - как в "Карта феи"  
    // Маркер добавлен, но карта остается под контролем пользователя
    console.log('✅ [YandexMapNative] Маркер добавлен, но карта НЕ центрируется (как "Карта феи")')
    
  } catch (error) {
    console.error('❌ [YandexMapNative] Ошибка добавления маркера объявления:', error)
  }
}

// Функция обратного геокодинга
// ПРОСТОЕ геокодирование как в "Карта феи" - без сложных проверок!
const performSimpleGeocode = async (center: [number, number]) => {
  if (!mapInstance.value) return // Простая проверка
  
  try {
    const ymaps = (window as any).ymaps
    if (!ymaps || !ymaps.geocode) return

    console.log('🔍 [YandexMapNative] Простое геокодирование:', center)
    
    const result = await ymaps.geocode(center, { 
      kind: 'house',
      results: 1 
    })
    
    const geoObject = result.geoObjects.get(0)
    if (geoObject && mapInstance.value) { // Проверяем что карта еще существует
      const address = geoObject.properties.get('text')
      console.log('✅ [YandexMapNative] Нашел адрес:', address)
      
      emit('centerAddressChange', {
        address,
        coordinates: center
      })
    }
  } catch (error) {
    console.warn('⚠️ [YandexMapNative] Ошибка простого геокодирования:', error)
  }
}

// ПРОСТОЙ debounce (500мс для плавности)
const debouncedSimpleGeocode = debounce(performSimpleGeocode, 500)

// МИНИМАЛЬНЫЕ события как в "Карта феи" - только необходимое!
const setupMinimalMapEvents = () => {
  if (!mapInstance.value) return

  console.log('🎯 [YandexMapNative] Настройка МИНИМАЛЬНЫХ событий (как "Карта феи")')

  // ПРОСТОЙ клик по карте (без проверок)
  mapInstance.value.events.add('click', (e: any) => {
    const coords = e.get('coords')
    console.log('🖱️ [YandexMapNative] Клик по карте:', coords)
    emit('mapClick', { coordinates: coords })
  })

  // ПРОСТОЕ геокодирование (только для address-picker)
  if (modeSettings.value.enableGeocode) {
    console.log('🔍 [YandexMapNative] Подключаю ПРОСТОЕ геокодирование')
    
    mapInstance.value.events.add('boundschange', (e: any) => {
      if (!mapInstance.value) return // Простая проверка
      
      const newCenter = mapInstance.value.getCenter()
      console.log('🔄 [YandexMapNative] Центр изменился:', newCenter)
      
      // ПРОСТОЕ debounced геокодирование
      debouncedSimpleGeocode(newCenter)
    })
  } else {
    console.log('ℹ️ [YandexMapNative] Геокодирование отключено для режима:', props.mode)
  }
  
  console.log('✅ [YandexMapNative] Минимальные события настроены')
}

const setCenter = (coordinates: [number, number], zoom?: number, force: boolean = false) => {
  if (mapInstance.value && force) {
    // Центрирование ТОЛЬКО при явном force=true
    console.log('🎯 [YandexMapNative] Принудительное центрирование (force=true):', coordinates)
    mapInstance.value.setCenter(coordinates, zoom || props.zoom)
  } else {
    // По умолчанию НЕ центрируем - как в "Карта феи"
    console.log('ℹ️ [YandexMapNative] setCenter вызван без force - карта не движется (как "Карта феи")')
  }
}

const addMarker = (coordinates: [number, number], properties = {}, options = {}) => {
  if (!mapInstance.value) return null

  try {
    // Проверяем активность карты
    if (mapsCore.value._isMapActive && !mapsCore.value._isMapActive(mapInstance.value)) {
      console.debug('⚠️ [YandexMapNative] Карта неактивна - не добавляю маркер')
      return null
    }
    
    // Получаем глобальный объект ymaps
    const ymaps = (window as any).ymaps
    if (!ymaps) {
      console.error('❌ [YandexMapNative] window.ymaps не найден для создания маркера!')
      return null
    }

    // НАТИВНОЕ создание маркера как в "Карта феи"
    const placemark = new ymaps.Placemark(coordinates, properties, {
      preset: 'islands#blueIcon',
      ...options
    })
    
    // Добавляем на карту ПРОСТЫМ способом
    mapInstance.value.geoObjects.add(placemark)
    console.log('✅ [YandexMapNative] Простой маркер добавлен')
    
    return placemark
    
  } catch (error) {
    console.error('❌ [YandexMapNative] Ошибка добавления простого маркера:', error)
    return null
  }
}

const clearSelectedLocation = () => {
  selectedLocation.value = null
  // Поисковый контрол теперь нативный, очистка не требуется
  console.log('✅ [YandexMapNative] Местоположение очищено')
}

const getUserLocation = async (): Promise<[number, number] | null> => {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      resolve(null)
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve([position.coords.latitude, position.coords.longitude])
      },
      () => {
        resolve(null)
      },
      { timeout: 10000, enableHighAccuracy: true }
    )
  })
}

const retry = () => {
  mapError.value = null
  initMap()
}

// Lifecycle
onMounted(async () => {
  await nextTick()
  initMap()
})

// ПРОСТАЯ очистка как в "Карта феи" - минимум операций!
onUnmounted(() => {
  try {
    console.log('🧹 [YandexMapNative] Простая очистка (как "Карта феи")')
    
    // ПРОСТО: очищаем маркеры если карта существует
    if (mapInstance.value && masterMarkers.value.length > 0) {
      console.log('🧹 [YandexMapNative] Очищаю маркеры...')
      try {
        mapInstance.value.geoObjects.removeAll()
      } catch (error) {
        console.warn('⚠️ [YandexMapNative] Ошибка очистки маркеров:', error)
      }
      masterMarkers.value = []
    }
    
    // ПРОСТО: уничтожаем карту если она существует
    if (mapInstance.value) {
      console.log('🧹 [YandexMapNative] Простое уничтожение карты...')
      try {
        if (typeof mapInstance.value.destroy === 'function') {
          mapInstance.value.destroy()
        }
      } catch (error) {
        console.warn('⚠️ [YandexMapNative] Ошибка destroy:', error)
      }
      mapInstance.value = null
    }
    
    console.log('✅ [YandexMapNative] Простая очистка завершена')
    
  } catch (error) {
    console.error('❌ [YandexMapNative] Ошибка простой очистки:', error)
  }
})

// ПРОСТОЕ expose (как "Карта феи")
defineExpose({
  map: mapInstance,
  setCenter,
  addMarker,
  clearSelectedLocation,
  getUserLocation
  // Нет сложных оберток - все просто!
})
</script>

<style scoped>
.yandex-map-native {
  position: relative;
  width: 100%;
}

.map-search-container {
  position: relative;
  z-index: 10;
  margin-bottom: 8px;
}

.search-control-wrapper {
  width: 100%;
}

.map-container {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background-color: #f5f5f5;
}

/* Центральный маркер */
.center-marker {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -100%);
  z-index: 100;
  pointer-events: none;
}

.marker-pin {
  width: 24px;
  height: 24px;
  background: #ff5722;
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  border: 2px solid #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  position: relative;
}

.marker-pin::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 6px;
  height: 6px;
  background: #fff;
  border-radius: 50%;
  transform: translate(-50%, -50%) rotate(45deg);
}

.marker-shadow {
  position: absolute;
  top: 22px;
  left: 50%;
  width: 16px;
  height: 8px;
  background: rgba(0,0,0,0.2);
  border-radius: 50%;
  transform: translateX(-50%);
  animation: pulse-shadow 2s infinite;
}

@keyframes pulse-shadow {
  0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.3; }
  50% { transform: translateX(-50%) scale(1.2); opacity: 0.1; }
}

/* Стили загрузки */
.map-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  background: #f8f9fa;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Стили ошибки */
.map-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  background: #fff3cd;
  color: #856404;
  padding: 20px;
  text-align: center;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.retry-button {
  margin-top: 16px;
  padding: 8px 16px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.retry-button:hover {
  background: #0056b3;
}

/* Информация о локации */
.location-info {
  margin-top: 12px;
  padding: 12px;
  background: #e7f3ff;
  border: 1px solid #b6d7ff;
  border-radius: 6px;
}

.location-info h4 {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #0366d6;
}

.location-info p {
  margin: 0 0 4px 0;
  font-size: 13px;
  color: #586069;
}

.coordinates {
  font-family: monospace;
  font-size: 12px !important;
  color: #6a737d !important;
}

/* Адаптивность */
@media (max-width: 768px) {
  .map-container {
    border-radius: 4px;
  }
  
  .map-search-container {
    margin-bottom: 4px;
  }
}
</style>