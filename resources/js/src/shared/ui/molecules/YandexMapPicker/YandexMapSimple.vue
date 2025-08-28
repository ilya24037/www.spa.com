<template>
  <div class="yandex-map-simple">
    <!-- Загрузка -->
    <div 
      v-if="loading" 
      class="yandex-map-simple__loading"
      :style="{ height: height + 'px' }"
    >
      <div class="flex flex-col items-center justify-center h-full">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="text-sm text-gray-600 mt-2">{{ loadingText }}</p>
      </div>
    </div>

    <!-- Ошибка -->
    <div 
      v-else-if="error"
      class="yandex-map-simple__error"
      :style="{ height: height + 'px' }"
    >
      <div class="flex flex-col items-center justify-center h-full text-center p-6">
        <div class="text-red-500 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Ошибка загрузки карты</h3>
        <p class="text-sm text-gray-600 mb-4">{{ error }}</p>
        <button 
          @click="initMap"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
        >
          Попробовать снова
        </button>
      </div>
    </div>

    <!-- Карта -->
    <div
      v-else
      :id="mapId"
      class="yandex-map-simple__container"
      :style="{ height: height + 'px' }"
    >
      <!-- Центральный маркер для single режима -->
      <div
        v-if="mode === 'single' && showSingleMarker && hasCoordinates"
        class="yandex-map-simple__center-marker"
      >
        <svg width="32" height="40" viewBox="0 0 32 40" fill="none">
          <path d="M16 0C7.164 0 0 7.164 0 16C0 24.836 16 40 16 40S32 24.836 32 16C32 7.164 24.836 0 16 0Z" fill="#007BFF"/>
          <circle cx="16" cy="16" r="6" fill="white"/>
          <circle cx="16" cy="16" r="2" fill="#007BFF"/>
        </svg>
      </div>

      <!-- Кнопка геолокации -->
      <button
        v-if="showGeolocationButton && mode === 'single'"
        @click="handleGeolocationClick"
        class="yandex-map-simple__geolocation-btn"
        :class="{ 'active': locationActive }"
        title="Определить местоположение"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// Интерфейс для маркера на карте
export interface MapMarker {
  id: string | number
  lat: number
  lng: number
  title?: string
  subtitle?: string
  url?: string
  phone?: string
  rating?: number
}

// Координаты
export interface Coordinates {
  lat: number
  lng: number
}

interface Props {
  modelValue?: string
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  mode?: 'single' | 'multiple'
  markers?: MapMarker[]
  showGeolocationButton?: boolean
  autoDetectLocation?: boolean
  clusterize?: boolean
  draggable?: boolean
  showSingleMarker?: boolean
  currentAddress?: string
  loadingText?: string
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  center: () => ({ lat: 58.0105, lng: 56.2502 }), // Пермь
  zoom: 15,
  apiKey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
  mode: 'single',
  markers: () => [],
  showGeolocationButton: false,
  autoDetectLocation: false,
  clusterize: false,
  draggable: true,
  showSingleMarker: true,
  loadingText: 'Загрузка карты...'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'marker-moved': [coords: Coordinates]
  'marker-click': [marker: MapMarker]
  'address-found': [address: string, coords: Coordinates]
  'search-error': [error: string]
  'marker-address-hover': [address: string]
}>()

// Реактивные переменные
const mapId = useId('yandex-map-simple')
const loading = ref(true)
const error = ref<string | null>(null)
const locationActive = ref(false)

let map: any = null
let currentCoords = ref<Coordinates>(props.center)

// Computed
const hasCoordinates = computed(() => {
  return props.modelValue && props.modelValue.includes(',')
})

// Простая функция загрузки Yandex Maps (точно такая же как в HTML тесте)
const loadYandexMaps = (): Promise<void> => {
  console.log('🗺️ Vue Simple: Starting loadYandexMaps...')
  
  return new Promise((resolve, reject) => {
    // Если уже загружено и готово
    if (window.ymaps && window.ymaps.ready) {
      console.log('🗺️ Vue Simple: Already loaded, calling ready')
      window.ymaps.ready(() => {
        console.log('🗺️ Vue Simple: Ready callback completed')
        resolve()
      })
      return
    }

    console.log('🗺️ Vue Simple: Creating script tag')
    const script = document.createElement('script')
    script.src = `https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=${props.apiKey}`
    
    script.onload = () => {
      console.log('🗺️ Vue Simple: Script loaded, waiting for ymaps.ready')
      window.ymaps.ready(() => {
        console.log('🗺️ Vue Simple: ymaps.ready completed successfully')
        resolve()
      })
    }
    
    script.onerror = () => {
      console.error('🗺️ Vue Simple: Script loading failed')
      reject(new Error('Не удалось загрузить Яндекс.Карты'))
    }
    
    document.head.appendChild(script)
    console.log('🗺️ Vue Simple: Script tag added to head')
  })
}

