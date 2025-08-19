<template>
  <div class="w-full rounded-lg overflow-hidden border border-gray-200">
    <!-- Debug info (только в development) -->
    <div v-if="false" style="font-size: 10px; color: #999; margin-bottom: 4px; display: flex; gap: 8px; align-items: center;">
      MapID: {{ mapId }} | Loading: {{ loading }}
      <button 
        @click="safeInitMap" 
        style="background: #ccc; border: 1px solid #999; padding: 2px 6px; font-size: 10px;"
      >
        Force Init
      </button>
    </div>
    
    <!-- Для режима с маркерами используем iframe -->
    <iframe 
      v-if="multiple && markers && markers.length > 0"
      :src="`/map-iframe.html?v=${Date.now()}`"
      class="w-full border-none rounded-lg"
      :style="{ height: height + 'px' }"
      frameborder="0"
      @load="onIframeLoad"
    />
    
    <!-- Для режима выбора точки используем обычную карту -->
    <div 
      v-else
      ref="mapContainer"
      :id="mapId" 
      class="w-full min-w-[200px] bg-gray-100 relative block"
      :style="{ height: height + 'px' }"
    >
      <!-- Загрузка -->
      <div 
        v-if="loading" 
        class="absolute inset-0 bg-white/90 z-10"
      >
        <div class="flex flex-col items-center justify-center h-full">
          <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-sm text-gray-600">Загрузка карты...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// Интерфейс для маркера на карте
export interface MapMarker {
  id: string | number
  lat: number
  lng: number
  title?: string
  description?: string
  icon?: string
  data?: any // Дополнительные данные (например, мастер)
}

interface Props {
  modelValue?: string // "lat,lng"
  height?: number
  center?: { lat: number; lng: number }
  zoom?: number
  apiKey?: string
  // Новые props для множественных меток
  multiple?: boolean // Режим множественных меток
  markers?: MapMarker[] // Массив маркеров для отображения
  clusterize?: boolean // Включить кластеризацию
  showSingleMarker?: boolean // Показывать одиночный маркер в режиме выбора
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  height: 360,
  center: () => ({ lat: 58.0105, lng: 56.2502 }), // Пермь
  zoom: 12,
  apiKey: '1220b4af-ae8e-4506-ab3a-c6329234066f', // Enterprise ключ из Avito
  multiple: false,
  markers: () => [],
  clusterize: false, // Отключено по умолчанию (KISS)
  showSingleMarker: true
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'address-found': [address: string, coordinates: { lat: number; lng: number }]
  'search-error': [error: string]
  'marker-moved': [coordinates: { lat: number; lng: number }]
  // Новые события для множественных меток
  'marker-click': [marker: MapMarker]
  'cluster-click': [markers: MapMarker[]]
  'bounds-change': [bounds: any]
}>()

// Состояние
const loading = ref(true)
const mapId = useId('yandex-map')
const map = ref<any>(null)
const placemark = ref<any>(null)
const mapContainer = ref<HTMLElement>()
// Новые переменные для множественных меток
const clusterer = ref<any>(null)
const placemarks = ref<any[]>([]) // Массив всех меток на карте

// Логируем ID для отладки (только в development)
if (import.meta.env.DEV) {
  // Debug log removed
}

// Парсинг координат из modelValue
const parseCoordinates = (value: string) => {
  if (value && value.includes(',')) {
    const [lat, lng] = value.split(',').map(Number)
    if (!isNaN(lat) && !isNaN(lng)) {
      return { lat, lng }
    }
  }
  return props.center
}

// Текущие координаты с проверкой
const currentCoords = ref(parseCoordinates(props.modelValue))
// Debug log removed

// Загрузка Яндекс.Карт API (упрощенный подход как в Авито)
const loadYandexMapsAPI = () => {
  // Проверяем, загружен ли уже API
  if (window.ymaps) {
    // Debug log removed
    return
  }
  
  // Проверяем, не загружается ли уже скрипт
  const existingScript = document.querySelector('script[src*="api-maps.yandex.ru"]')
  if (existingScript) {
    // Debug log removed
    return
  }

  // Создаем скрипт
  const script = document.createElement('script')
  script.src = `https://api-maps.yandex.ru/2.1/?apikey=${props.apiKey}&lang=ru_RU`
  script.async = true
  document.head.appendChild(script)
}