// Инициализация карты (простая версия как в HTML тесте)
const initMap = async () => {
  try {
    console.log('🗺️ Vue Simple: Starting initMap...')
    loading.value = true
    error.value = null

    await loadYandexMaps()
    
    // Определяем начальные координаты
    let initialCoords = props.center
    if (props.modelValue && props.modelValue.includes(',')) {
      const [lat, lng] = props.modelValue.split(',').map(Number)
      if (!isNaN(lat) && !isNaN(lng)) {
        initialCoords = { lat, lng }
      }
    }
    currentCoords.value = initialCoords

    // Ждем рендеринга DOM
    await new Promise(resolve => setTimeout(resolve, 100))
    
    const container = document.getElementById(mapId)
    if (!container) {
      throw new Error('Контейнер карты не найден')
    }

    // Определяем, мобильное ли устройство
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    
    // Создаем карту (точно как в HTML тесте)
    const controls = props.mode === 'single' 
      ? ['zoomControl', 'typeSelector'] 
      : ['zoomControl', 'searchControl']
      
    map = new window.ymaps.Map(mapId, {
      center: [initialCoords.lat, initialCoords.lng],
      zoom: props.zoom,
      controls,
      behaviors: isMobile 
        ? ['drag', 'dblClickZoom', 'multiTouch'] 
        : ['default']
    })

    // Устанавливаем ограничения зума
    map.options.set('minZoom', 10)
    map.options.set('maxZoom', 18)
    
    // Настройки для мобильных устройств
    if (isMobile) {
      map.behaviors.enable('multiTouch')
      map.options.set('suppressMapOpenBlock', true)
      map.options.set('dragInertiaEnable', true)
    }

    // Обработчики событий
    map.events.add('click', (e: any) => {
      if (props.mode === 'single' && props.draggable) {
        const coords = e.get('coords')
        const newCoords = { lat: coords[0], lng: coords[1] }
        currentCoords.value = newCoords
        const coordsString = `${coords[0]},${coords[1]}`
        emit('update:modelValue', coordsString)
        emit('marker-moved', newCoords)
      }
    })

    loading.value = false
    console.log('🗺️ Vue Simple: Map initialization completed successfully!')
    
  } catch (err) {
    console.error('🗺️ Vue Simple: Map initialization failed:', err)
    loading.value = false
    error.value = err instanceof Error ? err.message : 'Ошибка загрузки карты'
  }
}

// Геолокация
const handleGeolocationClick = async () => {
  if (!navigator.geolocation || !map) return
  
  locationActive.value = true
  
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const coords = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      }
      
      map.setCenter([coords.lat, coords.lng], 16)
      currentCoords.value = coords
      
      const coordsString = `${coords.lat},${coords.lng}`
      emit('update:modelValue', coordsString)
      emit('marker-moved', coords)
      
      locationActive.value = false
    },
    (error) => {
      console.error('Ошибка геолокации:', error)
      locationActive.value = false
    },
    { timeout: 5000 }
  )
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue && newValue.includes(',') && map) {
    const [lat, lng] = newValue.split(',').map(Number)
    if (!isNaN(lat) && !isNaN(lng)) {
      map.setCenter([lat, lng])
      currentCoords.value = { lat, lng }
    }
  }
})

// Lifecycle
onMounted(() => {
  console.log('🗺️ Vue Simple: Component mounted, starting initialization...')
  initMap()
})

onUnmounted(() => {
  if (map) {
    map.destroy()
    map = null
  }
})

// Типы для window.ymaps
declare global {
  interface Window {
    ymaps: any
  }
}
</script>

<style scoped>
.yandex-map-simple {
  @apply relative w-full;
}

.yandex-map-simple__container {
  @apply relative w-full rounded-lg overflow-hidden bg-gray-100;
  position: relative;
}

.yandex-map-simple__loading,
.yandex-map-simple__error {
  @apply relative w-full rounded-lg bg-gray-100 border border-gray-200;
}

.yandex-map-simple__center-marker {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -100%);
  z-index: 10;
  pointer-events: none;
}

.yandex-map-simple__geolocation-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10;
  @apply w-10 h-10 bg-white rounded-lg shadow-md border border-gray-200 flex items-center justify-center text-gray-600 hover:text-blue-600 transition-colors;
}

.yandex-map-simple__geolocation-btn.active {
  @apply text-blue-600 bg-blue-50;
}

@media (max-width: 640px) {
  .yandex-map-simple__container {
    @apply rounded-none;
  }
}
</style>