// Инициализация карты (упрощенный подход как в Авито)
const initMap = () => {
  if (!window.ymaps) {
    // Debug log removed
    return
  }
  
  // Debug log removed
  
  try {
    // Проверяем что элемент существует
    const mapElement = document.getElementById(mapId)
    if (!mapElement) {
      // Debug log removed
      loading.value = false
      return
    }
    
    // Создаем карту как в примере Авито
    map.value = new window.ymaps.Map(mapId, {
      center: [currentCoords.value.lat, currentCoords.value.lng],
      zoom: props.zoom,
      controls: ['zoomControl', 'searchControl'] // Базовые контролы
    })
    
    // Debug log removed

    // Автоопределение города пользователя по IP
    if (!props.modelValue) {
      window.ymaps.geolocation.get({
        provider: 'yandex'
      }).then((result: any) => {
        const coords = result.geoObjects.get(0).geometry.getCoordinates()
        if (coords && coords.length === 2) {
          map.value.setCenter(coords)
          currentCoords.value = { lat: coords[0], lng: coords[1] }
          // Debug log removed
        }
      }).catch((error: any) => {
        // Debug log removed
      })
    }

    // ВРЕМЕННО ПОЛНОСТЬЮ ОТКЛЮЧАЕМ МАРКЕРЫ
    // Проблема с несовместимостью Yandex Maps API и Vue реактивности
    if (props.multiple && props.markers && props.markers.length > 0) {
      // Debug log removed
      // Debug log removed
      
      // Выводим список мастеров в консоль
      props.markers.forEach((marker, index) => {
        if (marker && marker.lat && marker.lng) {
          // Debug log removed}, ${marker.lng.toFixed(4)}]`)
        }
      })
      
      // Центрируем карту на районе с мастерами
      if (props.markers.length > 0 && props.markers[0].lat && props.markers[0].lng) {
        setTimeout(() => {
          if (map.value) {
            // Центр Перми с учетом координат мастеров
            map.value.setCenter([58.0105, 56.2502], 12)
            // Debug log removed
          }
        }, 1000)
      }
    }
    
    loading.value = false
    // Debug log removed
    
  } catch (error) {
    console.error('Ошибка инициализации карты:', error)
    loading.value = false
  }
}

// Поиск адреса
const searchAddress = async (address: string): Promise<void> => {
  // Debug log removed
  
  if (!window.ymaps) {
    console.error('ymaps не доступен для поиска')
    emit('search-error', 'Карты не готовы')
    return
  }
  
  if (!address) {
    console.error('Адрес пустой')
    return
  }

  try {
    // Добавляем префикс города если его нет
    let searchQuery = address
    if (searchQuery.toLowerCase().indexOf('пермь') === -1) {
      searchQuery = `Пермь, ${address}`
    }

    // Debug log removed
    
    const result = await window.ymaps.geocode(searchQuery, { results: 1 })
    // Debug log removed
    
    const firstGeoObject = result.geoObjects.get(0)
    
    if (!firstGeoObject) {
      // Debug log removed
      emit('search-error', 'Адрес не найден')
      return
    }

    const coords = firstGeoObject.geometry.getCoordinates()
    const newCoords = { lat: coords[0], lng: coords[1] }
    
    // Debug log removed
    
    // Обновляем позицию карты и маркера
    if (map.value && placemark.value) {
      map.value.panTo(coords)
      placemark.value.geometry.setCoordinates(coords)
    }
    
    currentCoords.value = newCoords
    updateModelValue(coords.join(','))
    
    // Получаем точный адрес
    const geoAddress = firstGeoObject.getAddressLine()
    // Debug log removed
    emit('address-found', geoAddress, newCoords)
    
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
    emit('search-error', 'Ошибка поиска адреса')
  }
}

// Обновление modelValue
const updateModelValue = (value: string) => {
  emit('update:modelValue', value)
}

// Установка координат извне
const setCoordinates = (coords: { lat: number; lng: number }) => {
  if (!map.value || !placemark.value) return
  
  const ymapsCoords = [coords.lat, coords.lng]
  
  map.value.panTo(ymapsCoords)
  placemark.value.geometry.setCoordinates(ymapsCoords)
  
  currentCoords.value = coords
  updateModelValue(ymapsCoords.join(','))
}

// Следим за изменениями modelValue
watch(() => props.modelValue, (newValue) => {
  const coords = parseCoordinates(newValue)
  if (coords.lat !== currentCoords.value.lat || coords.lng !== currentCoords.value.lng) {
    setCoordinates(coords)
  }
})

// ВРЕМЕННО ОТКЛЮЧЕНО: следим за изменениями маркеров для обновления карты
// watch(() => props.markers, (newMarkers) => {
//   if (props.multiple && map.value) {
//     updateMarkers(newMarkers)
//   }
// }, { deep: true })

// Метод для обновления маркеров на карте (KISS - без кластеризации)
const updateMarkers = (newMarkers: MapMarker[]) => {
  if (!map.value || !window.ymaps) return
  
  // Debug log removed
  
  // Очищаем старые метки
  placemarks.value.forEach(placemark => {
    map.value.geoObjects.remove(placemark)
  })
  placemarks.value = []
  
  // Фильтруем валидные маркеры
  const validMarkers = newMarkers.filter(marker => 
    marker && 
    typeof marker.lat === 'number' && 
    typeof marker.lng === 'number' && 
    !isNaN(marker.lat) && 
    !isNaN(marker.lng) &&
    marker.lat >= -90 && marker.lat <= 90 &&
    marker.lng >= -180 && marker.lng <= 180
  )
  
  // Добавляем новые метки
  validMarkers.forEach(marker => {
    const placemark = new window.ymaps.Placemark(
      [marker.lat, marker.lng],
      {
        balloonContentHeader: marker.title || '',
        balloonContentBody: marker.description || '',
        markerData: marker
      },
      {
        preset: marker.icon || 'islands#blueIcon',
        draggable: false
      }
    )
    
    placemark.events.add('click', () => {
      emit('marker-click', marker)
    })
    
    // Сразу добавляем на карту
    map.value.geoObjects.add(placemark)
    placemarks.value.push(placemark)
  })
  
  // Центрируем карту
  if (validMarkers.length === 1) {
    map.value.setCenter([validMarkers[0].lat, validMarkers[0].lng], props.zoom)
  } else if (validMarkers.length > 1) {
    // Используем центр карты с меньшим зумом для нескольких маркеров
    map.value.setCenter([props.center.lat, props.center.lng], props.zoom - 2)
  }
}

// Метод для обновления центра карты
const updateCenter = (center: { lat: number; lng: number }, zoom?: number) => {
  if (!map.value) return
  
  map.value.setCenter([center.lat, center.lng], zoom || map.value.getZoom())
}

// Метод для получения текущих границ карты
const getBounds = () => {
  if (!map.value) return null
  
  const bounds = map.value.getBounds()
  if (bounds) {
    return {
      northEast: { lat: bounds[1][0], lng: bounds[1][1] },
      southWest: { lat: bounds[0][0], lng: bounds[0][1] }
    }
  }
  return null
}

// Обработчик загрузки iframe
const onIframeLoad = () => {
  // Debug log removed
  loading.value = false
  
  // Не отправляем данные в iframe - он сам загрузит их через API
}

// Метод для принудительной инициализации
const forceInit = async () => {
  if (!map.value) {
    await safeInitMap()
  }
}

// Экспорт методов для родительского компонента
defineExpose({
  searchAddress,
  setCoordinates,
  forceInit,
  updateMarkers,
  updateCenter,
  getBounds
})

// Функция проверки видимости контейнера
const waitForVisible = async (): Promise<void> => {
  return new Promise((resolve) => {
    let attempts = 0
    const maxAttempts = 50 // 5 секунд максимум
    
    const checkVisible = () => {
      attempts++
      
      if (mapContainer.value) {
        const rect = mapContainer.value.getBoundingClientRect()
        // Debug log removed
        
        if (rect.width > 0 && rect.height > 0) {
          // Debug log removed
          resolve()
          return
        }
      } else {
        // Debug log removed
      }
      
      if (attempts >= maxAttempts) {
        console.warn('Достигнуто максимальное количество попыток ожидания видимости контейнера')
        resolve() // Все равно пытаемся инициализировать
        return
      }
      
      // Проверяем снова через 100ms
      setTimeout(checkVisible, 100)
    }
    
    checkVisible()
  })
}

// Упрощенная инициализация (как в Авито)
const safeInitMap = () => {
  // Загружаем API если нужно
  loadYandexMapsAPI()
  
  // Используем паттерн из Авито - ymaps.ready
  if (window.ymaps) {
    window.ymaps.ready(initMap)
  } else {
    // Если API еще не загружен, ждем и пробуем снова
    let attempts = 0
    const maxAttempts = 10
    
    const checkAndInit = setInterval(() => {
      attempts++
      
      if (window.ymaps) {
        clearInterval(checkAndInit)
        window.ymaps.ready(initMap)
      } else if (attempts >= maxAttempts) {
        clearInterval(checkAndInit)
        console.error('Не удалось загрузить Яндекс.Карты после 10 попыток')
        loading.value = false
      }
    }, 500) // Проверяем каждые 500мс
  }
}

// Lifecycle
onMounted(() => {
  // Простая инициализация как в Авито
  safeInitMap()
})

onUnmounted(() => {
  if (map.value) {
    map.value.destroy()
  }
})

// Добавляем типы для window.ymaps
declare global {
  interface Window {
    ymaps: any
  }
}
</script>

<style scoped>
/* Специфичные стили для элементов Яндекс.Карт API - НЕЛЬЗЯ ТРОГАТЬ! */
:deep(.ymaps-2-1-79-map) {
  width: 100% !important;
  height: 100% !important;
}

:deep(.ymaps-2-1-79-balloon-content) {
  max-width: 200px !important;
}

/* Остальные стили мигрированы на Tailwind CSS в template */
</style>